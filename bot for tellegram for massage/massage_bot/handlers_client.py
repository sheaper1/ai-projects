from __future__ import annotations

import html
import logging
import re
from datetime import datetime, timedelta

from aiogram import Bot, F, Router
from aiogram.filters import Command, CommandStart
from aiogram.fsm.context import FSMContext
from aiogram.types import CallbackQuery, Message, ReplyKeyboardRemove

from massage_bot import keyboards, texts
from massage_bot.database import Addon, Database, Service
from massage_bot.schedule import decode_slot, filter_slots_by_day, format_slot, generate_slots, slot_is_available
from massage_bot.states import BookingState

logger = logging.getLogger(__name__)

_PHONE_RE = re.compile(r"^(\+?380\d{9}|0\d{9})$")

# Тексти адмінських reply-кнопок. Для адмінів їх обробляє адмін-роутер; у
# клієнтському роутері вони ловляться лише тоді, коли кнопку натиснув не-адмін
# зі застарілою клавіатурою — щоб повернути його в меню, а не слати майстру.
_REPLY_BUTTON_LABELS = frozenset(
    {
        "Головне меню",
        "Адмін-меню",
        "Нові заявки",
        "Записи за днями",
        "Інструкція",
        "Додати запис",
        "Послуги",
        "Графік роботи",
        "До списку днів",
    }
)

