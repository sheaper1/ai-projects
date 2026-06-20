from __future__ import annotations

import html
import logging
from datetime import date, datetime, timedelta

from aiogram import Bot, F, Router
from aiogram.filters import CommandStart
from aiogram.fsm.context import FSMContext
from aiogram.types import CallbackQuery, Message

from massage_bot import keyboards, texts
from massage_bot.database import BookingRequest, Database, Service
from massage_bot.schedule import decode_slot, filter_slots_by_day, format_date_label, format_slot, generate_slots, slot_is_available
from massage_bot.states import (
    AddonAdminState,
    AdminRequestState,
    BusinessSettingsState,
    ManualBookingState,
    ScheduleAdminState,
    ServiceAdminState,
)


WEEKDAYS = {
    1: "Понеділок",
    2: "Вівторок",
    3: "Середа",
    4: "Четвер",
    5: "П'ятниця",
    6: "Субота",
    7: "Неділя",
}

logger = logging.getLogger(__name__)


def build_admin_router(db: Database, admin_ids: tuple[int, ...], timezone=None) -> Router:
    router = Router()
    # Адмінський роутер обробляє лише повідомлення від адмінів. Повідомлення
    # звичайних клієнтів (зокрема ті, що збігаються з текстом адмін-кнопок або
    # схожі на дату) пропускаються далі — у клієнтський роутер, який пересилає
    # їх майстру. Callback-запити фільтруються окремо через guard().
    router.message.filter(
        lambda message: message.from_user is not None and message.from_user.id in admin_ids
    )

    def is_admin(user_id: int) -> bool:
        return user_id in admin_ids

    async def guard(callback: CallbackQuery) -> bool:
        if not is_admin(callback.from_user.id):
            await callback.answer(texts.ADMIN_ONLY, show_alert=True)
            return False
        return True

    async def guard_message(message: Message) -> bool:
        if not is_admin(message.from_user.id):
            await message.answer(texts.ADMIN_ONLY)
            return False
        return True

    @router.message(
        CommandStart(),
        lambda message: message.from_user and message.from_user.id in admin_ids,
    )
    async def admin_start(message: Message, state: FSMContext) -> None:
        await state.clear()
        db.clear_pending_booking(message.from_user.id)
        await message.answer(texts.GREETING, reply_markup=keyboards.main_menu(True))

    @router.callback_query(F.data == "admin:menu")
    async def menu(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        await state.clear()
        await callback.message.edit_text(texts.ADMIN_MENU, reply_markup=keyboards.admin_menu())
        await callback.answer()

    @router.callback_query(F.data == "admin:dashboard")
    async def dashboard(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        today = datetime.now(timezone).date() if timezone else date.today()
        counts = db.get_dashboard_counts(today)
        await callback.message.edit_text(
            "Стан на сьогодні\n\n"
            f"Нові заявки: {counts['pending']}\n"
            f"Підтверджені записи сьогодні: {counts['today_confirmed']}\n"
            f"Активні послуги: {counts['active_services']}\n"
            f"Закриті майбутні дати: {len(db.list_days_off(today))}",
            reply_markup=keyboards.admin_menu(),
        )
        await callback.answer()

    @router.message(F.text == "Адмін-меню")
    async def menu_from_keyboard(message: Message, state: FSMContext) -> None:
        if not await guard_message(message):
            return
        await state.clear()
        await message.answer(texts.ADMIN_MENU, reply_markup=keyboards.home_keyboard(True))

    @router.message(F.text == "Головне меню")
    async def home_from_keyboard(message: Message, state: FSMContext) -> None:
        if not await guard_message(message):
            return
        await state.clear()
        await message.answer(texts.MAIN_MENU, reply_markup=keyboards.main_menu(True))

    @router.message(F.text == "Послуги")
    async def services_from_keyboard(message: Message) -> None:
        if not await guard_message(message):
            return
        await message.answer(
            "Послуги. Оберіть послугу або додайте нову.",
            reply_markup=keyboards.admin_services_keyboard(db.list_services(active_only=False)),
        )

    @router.message(F.text == "Графік роботи")
    async def schedule_from_keyboard(message: Message) -> None:
        if not await guard_message(message):
            return
        await message.answer(_schedule_text(db), reply_markup=keyboards.schedule_days_keyboard(db.list_working_hours()))

    @router.message(F.text == "Інструкція")
    async def help_from_keyboard(message: Message) -> None:
        if not await guard_message(message):
            return
        await message.answer(
            _ADMIN_HELP_INTRO,
            reply_markup=keyboards.admin_help_menu_keyboard(_admin_help_topics_list()),
        )

    @router.message(F.text == "Додати запис")
    async def manual_booking_start(message: Message, state: FSMContext) -> None:
        if not await guard_message(message):
            return
        services = db.list_services(active_only=True)
        service_lines = "\n".join(f"{service.id}: {service.name}" for service in services)
        await state.set_state(ManualBookingState.entering)
        await message.answer(
            "Надішліть запис одним повідомленням:\n"
            "Ім'я; телефон; ID послуги; дата; час\n\n"
            "Приклад: Олена; 0981234567; 1; 05.06; 14:00\n\n"
            f"Послуги:\n{service_lines}",
            reply_markup=keyboards.home_keyboard(True),
        )

    @router.message(ManualBookingState.entering)
    async def manual_booking_create(message: Message, state: FSMContext) -> None:
        if not await guard_message(message):
            return
        parsed = _parse_manual_booking(message.text or "", timezone)
        if parsed is None:
            await message.answer("Не вдалося прочитати. Приклад: Олена; 0981234567; 1; 05.06; 14:00")
            return
        client_name, phone, service_id, slot_start = parsed
        service = db.get_service(service_id)
        if service is None or not service.is_active:
            await message.answer("Послугу не знайдено або її вимкнено.")
            return
        if not slot_is_available(db, service, slot_start, timezone):
            await message.answer("Цей час недоступний. Оберіть інший день або час.")
            return
        slot_end = slot_start + timedelta(minutes=service.duration_minutes)
        request_id = db.create_request(
            user_id=message.from_user.id,
            user_name=message.from_user.username,
            client_name=client_name,
            phone=phone,
            service_id=service.id,
            slot_start=slot_start,
            slot_end=slot_end,
            status="confirmed",
        )
        await state.clear()
        await message.answer(
            f"Запис #{request_id} додано.\n{client_name}\n{service.name}\n{format_slot(slot_start)}",
            reply_markup=keyboards.home_keyboard(True),
        )

    @router.message(F.text == "Нові заявки")
    async def pending_requests_from_keyboard(message: Message, state: FSMContext) -> None:
        if not await guard_message(message):
            return
        await state.clear()
        requests = db.list_pending_requests()
        if not requests:
            await message.answer("Нових заявок поки немає.", reply_markup=keyboards.home_keyboard(True))
            return
        await message.answer(
            "Нові заявки. Оберіть заявку кнопкою знизу:",
            reply_markup=keyboards.pending_requests_reply_keyboard(requests),
        )

    @router.message(F.text.startswith("Заявка #"))
    async def request_from_keyboard(message: Message, state: FSMContext) -> None:
        if not await guard_message(message):
            return
        request_id = _request_id_from_text(message.text or "")
        if request_id is None:
            await message.answer("Не вдалося прочитати номер заявки.", reply_markup=keyboards.home_keyboard(True))
            return
        request = db.get_request(request_id)
        if request is None:
            await message.answer("Заявку не знайдено.", reply_markup=keyboards.home_keyboard(True))
            return
        await state.set_state(AdminRequestState.viewing)
        await state.update_data(admin_request_id=request.id)
        await message.answer(
            _request_text(db, request),
            reply_markup=keyboards.request_decision_reply_keyboard(),
            parse_mode="HTML",
        )

    @router.message(AdminRequestState.viewing, F.text.in_({"Підтвердити", "Відхилити", "Скасувати", "Перенести"}))
    async def decide_request_from_keyboard(message: Message, state: FSMContext, bot: Bot) -> None:
        if not await guard_message(message):
            return
        data = await state.get_data()
        request = db.get_request(int(data["admin_request_id"]))
        if request is None:
            await state.clear()
            await message.answer("Заявку не знайдено.", reply_markup=keyboards.home_keyboard(True))
            return
        if message.text == "Підтвердити":
            if request.status != "pending":
                await _already_processed_message(request, message)
                await state.clear()
                return
            await _confirm_request_from_message(db, request, bot, message)
            await state.clear()
        elif message.text == "Відхилити":
            if request.status != "pending":
                await _already_processed_message(request, message)
                await state.clear()
                return
            await _decline_request_from_message(db, request, bot, message)
            await state.clear()
        elif message.text == "Скасувати":
            if request.status == "cancelled":
                await _already_processed_message(request, message)
                await state.clear()
                return
            await _cancel_request_from_message(db, request, bot, message)
            await state.clear()
        else:
            if request.status in {"cancelled", "declined"}:
                await _already_processed_message(request, message)
                await state.clear()
                return
            await _start_reschedule_from_message(db, request, message, state, timezone)

    @router.callback_query(F.data.startswith("admin:reschedule:"))
    async def reschedule_request(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        request_id = int(callback.data.split(":")[2])
        request = db.get_request(request_id)
        if request is None:
            await callback.answer("Заявку не знайдено.", show_alert=True)
            return
        if request.status in {"cancelled", "declined"}:
            await callback.answer(f"Запис уже оброблено: {_status_label(request.status)}.", show_alert=True)
            return
        await _start_reschedule_from_callback(db, request, callback, state, timezone)

    @router.callback_query(AdminRequestState.reschedule_date, F.data.startswith("date:"))
    async def reschedule_date_chosen(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        _, service_id_raw, encoded_day = callback.data.split(":")
        service = db.get_service(int(service_id_raw))
        if service is None:
            await callback.answer("Послугу не знайдено.", show_alert=True)
            return
        data = await state.get_data()
        extra = _request_addons_minutes(db, int(data.get("admin_request_id", 0)))
        selected_day = datetime.strptime(encoded_day, "%Y%m%d").date()
        slots = generate_slots(db, service, timezone, extra_minutes=extra)
        day_slots = filter_slots_by_day(slots, selected_day)
        if not day_slots:
            await callback.answer("На цей день немає вільного часу.", show_alert=True)
            return
        await state.set_state(AdminRequestState.reschedule_slot)
        await callback.message.edit_text(
            f"Оберіть новий час для запису:",
            reply_markup=keyboards.day_slots_keyboard(service.id, day_slots),
        )
        await callback.answer()

    @router.callback_query(AdminRequestState.reschedule_slot, F.data.startswith("slot:"))
    async def reschedule_slot_chosen(callback: CallbackQuery, state: FSMContext, bot: Bot) -> None:
        if not await guard(callback):
            return
        data = await state.get_data()
        request = db.get_request(int(data["admin_request_id"]))
        if request is None:
            await callback.answer("Заявку не знайдено.", show_alert=True)
            await state.clear()
            return
        _, service_id_raw, encoded_slot = callback.data.split(":")
        service = db.get_service(int(service_id_raw))
        if service is None:
            await callback.answer("Послугу не знайдено.", show_alert=True)
            return
        extra = _request_addons_minutes(db, request.id)
        slot_start = decode_slot(encoded_slot, timezone)
        if not slot_is_available(db, service, slot_start, timezone, exclude_request_id=request.id, extra_minutes=extra):
            await callback.answer("Цей час вже недоступний.", show_alert=True)
            return
        slot_end = slot_start + timedelta(minutes=service.duration_minutes + extra)
        db.update_request_slot(request.id, slot_start, slot_end, status="confirmed")
        if request.user_id not in admin_ids:
            await bot.send_message(
                request.user_id,
                f"Ваш запис перенесено.\nПослуга: {request.service_name}\nНовий час: {format_slot(slot_start)}",
                reply_markup=keyboards.client_confirmed_booking_keyboard(request.id),
            )
        await callback.message.edit_text(f"Запис #{request.id} перенесено на {format_slot(slot_start)}.")
        await state.clear()
        await callback.answer()

    @router.message(F.text == "Записи за днями")
    @router.message(F.text == "До списку днів")
    async def bookings_by_day_from_keyboard(message: Message, state: FSMContext) -> None:
        if not await guard_message(message):
            return
        await state.clear()
        today = datetime.now(timezone).date() if timezone else date.today()
        counts = db.count_requests_by_day(today, 14)
        await message.answer(
            "Записи за днями. Оберіть день кнопкою знизу:",
            reply_markup=keyboards.admin_booking_days_reply_keyboard(today, counts),
        )

    @router.message(F.text.regexp(r"^\d{2}\.\d{2}"))
    async def bookings_for_day_from_keyboard(message: Message) -> None:
        if not await guard_message(message):
            return
        selected_day = _day_from_button_text(message.text or "", timezone)
        requests = db.list_requests_for_day(selected_day)
        await message.answer(_bookings_day_text(db, selected_day, requests), reply_markup=keyboards.admin_day_bookings_reply_keyboard())

    @router.callback_query(F.data == "admin:services")
    async def services(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        await callback.message.edit_text(
            "Послуги. Оберіть послугу або додайте нову.",
            reply_markup=keyboards.admin_services_keyboard(db.list_services(active_only=False)),
        )
        await callback.answer()

    async def _show_service_details(callback: CallbackQuery, service_id: int, note: str | None = None) -> None:
        service = db.get_service(service_id)
        if service is None:
            await callback.answer("Послугу не знайдено.", show_alert=True)
            return
        text = _service_text(service)
        if note:
            text = f"{note}\n\n{text}"
        await callback.message.edit_text(text, reply_markup=keyboards.service_edit_keyboard(service))

    # ── Додавання послуги: назва (текст) → тривалість → ціна (кнопки) ──────────

    @router.callback_query(F.data == "admin:add_service")
    async def add_service(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        await state.set_state(ServiceAdminState.adding_name)
        await callback.message.edit_text(
            "Додаємо нову послугу.\n\nКрок 1 з 3 — надішліть назву послуги одним повідомленням."
        )
        await callback.answer()

    @router.message(ServiceAdminState.adding_name)
    async def add_service_name(message: Message, state: FSMContext) -> None:
        name = (message.text or "").strip()
        if not _valid_service_name(name):
            await message.answer("Назва має бути від 1 до 80 символів. Спробуйте ще раз.")
            return
        await state.update_data(new_name=name)
        await state.set_state(ServiceAdminState.adding_duration)
        await message.answer(
            f"Назва: {name}\n\nКрок 2 з 3 — оберіть тривалість 👇",
            reply_markup=keyboards.duration_picker_keyboard("admin:add_dur", "admin:services"),
        )

    @router.callback_query(ServiceAdminState.adding_duration, F.data.startswith("admin:add_dur:"))
    async def add_service_duration(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        duration = int(callback.data.split(":")[2])
        await state.update_data(new_duration=duration)
        await state.set_state(ServiceAdminState.adding_price)
        await callback.message.edit_text(
            f"Тривалість: {duration} хв\n\nКрок 3 з 3 — оберіть ціну 👇",
            reply_markup=keyboards.price_picker_keyboard(
                "admin:add_price", "admin:add_price_manual", "admin:services"
            ),
        )
        await callback.answer()

    @router.callback_query(ServiceAdminState.adding_price, F.data == "admin:add_price_manual")
    async def add_service_price_manual(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        await state.set_state(ServiceAdminState.adding_price_manual)
        await callback.message.edit_text("Надішліть ціну в грн одним числом (наприклад: 750).")
        await callback.answer()

    @router.callback_query(ServiceAdminState.adding_price, F.data.startswith("admin:add_price:"))
    async def add_service_price(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        price = int(callback.data.split(":")[2])
        data = await state.get_data()
        db.add_service(data["new_name"], int(data["new_duration"]), price)
        await state.clear()
        await callback.message.edit_text(
            "Послугу додано ✅",
            reply_markup=keyboards.admin_services_keyboard(db.list_services(active_only=False)),
        )
        await callback.answer()

    @router.message(ServiceAdminState.adding_price_manual)
    async def add_service_price_typed(message: Message, state: FSMContext) -> None:
        price = _parse_price(message.text)
        if price is None:
            await message.answer("Ціна має бути числом від 0 до 100000. Спробуйте ще раз.")
            return
        data = await state.get_data()
        db.add_service(data["new_name"], int(data["new_duration"]), price)
        await state.clear()
        await message.answer(
            "Послугу додано ✅",
            reply_markup=keyboards.admin_services_keyboard(db.list_services(active_only=False)),
        )

    @router.callback_query(F.data.startswith("admin:service:"))
    async def service_details(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        await state.clear()
        await _show_service_details(callback, int(callback.data.split(":")[2]))
        await callback.answer()

    # ── Редагування наявної послуги ───────────────────────────────────────────

    @router.callback_query(F.data.startswith("admin:svc_name:"))
    async def edit_service_name(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        service_id = int(callback.data.split(":")[2])
        if db.get_service(service_id) is None:
            await callback.answer("Послугу не знайдено.", show_alert=True)
            return
        await state.set_state(ServiceAdminState.editing_name)
        await state.update_data(service_id=service_id)
        await callback.message.edit_text("Надішліть нову назву послуги одним повідомленням.")
        await callback.answer()

    @router.message(ServiceAdminState.editing_name)
    async def service_name_typed(message: Message, state: FSMContext) -> None:
        name = (message.text or "").strip()
        if not _valid_service_name(name):
            await message.answer("Назва має бути від 1 до 80 символів. Спробуйте ще раз.")
            return
        data = await state.get_data()
        service_id = int(data["service_id"])
        db.set_service_name(service_id, name)
        service = db.get_service(service_id)
        await state.clear()
        await message.answer(
            f"Назву оновлено ✅\n\n{_service_text(service)}",
            reply_markup=keyboards.service_edit_keyboard(service),
        )

    @router.callback_query(F.data.startswith("admin:svc_dur:"))
    async def edit_service_duration(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        service_id = int(callback.data.split(":")[2])
        if db.get_service(service_id) is None:
            await callback.answer("Послугу не знайдено.", show_alert=True)
            return
        await callback.message.edit_text(
            "Оберіть нову тривалість 👇",
            reply_markup=keyboards.duration_picker_keyboard(
                f"admin:svc_dur_set:{service_id}", f"admin:service:{service_id}"
            ),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:svc_dur_set:"))
    async def set_service_duration(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        parts = callback.data.split(":")
        service_id, minutes = int(parts[2]), int(parts[3])
        db.set_service_duration(service_id, minutes)
        await _show_service_details(callback, service_id, note="Тривалість оновлено ✅")
        await callback.answer("Збережено")

    @router.callback_query(F.data.startswith("admin:svc_price_set:"))
    async def set_service_price_cb(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        parts = callback.data.split(":")
        service_id, price = int(parts[2]), int(parts[3])
        db.set_service_price(service_id, price)
        await _show_service_details(callback, service_id, note="Ціну оновлено ✅")
        await callback.answer("Збережено")

    @router.callback_query(F.data.startswith("admin:svc_price_manual:"))
    async def edit_service_price_manual(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        service_id = int(callback.data.split(":")[2])
        if db.get_service(service_id) is None:
            await callback.answer("Послугу не знайдено.", show_alert=True)
            return
        await state.set_state(ServiceAdminState.editing_price_manual)
        await state.update_data(service_id=service_id)
        await callback.message.edit_text("Надішліть ціну в грн одним числом (наприклад: 750).")
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:svc_price:"))
    async def edit_service_price(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        service_id = int(callback.data.split(":")[2])
        if db.get_service(service_id) is None:
            await callback.answer("Послугу не знайдено.", show_alert=True)
            return
        await callback.message.edit_text(
            "Оберіть нову ціну 👇",
            reply_markup=keyboards.price_picker_keyboard(
                f"admin:svc_price_set:{service_id}",
                f"admin:svc_price_manual:{service_id}",
                f"admin:service:{service_id}",
            ),
        )
        await callback.answer()

    @router.message(ServiceAdminState.editing_price_manual)
    async def service_price_typed(message: Message, state: FSMContext) -> None:
        price = _parse_price(message.text)
        if price is None:
            await message.answer("Ціна має бути числом від 0 до 100000. Спробуйте ще раз.")
            return
        data = await state.get_data()
        service_id = int(data["service_id"])
        db.set_service_price(service_id, price)
        service = db.get_service(service_id)
        await state.clear()
        await message.answer(
            f"Ціну оновлено ✅\n\n{_service_text(service)}",
            reply_markup=keyboards.service_edit_keyboard(service),
        )

    @router.callback_query(F.data.startswith("admin:disable_service:"))
    async def disable_service(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        service_id = int(callback.data.split(":")[2])
        db.deactivate_service(service_id)
        await callback.message.edit_text(
            "Послугу вимкнено для клієнтів.",
            reply_markup=keyboards.admin_services_keyboard(db.list_services(active_only=False)),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:enable_service:"))
    async def enable_service(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        service_id = int(callback.data.split(":")[2])
        db.activate_service(service_id)
        await callback.message.edit_text(
            "Послугу знову увімкнено для клієнтів.",
            reply_markup=keyboards.admin_services_keyboard(db.list_services(active_only=False)),
        )
        await callback.answer()

    # ── Додаткові послуги (add-ons) ───────────────────────────────────────────

    async def _show_addon_details(callback: CallbackQuery, addon_id: int, note: str | None = None) -> None:
        addon = db.get_addon(addon_id)
        if addon is None:
            await callback.answer("Допослугу не знайдено.", show_alert=True)
            return
        text = _addon_text(addon)
        if note:
            text = f"{note}\n\n{text}"
        await callback.message.edit_text(text, reply_markup=keyboards.addon_edit_keyboard(addon))

    @router.callback_query(F.data == "admin:addons")
    async def addons_list(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        await state.clear()
        await callback.message.edit_text(
            "Додаткові послуги. Оберіть зі списку або додайте нову.",
            reply_markup=keyboards.admin_addons_keyboard(db.list_addons(active_only=False)),
        )
        await callback.answer()

    @router.callback_query(F.data == "admin:add_addon")
    async def add_addon(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        await state.set_state(AddonAdminState.adding_name)
        await callback.message.edit_text(
            "Додаємо допослугу.\n\nКрок 1 з 3 — надішліть назву одним повідомленням."
        )
        await callback.answer()

    @router.message(AddonAdminState.adding_name)
    async def add_addon_name(message: Message, state: FSMContext) -> None:
        name = (message.text or "").strip()
        if not _valid_service_name(name):
            await message.answer("Назва має бути від 1 до 80 символів. Спробуйте ще раз.")
            return
        await state.update_data(new_name=name)
        await state.set_state(AddonAdminState.adding_duration)
        await message.answer(
            f"Назва: {name}\n\nКрок 2 з 3 — оберіть тривалість (0 — не впливає на час) 👇",
            reply_markup=keyboards.duration_picker_keyboard(
                "admin:add_adn_dur", "admin:addons", presets=keyboards.ADDON_DURATION_PRESETS
            ),
        )

    @router.callback_query(AddonAdminState.adding_duration, F.data.startswith("admin:add_adn_dur:"))
    async def add_addon_duration(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        duration = int(callback.data.split(":")[2])
        await state.update_data(new_duration=duration)
        await state.set_state(AddonAdminState.adding_price)
        await callback.message.edit_text(
            f"Тривалість: {duration} хв\n\nКрок 3 з 3 — оберіть ціну 👇",
            reply_markup=keyboards.price_picker_keyboard(
                "admin:add_adn_price",
                "admin:add_adn_price_manual",
                "admin:addons",
                presets=keyboards.ADDON_PRICE_PRESETS,
            ),
        )
        await callback.answer()

    @router.callback_query(AddonAdminState.adding_price, F.data == "admin:add_adn_price_manual")
    async def add_addon_price_manual(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        await state.set_state(AddonAdminState.adding_price_manual)
        await callback.message.edit_text("Надішліть ціну в грн одним числом (наприклад: 150).")
        await callback.answer()

    @router.callback_query(AddonAdminState.adding_price, F.data.startswith("admin:add_adn_price:"))
    async def add_addon_price(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        price = int(callback.data.split(":")[2])
        data = await state.get_data()
        db.add_addon(data["new_name"], int(data["new_duration"]), price)
        await state.clear()
        await callback.message.edit_text(
            "Допослугу додано ✅",
            reply_markup=keyboards.admin_addons_keyboard(db.list_addons(active_only=False)),
        )
        await callback.answer()

    @router.message(AddonAdminState.adding_price_manual)
    async def add_addon_price_typed(message: Message, state: FSMContext) -> None:
        price = _parse_price(message.text)
        if price is None:
            await message.answer("Ціна має бути числом від 0 до 100000. Спробуйте ще раз.")
            return
        data = await state.get_data()
        db.add_addon(data["new_name"], int(data["new_duration"]), price)
        await state.clear()
        await message.answer(
            "Допослугу додано ✅",
            reply_markup=keyboards.admin_addons_keyboard(db.list_addons(active_only=False)),
        )

    @router.callback_query(F.data.startswith("admin:addon:"))
    async def addon_details(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        await state.clear()
        await _show_addon_details(callback, int(callback.data.split(":")[2]))
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:adn_name:"))
    async def edit_addon_name(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        addon_id = int(callback.data.split(":")[2])
        if db.get_addon(addon_id) is None:
            await callback.answer("Допослугу не знайдено.", show_alert=True)
            return
        await state.set_state(AddonAdminState.editing_name)
        await state.update_data(addon_id=addon_id)
        await callback.message.edit_text("Надішліть нову назву допослуги одним повідомленням.")
        await callback.answer()

    @router.message(AddonAdminState.editing_name)
    async def addon_name_typed(message: Message, state: FSMContext) -> None:
        name = (message.text or "").strip()
        if not _valid_service_name(name):
            await message.answer("Назва має бути від 1 до 80 символів. Спробуйте ще раз.")
            return
        data = await state.get_data()
        addon_id = int(data["addon_id"])
        db.set_addon_name(addon_id, name)
        addon = db.get_addon(addon_id)
        await state.clear()
        await message.answer(
            f"Назву оновлено ✅\n\n{_addon_text(addon)}",
            reply_markup=keyboards.addon_edit_keyboard(addon),
        )

    @router.callback_query(F.data.startswith("admin:adn_dur:"))
    async def edit_addon_duration(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        addon_id = int(callback.data.split(":")[2])
        if db.get_addon(addon_id) is None:
            await callback.answer("Допослугу не знайдено.", show_alert=True)
            return
        await callback.message.edit_text(
            "Оберіть тривалість (0 — не впливає на час) 👇",
            reply_markup=keyboards.duration_picker_keyboard(
                f"admin:adn_dur_set:{addon_id}", f"admin:addon:{addon_id}", presets=keyboards.ADDON_DURATION_PRESETS
            ),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:adn_dur_set:"))
    async def set_addon_duration(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        parts = callback.data.split(":")
        addon_id, minutes = int(parts[2]), int(parts[3])
        db.set_addon_duration(addon_id, minutes)
        await _show_addon_details(callback, addon_id, note="Тривалість оновлено ✅")
        await callback.answer("Збережено")

    @router.callback_query(F.data.startswith("admin:adn_price_set:"))
    async def set_addon_price_cb(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        parts = callback.data.split(":")
        addon_id, price = int(parts[2]), int(parts[3])
        db.set_addon_price(addon_id, price)
        await _show_addon_details(callback, addon_id, note="Ціну оновлено ✅")
        await callback.answer("Збережено")

    @router.callback_query(F.data.startswith("admin:adn_price_manual:"))
    async def edit_addon_price_manual(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        addon_id = int(callback.data.split(":")[2])
        if db.get_addon(addon_id) is None:
            await callback.answer("Допослугу не знайдено.", show_alert=True)
            return
        await state.set_state(AddonAdminState.editing_price_manual)
        await state.update_data(addon_id=addon_id)
        await callback.message.edit_text("Надішліть ціну в грн одним числом (наприклад: 150).")
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:adn_price:"))
    async def edit_addon_price(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        addon_id = int(callback.data.split(":")[2])
        if db.get_addon(addon_id) is None:
            await callback.answer("Допослугу не знайдено.", show_alert=True)
            return
        await callback.message.edit_text(
            "Оберіть ціну 👇",
            reply_markup=keyboards.price_picker_keyboard(
                f"admin:adn_price_set:{addon_id}",
                f"admin:adn_price_manual:{addon_id}",
                f"admin:addon:{addon_id}",
                presets=keyboards.ADDON_PRICE_PRESETS,
            ),
        )
        await callback.answer()

    @router.message(AddonAdminState.editing_price_manual)
    async def addon_price_typed(message: Message, state: FSMContext) -> None:
        price = _parse_price(message.text)
        if price is None:
            await message.answer("Ціна має бути числом від 0 до 100000. Спробуйте ще раз.")
            return
        data = await state.get_data()
        addon_id = int(data["addon_id"])
        db.set_addon_price(addon_id, price)
        addon = db.get_addon(addon_id)
        await state.clear()
        await message.answer(
            f"Ціну оновлено ✅\n\n{_addon_text(addon)}",
            reply_markup=keyboards.addon_edit_keyboard(addon),
        )

    @router.callback_query(F.data.startswith("admin:disable_addon:"))
    async def disable_addon(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        addon_id = int(callback.data.split(":")[2])
        db.deactivate_addon(addon_id)
        await _show_addon_details(callback, addon_id, note="Вимкнено для клієнтів.")
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:enable_addon:"))
    async def enable_addon(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        addon_id = int(callback.data.split(":")[2])
        db.activate_addon(addon_id)
        await _show_addon_details(callback, addon_id, note="Знову доступно клієнтам.")
        await callback.answer()

    @router.callback_query(F.data == "admin:schedule")
    async def schedule(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        await state.clear()
        await callback.message.edit_text(
            _schedule_text(db),
            reply_markup=keyboards.schedule_days_keyboard(db.list_working_hours()),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:hday:"))
    async def hours_day(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        weekday = int(callback.data.split(":")[2])
        hours = db.get_working_hours(weekday)
        await callback.message.edit_text(
            _hours_day_text(weekday, hours),
            reply_markup=keyboards.hours_day_keyboard(weekday, hours),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:htoggle:"))
    async def hours_toggle(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        weekday = int(callback.data.split(":")[2])
        hours = db.get_working_hours(weekday)
        start = hours.start_time if hours else "10:00"
        end = hours.end_time if hours else "19:00"
        new_working = not (hours.is_working if hours else True)
        db.set_working_hours(weekday, start, end, new_working)
        hours = db.get_working_hours(weekday)
        await callback.message.edit_text(
            _hours_day_text(weekday, hours),
            reply_markup=keyboards.hours_day_keyboard(weekday, hours),
        )
        await callback.answer("Збережено")

    @router.callback_query(F.data.startswith("admin:htime:"))
    async def hours_time_grid(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        _, _, weekday_raw, which = callback.data.split(":")
        weekday = int(weekday_raw)
        title = "початок" if which == "s" else "кінець"
        await callback.message.edit_text(
            f"{WEEKDAYS[weekday]}: оберіть {title} робочого дня 👇",
            reply_markup=keyboards.time_grid_keyboard(weekday, which),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:hpick:"))
    async def hours_pick(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        _, _, weekday_raw, which, minutes_raw = callback.data.split(":")
        weekday = int(weekday_raw)
        picked = f"{int(minutes_raw) // 60:02d}:{int(minutes_raw) % 60:02d}"
        hours = db.get_working_hours(weekday)
        start = hours.start_time if hours else "10:00"
        end = hours.end_time if hours else "19:00"
        is_working = hours.is_working if hours else True
        if which == "s":
            start = picked
        else:
            end = picked
        if _time_minutes(start) >= _time_minutes(end):
            await callback.answer("Початок має бути раніше за кінець.", show_alert=True)
            return
        db.set_working_hours(weekday, start, end, is_working)
        hours = db.get_working_hours(weekday)
        await callback.message.edit_text(
            _hours_day_text(weekday, hours),
            reply_markup=keyboards.hours_day_keyboard(weekday, hours),
        )
        await callback.answer("Збережено")

    @router.callback_query(F.data.in_({"admin:close_day", "admin:open_day"}))
    async def edit_day_off(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        mode = "close" if callback.data == "admin:close_day" else "open"
        await state.set_state(ScheduleAdminState.editing_day_off)
        await state.update_data(day_off_mode=mode)
        action = "закрити" if mode == "close" else "відкрити"
        await callback.message.edit_text(
            f"Надішліть дату, яку потрібно {action}, у форматі ДД.ММ або ДД.ММ.РРРР."
        )
        await callback.answer()

    @router.message(ScheduleAdminState.editing_day_off)
    async def day_off_updated(message: Message, state: FSMContext) -> None:
        if not await guard_message(message):
            return
        selected_day = _parse_calendar_day(message.text or "", timezone)
        if selected_day is None:
            await message.answer("Не вдалося прочитати дату. Приклад: 25.12 або 25.12.2026")
            return
        data = await state.get_data()
        if data["day_off_mode"] == "close":
            active_requests = [
                request
                for request in db.list_requests_for_day(selected_day)
                if request.status in {"pending", "confirmed"}
            ]
            if active_requests:
                await message.answer(
                    f"На цю дату є {len(active_requests)} активних запис(ів). "
                    "Спочатку перенесіть або скасуйте їх."
                )
                return
            db.set_day_off(selected_day)
            result = f"{selected_day.strftime('%d.%m.%Y')} закрито для запису."
        else:
            db.remove_day_off(selected_day)
            result = f"{selected_day.strftime('%d.%m.%Y')} знову відкрито для запису."
        await state.clear()
        await message.answer(result, reply_markup=keyboards.home_keyboard(True))

    @router.callback_query(F.data == "admin:requests")
    async def pending_requests(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        requests = db.list_pending_requests()
        if not requests:
            await callback.message.edit_text("Нових заявок поки немає.", reply_markup=keyboards.admin_menu())
            await callback.answer()
            return
        await callback.message.edit_text(
            "Нові заявки:",
            reply_markup=keyboards.pending_requests_keyboard(requests),
        )
        await callback.answer()

    @router.callback_query(F.data == "admin:help")
    async def admin_help(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        await state.clear()
        await callback.message.edit_text(
            _ADMIN_HELP_INTRO,
            reply_markup=keyboards.admin_help_menu_keyboard(_admin_help_topics_list()),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:help:"))
    async def admin_help_topic(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        topic = _ADMIN_HELP_TOPICS.get(callback.data.split(":")[2])
        if topic is None:
            await callback.answer("Тему не знайдено.", show_alert=True)
            return
        title, body = topic
        await callback.message.edit_text(
            f"{title}\n\n{body}",
            reply_markup=keyboards.admin_help_topic_keyboard(),
        )
        await callback.answer()

    @router.callback_query(F.data == "admin:location")
    async def edit_location(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        current_address = db.get_setting("business_address", "не вказано")
        current_map = db.get_setting("map_url", "не вказано")
        await state.set_state(BusinessSettingsState.editing_location)
        await callback.message.edit_text(
            "Надішліть адресу та посилання на карту через крапку з комою:\n"
            "Адреса; https://maps.google.com/...\n\n"
            "Посилання можна залишити порожнім: Адреса;\n\n"
            f"Поточна адреса: {current_address}\n"
            f"Поточна карта: {current_map}"
        )
        await callback.answer()

    @router.message(BusinessSettingsState.editing_location)
    async def location_updated(message: Message, state: FSMContext) -> None:
        if not await guard_message(message):
            return
        parts = [part.strip() for part in (message.text or "").split(";", maxsplit=1)]
        if len(parts) != 2 or not parts[0]:
            await message.answer("Формат: Адреса; посилання на карту")
            return
        address, map_url = parts
        if map_url and not map_url.startswith(("https://", "http://")):
            await message.answer("Посилання має починатися з https:// або http://")
            return
        db.set_setting("business_address", address)
        db.set_setting("map_url", map_url)
        await state.clear()
        await message.answer("Адресу та карту оновлено.", reply_markup=keyboards.home_keyboard(True))

    @router.callback_query(F.data == "admin:bookings")
    async def bookings_by_day(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        today = datetime.now(timezone).date() if timezone else date.today()
        counts = db.count_requests_by_day(today, 14)
        await callback.message.edit_text(
            "Записи за днями. Оберіть день, щоб подивитися заявки та підтверджені записи.",
            reply_markup=keyboards.admin_booking_days_keyboard(today, counts),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:bookings:"))
    async def bookings_for_day(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        encoded_day = callback.data.split(":")[2]
        selected_day = datetime.strptime(encoded_day, "%Y%m%d").date()
        requests = db.list_requests_for_day(selected_day)
        await callback.message.edit_text(
            _bookings_day_text(db, selected_day, requests),
            reply_markup=keyboards.admin_day_bookings_keyboard(selected_day, requests),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:req:"))
    async def request_details(callback: CallbackQuery) -> None:
        if not await guard(callback):
            return
        request_id = int(callback.data.split(":")[2])
        request = db.get_request(request_id)
        if request is None:
            await callback.answer("Заявку не знайдено.", show_alert=True)
            return
        await callback.message.edit_text(
            _request_text(db, request),
            reply_markup=keyboards.request_actions(request.id, request.status),
            parse_mode="HTML",
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:confirm:"))
    async def confirm_request(callback: CallbackQuery, bot: Bot) -> None:
        if not await guard(callback):
            return
        request_id = int(callback.data.split(":")[2])
        request = db.get_request(request_id)
        if request is None:
            await callback.answer("Заявку не знайдено.", show_alert=True)
            return
        if request.status != "pending":
            await callback.answer(f"Заявку вже оброблено: {_status_label(request.status)}.", show_alert=True)
            return
        slot_start = datetime.fromisoformat(request.slot_start)
        slot_end = datetime.fromisoformat(request.slot_end)
        if db.list_confirmed_slots(slot_start, slot_end):
            await callback.answer("Цей слот уже зайнятий іншим підтвердженим записом.", show_alert=True)
            return
        db.set_request_status(request_id, "confirmed")
        notification_failed = False
        try:
            await bot.send_message(
                request.user_id,
                f"Ваш запис підтверджено.\nПослуга: {request.service_name}\nЧас: {format_slot(slot_start)}",
                reply_markup=keyboards.client_confirmed_booking_keyboard(request_id),
            )
        except Exception:
            notification_failed = True
            logger.exception("Failed to notify user about confirmed request %s", request_id)
        result = f"Заявку #{request_id} підтверджено."
        if notification_failed:
            result += "\n\n⚠️ Не вдалося надіслати повідомлення клієнту. Зв'яжіться з ним телефоном."
        await callback.message.edit_text(result, reply_markup=keyboards.admin_menu())
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:cancel:"))
    async def cancel_request(callback: CallbackQuery, bot: Bot) -> None:
        if not await guard(callback):
            return
        request_id = int(callback.data.split(":")[2])
        request = db.get_request(request_id)
        if request is None:
            await callback.answer("Заявку не знайдено.", show_alert=True)
            return
        if request.status == "cancelled":
            await callback.answer("Запис уже скасовано.", show_alert=True)
            return
        db.set_request_status(request_id, "cancelled")
        if request.user_id not in admin_ids:
            await bot.send_message(request.user_id, "Ваш запис скасовано адміністратором.")
        await callback.message.edit_text(f"Запис #{request_id} скасовано.", reply_markup=keyboards.admin_menu())
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:decline:"))
    async def decline_request(callback: CallbackQuery, bot: Bot) -> None:
        if not await guard(callback):
            return
        request_id = int(callback.data.split(":")[2])
        request = db.get_request(request_id)
        if request is None:
            await callback.answer("Заявку не знайдено.", show_alert=True)
            return
        if request.status != "pending":
            await callback.answer(f"Заявку вже оброблено: {_status_label(request.status)}.", show_alert=True)
            return
        db.set_request_status(request_id, "declined")
        await bot.send_message(
            request.user_id,
            "На жаль, цей час не вдалося підтвердити. Оберіть, будь ласка, інший слот або напишіть майстру.",
        )
        await callback.message.edit_text(f"Заявку #{request_id} відхилено.", reply_markup=keyboards.admin_menu())
        await callback.answer()

    @router.callback_query(F.data.startswith("admin:"))
    async def stale_admin_callback(callback: CallbackQuery, state: FSMContext) -> None:
        if not await guard(callback):
            return
        await state.clear()
        await callback.answer("Ця кнопка вже неактуальна.", show_alert=True)
        await callback.message.answer(
            "Ця дія вже неактуальна або бот був перезапущений. Відкрийте адмін-меню і спробуйте ще раз.",
            reply_markup=keyboards.home_keyboard(True),
        )

    return router


def _valid_service_name(name: str) -> bool:
    return bool(name) and len(name) <= 80


def _parse_price(text: str | None) -> int | None:
    raw = (text or "").strip()
    if not raw.isdigit():
        return None
    price = int(raw)
    return price if 0 <= price <= 100_000 else None


def _hours_day_text(weekday: int, hours) -> str:
    if hours is None:
        return f"{WEEKDAYS[weekday]}\n\nГрафік ще не задано. Оберіть час або зробіть день робочим."
    if hours.is_working:
        state_line = f"Робочий день: {hours.start_time}–{hours.end_time}"
    else:
        state_line = f"Вихідний (збережений час: {hours.start_time}–{hours.end_time})"
    return f"{WEEKDAYS[weekday]}\n\n{state_line}\n\nЗмінюйте кнопками нижче 👇"


def _parse_service_payload(text: str | None) -> tuple[str, int, int] | None:
    if not text:
        return None
    parts = [part.strip() for part in text.split(";")]
    if len(parts) != 3:
        return None
    name, duration_raw, price_raw = parts
    if not name or len(name) > 80 or not duration_raw.isdigit() or not price_raw.isdigit():
        return None
    duration = int(duration_raw)
    price = int(price_raw)
    if not 15 <= duration <= 240 or not 0 <= price <= 100_000:
        return None
    return name, duration, price


def _request_id_from_text(text: str) -> int | None:
    prefix = "Заявка #"
    if not text.startswith(prefix):
        return None
    number = text.removeprefix(prefix).split("·", maxsplit=1)[0].strip()
    return int(number) if number.isdigit() else None


def _day_from_button_text(text: str, timezone) -> date:
    raw = text.split("·", maxsplit=1)[0].strip()
    day = datetime.strptime(raw, "%d.%m").date()
    today = datetime.now(timezone).date() if timezone else date.today()
    selected = day.replace(year=today.year)
    if selected < today:
        selected = selected.replace(year=today.year + 1)
    return selected


def _schedule_text(db: Database) -> str:
    rows = db.list_working_hours()
    text = "Поточний графік:\n\n" + "\n".join(
        f"{WEEKDAYS[item.weekday]}: "
        f"{item.start_time}-{item.end_time}" if item.is_working else f"{WEEKDAYS[item.weekday]}: вихідний"
        for item in rows
    )
    days_off = db.list_days_off(date.today())
    if days_off:
        text += "\n\nЗакриті дати:\n" + ", ".join(day.strftime("%d.%m.%Y") for day in days_off[:10])
    return text


def _parse_calendar_day(value: str, timezone) -> date | None:
    today = datetime.now(timezone).date() if timezone else date.today()
    for pattern in ("%d.%m.%Y", "%d.%m"):
        try:
            parsed = datetime.strptime(value.strip(), pattern).date()
        except ValueError:
            continue
        if pattern == "%d.%m":
            parsed = parsed.replace(year=today.year)
            if parsed < today:
                parsed = parsed.replace(year=today.year + 1)
        if parsed < today:
            return None
        return parsed
    return None


async def _already_processed_message(request: BookingRequest, message: Message) -> None:
    await message.answer(
        f"Цю заявку вже оброблено: {_status_label(request.status)}.",
        reply_markup=keyboards.home_keyboard(True),
    )


async def _confirm_request_from_message(db: Database, request: BookingRequest, bot: Bot, message: Message) -> None:
    slot_start = datetime.fromisoformat(request.slot_start)
    slot_end = datetime.fromisoformat(request.slot_end)
    if db.list_confirmed_slots(slot_start, slot_end):
        await message.answer("Цей слот уже зайнятий іншим підтвердженим записом.", reply_markup=keyboards.home_keyboard(True))
        return
    db.set_request_status(request.id, "confirmed")
    await bot.send_message(
        request.user_id,
        f"Ваш запис підтверджено.\nПослуга: {request.service_name}\nЧас: {format_slot(slot_start)}",
        reply_markup=keyboards.client_confirmed_booking_keyboard(request.id),
    )
    await message.answer(f"Заявку #{request.id} підтверджено.", reply_markup=keyboards.home_keyboard(True))


async def _decline_request_from_message(db: Database, request: BookingRequest, bot: Bot, message: Message) -> None:
    db.set_request_status(request.id, "declined")
    await bot.send_message(
        request.user_id,
        "На жаль, цей час не вдалося підтвердити. Оберіть, будь ласка, інший слот або напишіть майстру.",
    )
    await message.answer(f"Заявку #{request.id} відхилено.", reply_markup=keyboards.home_keyboard(True))


async def _cancel_request_from_message(db: Database, request: BookingRequest, bot: Bot, message: Message) -> None:
    db.set_request_status(request.id, "cancelled")
    if request.user_id != message.from_user.id:
        await bot.send_message(request.user_id, "Ваш запис скасовано адміністратором.")
    await message.answer(f"Запис #{request.id} скасовано.", reply_markup=keyboards.home_keyboard(True))


def _request_addons_minutes(db: Database, request_id: int) -> int:
    if not request_id:
        return 0
    return sum(addon.duration_minutes for addon in db.list_request_addons(request_id))


async def _start_reschedule_from_message(
    db: Database,
    request: BookingRequest,
    message: Message,
    state: FSMContext,
    timezone,
) -> None:
    service = db.get_service(request.service_id)
    if service is None:
        await message.answer("Послугу не знайдено.", reply_markup=keyboards.home_keyboard(True))
        return
    slots = generate_slots(db, service, timezone, extra_minutes=_request_addons_minutes(db, request.id))
    if not slots:
        await message.answer("Немає вільних слотів для перенесення.", reply_markup=keyboards.home_keyboard(True))
        return
    await state.set_state(AdminRequestState.reschedule_date)
    await state.update_data(admin_request_id=request.id)
    await message.answer("Оберіть новий день:", reply_markup=keyboards.home_keyboard(True))
    await message.answer("Доступні дні:", reply_markup=keyboards.dates_keyboard(service.id, slots))


async def _start_reschedule_from_callback(
    db: Database,
    request: BookingRequest,
    callback: CallbackQuery,
    state: FSMContext,
    timezone,
) -> None:
    service = db.get_service(request.service_id)
    if service is None:
        await callback.answer("Послугу не знайдено.", show_alert=True)
        return
    slots = generate_slots(db, service, timezone, extra_minutes=_request_addons_minutes(db, request.id))
    if not slots:
        await callback.answer("Немає вільних слотів для перенесення.", show_alert=True)
        return
    await state.set_state(AdminRequestState.reschedule_date)
    await state.update_data(admin_request_id=request.id)
    await callback.message.edit_text("Оберіть новий день:", reply_markup=keyboards.dates_keyboard(service.id, slots))
    await callback.answer()


def _parse_manual_booking(text: str, timezone) -> tuple[str, str, int, datetime] | None:
    parts = [part.strip() for part in text.split(";")]
    if len(parts) != 5:
        return None
    client_name, phone, service_id_raw, day_raw, time_raw = parts
    normalized_phone = _normalize_phone(phone)
    if (
        not client_name
        or not any(char.isalpha() for char in client_name)
        or not service_id_raw.isdigit()
        or not _valid_phone(normalized_phone)
    ):
        return None
    try:
        day = datetime.strptime(day_raw, "%d.%m").date()
        hour_minute = datetime.strptime(time_raw, "%H:%M").time()
    except ValueError:
        return None
    today = datetime.now(timezone).date() if timezone else date.today()
    day = day.replace(year=today.year)
    if day < today:
        day = day.replace(year=today.year + 1)
    slot_start = datetime.combine(day, hour_minute, tzinfo=timezone)
    if slot_start <= datetime.now(timezone):
        return None
    return client_name, normalized_phone, int(service_id_raw), slot_start


def _parse_hours_payload(text: str | None) -> tuple[int, str, str, bool] | None:
    if not text:
        return None
    parts = [part.strip().lower() for part in text.split(";")]
    if len(parts) != 4 or not parts[0].isdigit():
        return None
    weekday = int(parts[0])
    if weekday < 1 or weekday > 7:
        return None
    start_time, end_time = parts[1], parts[2]
    if not _looks_like_time(start_time) or not _looks_like_time(end_time):
        return None
    status = parts[3]
    if status not in {"працюю", "вихідний"}:
        return None
    if status == "працюю" and _time_minutes(start_time) >= _time_minutes(end_time):
        return None
    return weekday, start_time, end_time, status == "працюю"


def _looks_like_time(value: str) -> bool:
    try:
        hour, minute = value.split(":", maxsplit=1)
        return 0 <= int(hour) <= 23 and 0 <= int(minute) <= 59
    except ValueError:
        return False


def _time_minutes(value: str) -> int:
    hour, minute = value.split(":", maxsplit=1)
    return int(hour) * 60 + int(minute)


def _normalize_phone(value: str) -> str:
    return "".join(char for char in value if char not in " -()")


def _valid_phone(value: str) -> bool:
    return (
        len(value) == 10 and value.startswith("0") and value.isdigit()
    ) or (
        len(value) == 13 and value.startswith("+380") and value[1:].isdigit()
    )


def _service_text(service: Service) -> str:
    status = "активна" if service.is_active else "вимкнена"
    return (
        f"Послуга: {service.name}\n"
        f"Тривалість: {service.duration_minutes} хв\n"
        f"Ціна: {texts.format_price(service.price_uah)}\n"
        f"Статус: {status}"
    )


def _addon_text(addon) -> str:
    duration = f"{addon.duration_minutes} хв" if addon.duration_minutes else "без додаткового часу"
    status = "активна" if addon.is_active else "вимкнена"
    return (
        f"Допослуга: {addon.name}\n"
        f"Тривалість: {duration}\n"
        f"Ціна: {addon.price_uah} грн\n"
        f"Статус: {status}"
    )


def _bookings_day_text(db: Database, day: date, requests: list[BookingRequest]) -> str:
    title = f"{format_date_label(day)} · записи"
    if not requests:
        return f"{title}\n\nНа цей день записів поки немає."

    lines = [title, ""]
    for request in requests:
        slot_start = datetime.fromisoformat(request.slot_start)
        entry = (
            f"{slot_start.strftime('%H:%M')} · {_status_label(request.status)} · #{request.id}\n"
            f"{request.client_name} · {request.phone}\n"
            f"{request.service_name}"
        )
        addons = db.list_request_addons(request.id)
        if addons:
            entry += "\n+ " + ", ".join(addon.name for addon in addons)
        lines.append(entry)
    return "\n\n".join(lines)


def _status_label(status: str) -> str:
    labels = {
        "pending": "очікує",
        "confirmed": "підтверджено",
        "declined": "відхилено",
        "cancelled": "скасовано",
        "expired": "прострочено",
    }
    return labels.get(status, status)


_ADMIN_HELP_INTRO = "📖 Інструкція\n\nОберіть тему — поясню коротко й по ділу 👇"

_ADMIN_HELP_TOPICS: dict[str, tuple[str, str]] = {
    "requests": (
        "🆕 Нові заявки",
        "Коли клієнт записується, заявка приходить вам сюди і окремим сповіщенням із кнопками.\n\n"
        "Відкрийте заявку й оберіть:\n"
        "• Підтвердити — клієнт отримає підтвердження.\n"
        "• Відхилити — клієнту прийде відмова з пропозицією обрати інший час.\n"
        "• Перенести — оберіть новий день і час кнопками.\n"
        "• Скасувати — скасувати запис.\n\n"
        "У заявці видно ім'я, телефон, послугу, допослуги й суму, а також контакт клієнта — "
        "тапніть «@…» чи «відкрити чат», щоб одразу написати йому в Telegram.",
    ),
    "bookings": (
        "📅 Записи за днями",
        "Показує по днях, що вже заплановано. Оберіть день — побачите час, статус, ім'я, "
        "телефон, послугу й допослуги.\n\n"
        "Звідти можна відкрити будь-яку заявку й одразу підтвердити чи перенести.",
    ),
    "services": (
        "💆 Послуги",
        "Оберіть послугу зі списку й змінюйте кнопками:\n"
        "• ✏️ Назва — надішліть нову назву повідомленням.\n"
        "• ⏱ Тривалість — оберіть зі списку.\n"
        "• 💰 Ціна — готові суми або «Інша сума».\n"
        "• Вимкнути / Увімкнути — приховати від клієнтів або повернути.\n\n"
        "Кнопка «Додати послугу» проведе через 3 кроки: назва → тривалість → ціна.",
    ),
    "addons": (
        "➕ Додаткові послуги",
        "Це додатки до основної послуги (гарячі камені, аромотерапія тощо). Клієнт обирає їх "
        "галочками під час запису, ціни й час підсумовуються автоматично.\n\n"
        "Редагуються так само кнопками, що й послуги. Важливо про тривалість:\n"
        "• 0 хв — допослуга додає лише ціну й не подовжує сеанс.\n"
        "• більше 0 — бот враховує цей час у вільних слотах, щоб сеанси не накладалися.",
    ),
    "schedule": (
        "🕐 Графік роботи",
        "Оберіть день тижня й налаштуйте кнопками:\n"
        "• 🕐 Початок / Кінець — час обирається з готової сітки.\n"
        "• Зробити вихідним / робочим.\n\n"
        "Окремо «Закрити дату» / «Відкрити дату» — для відпустки чи свята на конкретний день.",
    ),
    "automation": (
        "🤖 Що бот робить сам",
        "• Нагадує клієнту про сеанс за добу.\n"
        "• Якщо клієнт почав запис і не завершив — згодом делікатно нагадує продовжити.\n"
        "• Непідтверджена заявка автоматично звільняє час, якщо її довго не підтверджували.\n"
        "• Незрозумілі повідомлення від клієнтів пересилає вам разом із контактом для відповіді.",
    ),
}


def _admin_help_topics_list() -> list[tuple[str, str]]:
    return [(key, title) for key, (title, _) in _ADMIN_HELP_TOPICS.items()]


def _request_text(db: Database, request: BookingRequest) -> str:
    slot_start = datetime.fromisoformat(request.slot_start)
    contact_line = texts.client_contact_block(request.user_id, request.user_name)
    addons = db.list_request_addons(request.id)
    service = db.get_service(request.service_id)
    if service is not None:
        service_line = (
            f"Послуга: {html.escape(request.service_name)} "
            f"({service.duration_minutes} хв) — {texts.format_price(service.price_uah)}"
        )
    else:
        service_line = f"Послуга: {html.escape(request.service_name)}"
    lines = [
        f"Заявка #{request.id}",
        f"Клієнт: {html.escape(request.client_name)}",
        f"Телефон: {html.escape(request.phone)}",
        contact_line,
        service_line,
    ]
    if addons:
        for addon in addons:
            extra = f"+{addon.price_uah} грн"
            if addon.duration_minutes:
                extra += f", +{addon.duration_minutes} хв"
            lines.append(f"  • {html.escape(addon.name)} ({extra})")
        base_price = service.price_uah if service else 0
        base_minutes = service.duration_minutes if service else 0
        addon_price = sum(addon.price_uah for addon in addons)
        total_minutes = base_minutes + sum(addon.duration_minutes for addon in addons)
        lines.append(f"💰 Разом: {total_minutes} хв · {texts.format_total(base_price, addon_price)}")
    lines.append(f"Час: {html.escape(format_slot(slot_start))}")
    lines.append(f"Статус: {_status_label(request.status)}")
    return "\n".join(lines)