def build_client_router(
    db: Database,
    admin_ids: tuple[int, ...],
    timezone,
    business_address: str = "",
    map_url: str = "",
) -> Router:
    router = Router()

    def is_admin(user_id: int) -> bool:
        return user_id in admin_ids

    # ── /start ────────────────────────────────────────────────────────────────

    @router.message(CommandStart())
    async def start(message: Message, state: FSMContext) -> None:
        user_id = message.from_user.id
        await state.clear()
        db.clear_pending_booking(user_id)
        await message.answer(texts.GREETING, reply_markup=keyboards.main_menu(is_admin(user_id)))

    @router.message(Command("book"))
    async def book_command(message: Message, state: FSMContext) -> None:
        services = db.list_services()
        if not services:
            await message.answer("Наразі немає доступних послуг.")
            return
        await state.set_state(BookingState.choosing_service)
        db.upsert_pending_booking(message.from_user.id, message.chat.id)
        await message.answer(
            texts.ASK_SERVICE,
            reply_markup=keyboards.booking_services_keyboard(services),
        )

    @router.message(Command("my_bookings"))
    async def my_bookings_command(message: Message) -> None:
        requests = db.list_user_requests(
            message.from_user.id,
            from_dt=datetime.now(timezone),
            active_only=True,
        )
        if not requests:
            await message.answer(
                "У вас немає майбутніх активних записів.",
                reply_markup=keyboards.main_menu(is_admin(message.from_user.id)),
            )
            return
        await message.answer(
            _user_bookings_text(db, requests),
            reply_markup=keyboards.client_bookings_keyboard(requests),
        )

    # ── Menu navigation ───────────────────────────────────────────────────────

    @router.callback_query(F.data == "client:back")
    async def back_to_menu(callback: CallbackQuery, state: FSMContext) -> None:
        user_id = callback.from_user.id
        await state.clear()
        db.clear_pending_booking(user_id)
        await callback.message.edit_text(
            texts.GREETING,
            reply_markup=keyboards.main_menu(is_admin(user_id)),
        )
        await callback.answer()

    @router.callback_query(F.data == "client:services_prices")
    async def services_prices(callback: CallbackQuery) -> None:
        await callback.message.edit_text(
            _services_text(db.list_services()),
            reply_markup=keyboards.services_prices_keyboard(),
        )
        await callback.answer()

    @router.callback_query(F.data == "client:address")
    async def address(callback: CallbackQuery) -> None:
        await callback.message.edit_text(
            _address_text(db, business_address, map_url),
            reply_markup=keyboards.address_keyboard(),
        )
        await callback.answer()

    @router.callback_query(F.data == "client:bookings")
    async def my_bookings(callback: CallbackQuery) -> None:
        requests = db.list_user_requests(
            callback.from_user.id,
            from_dt=datetime.now(timezone),
            active_only=True,
        )
        if not requests:
            await callback.message.edit_text(
                "У вас немає майбутніх активних записів.",
                reply_markup=keyboards.services_prices_keyboard(),
            )
            await callback.answer()
            return
        await callback.message.edit_text(
            _user_bookings_text(db, requests),
            reply_markup=keyboards.client_bookings_keyboard(requests),
        )
        await callback.answer()

    @router.callback_query(F.data == "client:faq")
    async def faq(callback: CallbackQuery) -> None:
        await callback.message.edit_text(
            "Оберіть питання, яке вас цікавить 👇",
            reply_markup=keyboards.faq_keyboard(),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("faq:"))
    async def faq_answer(callback: CallbackQuery) -> None:
        key = callback.data.split(":")[1]
        answers = {
            "payment": texts.FAQ_PAYMENT,
            "duration": texts.FAQ_DURATION,
            "contraindications": texts.FAQ_CONTRAINDICATIONS,
            "reschedule": texts.FAQ_RESCHEDULE,
        }
        text = answers.get(key, "Відповідь не знайдена.")
        await callback.message.edit_text(text, reply_markup=keyboards.back_to_menu_keyboard())
        await callback.answer()

    # ── Booking FSM ───────────────────────────────────────────────────────────

    @router.callback_query(F.data == "client:book")
    async def book_start(callback: CallbackQuery, state: FSMContext) -> None:
        user_id = callback.from_user.id
        services = db.list_services()
        if not services:
            await callback.answer("Наразі немає доступних послуг.", show_alert=True)
            return
        await state.set_state(BookingState.choosing_service)
        db.upsert_pending_booking(user_id, callback.message.chat.id)
        await callback.message.edit_text(
            texts.ASK_SERVICE,
            reply_markup=keyboards.booking_services_keyboard(services),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("bservice:"))
    async def service_chosen(callback: CallbackQuery, state: FSMContext) -> None:
        service_id = int(callback.data.split(":")[1])
        service = db.get_service(service_id)
        if service is None or not service.is_active:
            await callback.answer("Послуга більше недоступна.", show_alert=True)
            return
        await state.update_data(service_id=service.id, addon_ids=[])
        addons = db.list_addons(active_only=True)
        if addons:
            await state.set_state(BookingState.choosing_addons)
            await callback.message.edit_text(
                _addons_prompt(service, []),
                reply_markup=keyboards.booking_addons_keyboard(addons, set()),
            )
            await callback.answer()
            return
        slots = generate_slots(db, service, timezone)
        if not slots:
            await callback.answer("Для цієї послуги поки немає вільного часу.", show_alert=True)
            return
        await state.set_state(BookingState.choosing_date)
        await callback.message.edit_text(
            texts.ASK_DATE,
            reply_markup=keyboards.dates_keyboard(service.id, slots, back_callback="client:book"),
        )
        await callback.answer()

    @router.callback_query(BookingState.choosing_addons, F.data.startswith("addon:toggle:"))
    async def addons_toggle(callback: CallbackQuery, state: FSMContext) -> None:
        addon_id = int(callback.data.split(":")[2])
        data = await state.get_data()
        service = db.get_service(int(data.get("service_id", 0)))
        if service is None or not service.is_active:
            await callback.answer("Послуга більше недоступна.", show_alert=True)
            return
        selected = list(data.get("addon_ids", []))
        if addon_id in selected:
            selected.remove(addon_id)
        else:
            selected.append(addon_id)
        await state.update_data(addon_ids=selected)
        addons = db.list_addons(active_only=True)
        chosen = [addon for addon in addons if addon.id in set(selected)]
        await callback.message.edit_text(
            _addons_prompt(service, chosen),
            reply_markup=keyboards.booking_addons_keyboard(addons, set(selected)),
        )
        await callback.answer()

    @router.callback_query(BookingState.choosing_addons, F.data == "addon:done")
    async def addons_done(callback: CallbackQuery, state: FSMContext) -> None:
        data = await state.get_data()
        service = db.get_service(int(data.get("service_id", 0)))
        if service is None or not service.is_active:
            await callback.answer("Послуга більше недоступна.", show_alert=True)
            return
        addons = _selected_addons(db, data.get("addon_ids", []))
        slots = generate_slots(db, service, timezone, extra_minutes=_addons_minutes(addons))
        if not slots:
            await callback.answer(
                "Для цього набору поки немає вільного часу. Приберіть щось або оберіть іншу послугу.",
                show_alert=True,
            )
            return
        await state.set_state(BookingState.choosing_date)
        await callback.message.edit_text(
            texts.ASK_DATE,
            reply_markup=keyboards.dates_keyboard(service.id, slots, back_callback="addon:reopen"),
        )
        await callback.answer()

    @router.callback_query(F.data == "addon:reopen")
    async def addons_reopen(callback: CallbackQuery, state: FSMContext) -> None:
        data = await state.get_data()
        service = db.get_service(int(data.get("service_id", 0)))
        if service is None or not service.is_active:
            await callback.answer("Почніть запис заново: /start", show_alert=True)
            return
        addons = db.list_addons(active_only=True)
        selected = set(data.get("addon_ids", []))
        await state.set_state(BookingState.choosing_addons)
        await callback.message.edit_text(
            _addons_prompt(service, [addon for addon in addons if addon.id in selected]),
            reply_markup=keyboards.booking_addons_keyboard(addons, selected),
        )
        await callback.answer()

    @router.callback_query(F.data == "client:dates")
    async def reopen_dates(callback: CallbackQuery, state: FSMContext) -> None:
        data = await state.get_data()
        service = db.get_service(int(data.get("service_id", 0)))
        if service is None or not service.is_active:
            await callback.answer("Почніть запис заново: /start", show_alert=True)
            return
        addons = _selected_addons(db, data.get("addon_ids", []))
        slots = generate_slots(db, service, timezone, extra_minutes=_addons_minutes(addons))
        if not slots:
            await callback.answer("Вільного часу вже немає.", show_alert=True)
            return
        await state.set_state(BookingState.choosing_date)
        await callback.message.edit_text(
            texts.ASK_DATE,
            reply_markup=keyboards.dates_keyboard(service.id, slots, back_callback=_dates_back_callback(db)),
        )
        await callback.answer()

    @router.callback_query(BookingState.choosing_date, F.data.startswith("date:"))
    async def date_chosen(callback: CallbackQuery, state: FSMContext) -> None:
        _, service_id_raw, encoded_day = callback.data.split(":")
        service = db.get_service(int(service_id_raw))
        if service is None or not service.is_active:
            await callback.answer("Послуга більше недоступна.", show_alert=True)
            return
        data = await state.get_data()
        addons = _selected_addons(db, data.get("addon_ids", []))
        selected_day = datetime.strptime(encoded_day, "%Y%m%d").date()
        slots = filter_slots_by_day(
            generate_slots(db, service, timezone, extra_minutes=_addons_minutes(addons)),
            selected_day,
        )
        if not slots:
            await callback.answer("На цей день вільного часу вже немає.", show_alert=True)
            return
        await state.set_state(BookingState.choosing_slot)
        await callback.message.edit_text(
            texts.ASK_TIME,
            reply_markup=keyboards.day_slots_keyboard(
                service.id,
                slots,
                back_callback="client:dates",
            ),
        )
        await callback.answer()

    @router.callback_query(BookingState.choosing_slot, F.data.startswith("slot:"))
    async def slot_chosen(callback: CallbackQuery, state: FSMContext) -> None:
        _, service_id_raw, encoded_slot = callback.data.split(":")
        service = db.get_service(int(service_id_raw))
        if service is None or not service.is_active:
            await callback.answer("Послуга більше недоступна.", show_alert=True)
            return
        data = await state.get_data()
        addons = _selected_addons(db, data.get("addon_ids", []))
        slot_start = decode_slot(encoded_slot, timezone)
        if not slot_is_available(db, service, slot_start, timezone, extra_minutes=_addons_minutes(addons)):
            await callback.answer("Цей час уже недоступний. Оберіть інший.", show_alert=True)
            return
        await state.update_data(service_id=service.id, slot=encoded_slot)
        await state.set_state(BookingState.entering_name)
        await callback.message.edit_text(f"{service.name}\n{format_slot(slot_start)}\n\n{texts.ASK_NAME}")
        await callback.answer()

    @router.message(BookingState.entering_name)
    async def name_entered(message: Message, state: FSMContext) -> None:
        if not message.text:
            await message.answer(texts.UNSUPPORTED_MESSAGE)
            return
        name = message.text.strip()
        if len(name) < 2 or len(name) > 60 or not any(char.isalpha() for char in name):
            await message.answer("Вкажіть ім'я від 2 до 60 символів, використовуючи літери.")
            return
        await state.update_data(name=name)
        await state.set_state(BookingState.entering_phone)
        await message.answer(texts.ASK_PHONE, reply_markup=keyboards.phone_keyboard())

    @router.message(BookingState.entering_phone)
    async def phone_entered(message: Message, state: FSMContext) -> None:
        phone = _normalize_phone(_extract_phone(message))
        if not _validate_phone(phone):
            await message.answer(texts.PHONE_INVALID)
            return
        await state.update_data(phone=phone)
        await state.set_state(BookingState.confirming)
        data = await state.get_data()
        service = db.get_service(int(data["service_id"]))
        if service is None:
            await state.clear()
            await message.answer("Послуга більше недоступна. Почніть запис заново: /start")
            return
        slot_start = decode_slot(data["slot"], timezone)
        addons = _selected_addons(db, data.get("addon_ids", []))
        summary = _booking_summary(service, slot_start, addons, data["name"], phone)
        await message.answer("Телефон отримано.", reply_markup=ReplyKeyboardRemove())
        await message.answer(summary, reply_markup=keyboards.booking_confirmation_keyboard())

    @router.callback_query(BookingState.confirming, F.data == "booking:confirm")
    async def booking_confirmed(callback: CallbackQuery, state: FSMContext, bot: Bot) -> None:
        data = await state.get_data()
        user = callback.from_user
        service = db.get_service(int(data["service_id"]))
        if service is None or not service.is_active:
            await callback.answer("Послуга більше недоступна.", show_alert=True)
            return
        slot_start = decode_slot(data["slot"], timezone)
        addons = _selected_addons(db, data.get("addon_ids", []))
        extra_minutes = _addons_minutes(addons)
        if not slot_is_available(db, service, slot_start, timezone, extra_minutes=extra_minutes):
            await state.set_state(BookingState.choosing_date)
            await callback.message.edit_text(
                "Цей час уже зайнято. Оберіть інший день:",
                reply_markup=keyboards.dates_keyboard(
                    service.id,
                    generate_slots(db, service, timezone, extra_minutes=extra_minutes),
                    back_callback=_dates_back_callback(db),
                ),
            )
            await callback.answer()
            return
        slot_end = slot_start + timedelta(minutes=service.duration_minutes + extra_minutes)

        request_id = db.create_request_if_available(
            user_id=user.id,
            user_name=user.username,
            client_name=data["name"],
            phone=data["phone"],
            service_id=service.id,
            slot_start=slot_start,
            slot_end=slot_end,
            addons=addons,
        )
        if request_id is None:
            await state.set_state(BookingState.choosing_date)
            await callback.message.edit_text(
                "Цей час щойно зайняли. Оберіть інший день:",
                reply_markup=keyboards.dates_keyboard(
                    service.id,
                    generate_slots(db, service, timezone, extra_minutes=extra_minutes),
                    back_callback=_dates_back_callback(db),
                ),
            )
            await callback.answer()
            return

        contact_line = texts.client_contact_block(user.id, user.username)
        admin_text = texts.ADMIN_REQUEST_TEMPLATE.format(
            name=html.escape(data["name"]),
            phone=html.escape(data["phone"]),
            service=html.escape(f"{service.name} ({service.duration_minutes} хв) — {texts.format_price(service.price_uah)}"),
            time=html.escape(format_slot(slot_start)),
            telegram_line=contact_line,
        )
        if addons:
            total_minutes = service.duration_minutes + extra_minutes
            extras = "Додатково:\n" + "\n".join(
                f"• {html.escape(addon.name)} ({_addon_extra_label(addon)})" for addon in addons
            )
            total_str = texts.format_total(service.price_uah, _addons_price(addons))
            admin_text = f"{admin_text}\n\n{extras}\n💰 Разом: {total_minutes} хв · {total_str}"
        for admin_id in admin_ids:
            try:
                await bot.send_message(
                    admin_id,
                    f"Заявка #{request_id}\n\n{admin_text}",
                    reply_markup=keyboards.request_actions(request_id),
                    parse_mode="HTML",
                )
            except Exception:
                logger.exception("Failed to notify admin %s", admin_id)

        db.clear_pending_booking(user.id)
        await state.clear()
        await callback.message.edit_text(
            texts.BOOKING_DONE,
            reply_markup=keyboards.main_menu(is_admin(user.id)),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("client:cancel_prompt:"))
    async def cancel_booking_prompt(callback: CallbackQuery) -> None:
        request_id = int(callback.data.split(":")[2])
        request = db.get_request(request_id)
        if request is None or request.user_id != callback.from_user.id:
            await callback.answer("Запис не знайдено.", show_alert=True)
            return
        if request.status not in {"pending", "confirmed"}:
            await callback.answer("Цей запис уже неактивний.", show_alert=True)
            return
        await callback.message.edit_text(
            f"Скасувати запис #{request.id}?\n"
            f"{request.service_name}\n"
            f"{format_slot(datetime.fromisoformat(request.slot_start))}",
            reply_markup=keyboards.client_cancel_confirmation_keyboard(request.id),
        )
        await callback.answer()

    @router.callback_query(F.data.startswith("client:cancel:"))
    async def cancel_booking(callback: CallbackQuery, state: FSMContext, bot: Bot) -> None:
        request_id = int(callback.data.split(":")[2])
        request = db.get_request(request_id)
        if request is None or request.user_id != callback.from_user.id:
            await callback.answer("Запис не знайдено.", show_alert=True)
            return
        if request.status == "cancelled":
            await callback.answer("Запис уже скасовано.", show_alert=True)
            return
        if request.status == "declined":
            await callback.answer("Цю заявку вже відхилено.", show_alert=True)
            return
        if request.status == "expired":
            await callback.answer("Термін цієї заявки вже минув.", show_alert=True)
            return
        db.set_request_status(request.id, "cancelled")
        contact_line = texts.client_contact_block(
            request.user_id, request.user_name, phone=request.phone
        )
        for admin_id in admin_ids:
            try:
                await bot.send_message(
                    admin_id,
                    f"Клієнт скасував запис #{request.id}.\n"
                    f"{html.escape(request.client_name)} · {html.escape(request.service_name)} · "
                    f"{html.escape(format_slot(datetime.fromisoformat(request.slot_start)))}\n"
                    f"{contact_line}",
                    parse_mode="HTML",
                )
            except Exception:
                logger.exception("Failed to notify admin %s about cancellation", admin_id)
        await state.clear()
        await callback.message.edit_text(
            f"Запис #{request.id} скасовано.",
            reply_markup=keyboards.main_menu(is_admin(callback.from_user.id)),
        )
        await callback.answer()

    @router.callback_query(BookingState.confirming, F.data == "booking:restart")
    async def booking_restart(callback: CallbackQuery, state: FSMContext) -> None:
        user_id = callback.from_user.id
        services = db.list_services()
        await state.set_state(BookingState.choosing_service)
        db.upsert_pending_booking(user_id, callback.message.chat.id)
        await callback.message.edit_text(
            texts.ASK_SERVICE,
            reply_markup=keyboards.booking_services_keyboard(services),
        )
        await callback.answer()

    # ── Fallback handlers ─────────────────────────────────────────────────────

    @router.message(F.text.in_(_REPLY_BUTTON_LABELS))
    async def stale_reply_button(message: Message, state: FSMContext) -> None:
        # Натискання адмінських reply-кнопок уже оброблені адмін-роутером (він
        # включений першим). Сюди доходять лише не-адміни із застарілою
        # reply-клавіатурою, що лишилася з попередніх сесій. Повертаємо їх у
        # головне меню та прибираємо зайву клавіатуру, а не пересилаємо майстру.
        user_id = message.from_user.id
        await state.clear()
        db.clear_pending_booking(user_id)
        await message.answer("Повертаю в головне меню 👇", reply_markup=ReplyKeyboardRemove())
        await message.answer(texts.GREETING, reply_markup=keyboards.main_menu(is_admin(user_id)))

    @router.callback_query()
    async def unknown_callback(callback: CallbackQuery, state: FSMContext) -> None:
        await state.clear()
        await callback.answer("Не вдалося виконати дію.", show_alert=True)
        await callback.message.answer(
            "Оберіть дію нижче:",
            reply_markup=keyboards.main_menu(is_admin(callback.from_user.id)),
        )

    @router.message(~F.text)
    async def unsupported_content(message: Message, state: FSMContext) -> None:
        current = await state.get_state()
        if current is not None:
            await message.answer(texts.UNSUPPORTED_MESSAGE)
        else:
            await message.answer(
                texts.UNSUPPORTED_MESSAGE,
                reply_markup=keyboards.main_menu(is_admin(message.from_user.id)),
            )

    @router.message()
    async def unknown_text(message: Message, state: FSMContext, bot: Bot) -> None:
        current = await state.get_state()
        if current is not None:
            await message.answer("Не зовсім зрозумів. Скористайтеся кнопками або почніть заново: /start")
            return

        user = message.from_user
        if is_admin(user.id):
            await message.answer(
                "Не зовсім зрозумів. Оберіть дію кнопкою або відкрийте адмін-меню.",
                reply_markup=keyboards.home_keyboard(True),
            )
            return
        contact_line = texts.client_contact_block(user.id, user.username)
        for admin_id in admin_ids:
            try:
                await bot.send_message(
                    admin_id,
                    f"❓ Питання від клієнта\n\n"
                    f"👤 {html.escape(user.full_name)}\n"
                    f"{contact_line}\n\n"
                    f"💬 {html.escape(message.text or '(порожнє)')}",
                    parse_mode="HTML",
                )
            except Exception:
                logger.exception("Failed to forward message to admin %s", admin_id)

        await message.answer(
            texts.FALLBACK_TEXT,
            reply_markup=keyboards.main_menu(is_admin(user.id)),
        )

    return router


def _extract_phone(message: Message) -> str:
    if message.contact and message.contact.phone_number:
        return message.contact.phone_number.strip()
    return (message.text or "").strip()


def _validate_phone(phone: str) -> bool:
    return bool(_PHONE_RE.match(phone))


def _normalize_phone(phone: str) -> str:
    return re.sub(r"[\s\-\(\)]", "", phone)


def _selected_addons(db: Database, addon_ids) -> list[Addon]:
    if not addon_ids:
        return []
    wanted = {int(item) for item in addon_ids}
    return [addon for addon in db.list_addons(active_only=True) if addon.id in wanted]


def _addons_minutes(addons: list[Addon]) -> int:
    return sum(addon.duration_minutes for addon in addons)


def _addons_price(addons: list[Addon]) -> int:
    return sum(addon.price_uah for addon in addons)


def _dates_back_callback(db: Database) -> str:
    return "addon:reopen" if db.list_addons(active_only=True) else "client:book"


def _addon_extra_label(addon: Addon) -> str:
    label = f"+{addon.price_uah} грн"
    if addon.duration_minutes:
        label += f", +{addon.duration_minutes} хв"
    return label


def _addons_prompt(service: Service, selected: list[Addon]) -> str:
    lines = [
        "Бажаєте додати щось до сеансу? Це необов'язково — можна одразу натиснути «Без додаткових».",
        "",
        f"Основна послуга: {service.name} ({service.duration_minutes} хв) — {texts.format_price(service.price_uah)}",
    ]
    if selected:
        lines.append("")
        lines.append("Обрано додатково:")
        for addon in selected:
            lines.append(f"• {addon.name} ({_addon_extra_label(addon)})")
        total_minutes = service.duration_minutes + _addons_minutes(selected)
        lines.append("")
        lines.append(f"Разом: {total_minutes} хв · {texts.format_total(service.price_uah, _addons_price(selected))}")
    return "\n".join(lines)


def _booking_summary(service: Service, slot_start, addons: list[Addon], name: str, phone: str) -> str:
    total_minutes = service.duration_minutes + _addons_minutes(addons)
    lines = [
        "Перевірте ваш запис:",
        "",
        f"Послуга: {service.name} ({service.duration_minutes} хв) — {texts.format_price(service.price_uah)}",
    ]
    if addons:
        lines.append("Додатково:")
        for addon in addons:
            lines.append(f"• {addon.name} ({_addon_extra_label(addon)})")
    lines += [
        "",
        f"Час: {format_slot(slot_start)}",
        f"Тривалість: {total_minutes} хв",
        f"Разом до сплати: {texts.format_total(service.price_uah, _addons_price(addons))}",
        f"Ім'я: {name}",
        f"Телефон: {phone}",
        "",
        "Все вірно?",
    ]
    return "\n".join(lines)


def _services_text(services: list[Service]) -> str:
    if not services:
        return "Наразі немає доступних послуг."
    lines = ["Ось актуальні послуги та ціни 👇", ""]
    lines.extend(
        f"• {service.name} ({service.duration_minutes} хв) — {texts.format_price(service.price_uah)}"
        for service in services
    )
    return "\n".join(lines)


def _address_text(db: Database, business_address: str, map_url: str) -> str:
    address = db.get_setting("business_address", business_address)
    map_url = db.get_setting("map_url", map_url)
    address = address or "Адресу ще не вказано. Уточніть її у майстра."
    weekdays = ("Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Нд")
    working_days = [hours for hours in db.list_working_hours() if hours.is_working]
    schedule_parts = [
        f"{weekdays[hours.weekday - 1]} {hours.start_time}-{hours.end_time}"
        for hours in working_days
    ]
    schedule_line = f"Графік: {', '.join(schedule_parts)}" if schedule_parts else "Графік уточнюється."
    map_line = f"\n🗺 Маршрут: {map_url}" if map_url else ""
    return f"📍 {address}\n🕘 {schedule_line}{map_line}"


def _user_bookings_text(db: Database, requests) -> str:
    labels = {
        "pending": "очікує підтвердження",
        "confirmed": "підтверджено",
    }
    lines = ["Ваші майбутні записи:", ""]
    for request in requests:
        slot_start = datetime.fromisoformat(request.slot_start)
        addons = db.list_request_addons(request.id)
        block = [
            f"#{request.id} · {format_slot(slot_start)}",
            request.service_name,
        ]
        if addons:
            block.append("Додатково: " + ", ".join(addon.name for addon in addons))
            service = db.get_service(request.service_id)
            base_price = service.price_uah if service else 0
            addon_price = sum(addon.price_uah for addon in addons)
            block.append(f"Разом: {texts.format_total(base_price, addon_price)}")
        block.append(f"Статус: {labels.get(request.status, request.status)}")
        lines.append("\n".join(block))
    return "\n\n".join(lines)
