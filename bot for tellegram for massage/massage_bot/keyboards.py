from __future__ import annotations

from datetime import date, datetime, timedelta

from aiogram.types import InlineKeyboardButton, InlineKeyboardMarkup, KeyboardButton, ReplyKeyboardMarkup

from massage_bot import texts
from massage_bot.database import Addon, BookingRequest, Service, WorkingHours
from massage_bot.schedule import encode_slot, format_date_label, format_slot, format_time


# ── Client keyboards ──────────────────────────────────────────────────────────

def main_menu(is_admin: bool = False) -> InlineKeyboardMarkup:
    buttons = [
        [InlineKeyboardButton(text="Послуги та ціни", callback_data="client:services_prices")],
        [InlineKeyboardButton(text="Записатися", callback_data="client:book")],
        [InlineKeyboardButton(text="Мої записи", callback_data="client:bookings")],
        [InlineKeyboardButton(text="Адреса і графік", callback_data="client:address")],
        [InlineKeyboardButton(text="Інше питання", callback_data="client:faq")],
    ]
    if is_admin:
        buttons.append([InlineKeyboardButton(text="Адмін-меню", callback_data="admin:menu")])
    return InlineKeyboardMarkup(inline_keyboard=buttons)


def services_prices_keyboard() -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="Записатися", callback_data="client:book")],
        [InlineKeyboardButton(text="Меню", callback_data="client:back")],
    ])


def address_keyboard() -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="Записатися", callback_data="client:book")],
        [InlineKeyboardButton(text="Меню", callback_data="client:back")],
    ])


def faq_keyboard() -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="💳 Оплата", callback_data="faq:payment")],
        [InlineKeyboardButton(text="⏱ Тривалість сеансу", callback_data="faq:duration")],
        [InlineKeyboardButton(text="⚠️ Протипоказання", callback_data="faq:contraindications")],
        [InlineKeyboardButton(text="🔁 Перенести запис", callback_data="faq:reschedule")],
        [InlineKeyboardButton(text="Меню", callback_data="client:back")],
    ])


def back_to_menu_keyboard() -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="Меню", callback_data="client:back")],
    ])


def booking_services_keyboard(services: list[Service]) -> InlineKeyboardMarkup:
    buttons = [
        [
            InlineKeyboardButton(
                text=f"{service.name} · {texts.format_price(service.price_uah)}",
                callback_data=f"bservice:{service.id}",
            )
        ]
        for service in services
    ]
    buttons.append([InlineKeyboardButton(text="Меню", callback_data="client:back")])
    return InlineKeyboardMarkup(inline_keyboard=buttons)


def home_keyboard(is_admin: bool = False) -> ReplyKeyboardMarkup:
    keyboard = [[KeyboardButton(text="Головне меню")]]
    if is_admin:
        keyboard = [
            [KeyboardButton(text="Адмін-меню"), KeyboardButton(text="Нові заявки")],
            [KeyboardButton(text="Записи за днями"), KeyboardButton(text="Інструкція")],
            [KeyboardButton(text="Додати запис"), KeyboardButton(text="Послуги")],
            [KeyboardButton(text="Графік роботи")],
            [KeyboardButton(text="Головне меню")],
        ]
    return ReplyKeyboardMarkup(keyboard=keyboard, resize_keyboard=True, is_persistent=True)


def phone_keyboard() -> ReplyKeyboardMarkup:
    return ReplyKeyboardMarkup(
        keyboard=[[KeyboardButton(text="Поділитися телефоном", request_contact=True)]],
        resize_keyboard=True,
        one_time_keyboard=True,
    )


def booking_confirmation_keyboard() -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        inline_keyboard=[
            [InlineKeyboardButton(text="Все вірно ✅", callback_data="booking:confirm")],
            [InlineKeyboardButton(text="Змінити", callback_data="booking:restart")],
        ]
    )


# ── Admin keyboards ───────────────────────────────────────────────────────────

def admin_menu() -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        inline_keyboard=[
            [InlineKeyboardButton(text="Стан на сьогодні", callback_data="admin:dashboard")],
            [InlineKeyboardButton(text="Послуги", callback_data="admin:services")],
            [InlineKeyboardButton(text="Додаткові послуги", callback_data="admin:addons")],
            [InlineKeyboardButton(text="Графік роботи", callback_data="admin:schedule")],
            [InlineKeyboardButton(text="Нові заявки", callback_data="admin:requests")],
            [InlineKeyboardButton(text="Записи за днями", callback_data="admin:bookings")],
            [InlineKeyboardButton(text="Адреса і контакти", callback_data="admin:location")],
            [InlineKeyboardButton(text="Інструкція", callback_data="admin:help")],
            [InlineKeyboardButton(text="Головне меню", callback_data="client:back")],
        ]
    )


def admin_help_menu_keyboard(topics: list[tuple[str, str]]) -> InlineKeyboardMarkup:
    rows = [
        [InlineKeyboardButton(text=title, callback_data=f"admin:help:{key}")]
        for key, title in topics
    ]
    rows.append(
        [
            InlineKeyboardButton(text="Адмін-меню", callback_data="admin:menu"),
            InlineKeyboardButton(text="Головне меню", callback_data="client:back"),
        ]
    )
    return InlineKeyboardMarkup(inline_keyboard=rows)


def admin_help_topic_keyboard() -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        inline_keyboard=[
            [InlineKeyboardButton(text="← До тем", callback_data="admin:help")],
            [InlineKeyboardButton(text="Адмін-меню", callback_data="admin:menu")],
        ]
    )


def admin_booking_days_keyboard(start_day: date, counts: dict[date, int], days: int = 14) -> InlineKeyboardMarkup:
    buttons = []
    for index in range(days):
        day = start_day + timedelta(days=index)
        count = counts.get(day, 0)
        suffix = f" · {count} запис(ів)" if count else " · немає записів"
        buttons.append(
            [
                InlineKeyboardButton(
                    text=f"{format_date_label(day, start_day)}{suffix}",
                    callback_data=f"admin:bookings:{day.strftime('%Y%m%d')}",
                )
            ]
        )
    buttons.append([InlineKeyboardButton(text="Назад", callback_data="admin:menu")])
    return InlineKeyboardMarkup(inline_keyboard=buttons)


def admin_booking_days_reply_keyboard(start_day: date, counts: dict[date, int], days: int = 14) -> ReplyKeyboardMarkup:
    rows = []
    for index in range(days):
        day = start_day + timedelta(days=index)
        count = counts.get(day, 0)
        suffix = f" · {count} запис(ів)" if count else " · немає записів"
        rows.append([KeyboardButton(text=f"{day.strftime('%d.%m')}{suffix}")])
    rows.append([KeyboardButton(text="Адмін-меню"), KeyboardButton(text="Головне меню")])
    return ReplyKeyboardMarkup(keyboard=rows, resize_keyboard=True, is_persistent=True)


def admin_day_bookings_keyboard(
    day: date,
    requests: list[BookingRequest] | None = None,
) -> InlineKeyboardMarkup:
    buttons = [
        [
            InlineKeyboardButton(
                text=f"#{request.id} · {request.client_name} · {datetime.fromisoformat(request.slot_start).strftime('%H:%M')}",
                callback_data=f"admin:req:{request.id}",
            )
        ]
        for request in (requests or [])
    ]
    buttons.append([InlineKeyboardButton(text="До списку днів", callback_data="admin:bookings")])
    buttons.append([InlineKeyboardButton(text="Адмін-меню", callback_data="admin:menu")])
    return InlineKeyboardMarkup(inline_keyboard=buttons)


def admin_day_bookings_reply_keyboard() -> ReplyKeyboardMarkup:
    return ReplyKeyboardMarkup(
        keyboard=[
            [KeyboardButton(text="До списку днів")],
            [KeyboardButton(text="Адмін-меню"), KeyboardButton(text="Головне меню")],
        ],
        resize_keyboard=True,
        is_persistent=True,
    )


def pending_requests_reply_keyboard(requests: list[BookingRequest]) -> ReplyKeyboardMarkup:
    rows = [[KeyboardButton(text=f"Заявка #{request.id} · {request.client_name}")] for request in requests]
    rows.append([KeyboardButton(text="Адмін-меню"), KeyboardButton(text="Головне меню")])
    return ReplyKeyboardMarkup(keyboard=rows, resize_keyboard=True, is_persistent=True)


def request_decision_reply_keyboard() -> ReplyKeyboardMarkup:
    return ReplyKeyboardMarkup(
        keyboard=[
            [KeyboardButton(text="Підтвердити"), KeyboardButton(text="Відхилити")],
            [KeyboardButton(text="Перенести"), KeyboardButton(text="Скасувати")],
            [KeyboardButton(text="Нові заявки"), KeyboardButton(text="Адмін-меню")],
        ],
        resize_keyboard=True,
        is_persistent=True,
    )


def admin_services_keyboard(services: list[Service]) -> InlineKeyboardMarkup:
    buttons = [[InlineKeyboardButton(text="Додати послугу", callback_data="admin:add_service")]]
    for service in services:
        status = "" if service.is_active else " (вимкнено)"
        buttons.append([InlineKeyboardButton(text=f"{service.name}{status}", callback_data=f"admin:service:{service.id}")])
    buttons.append([InlineKeyboardButton(text="Назад", callback_data="admin:menu")])
    return InlineKeyboardMarkup(inline_keyboard=buttons)


_SHORT_WEEKDAYS = ("Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Нд")
DURATION_PRESETS = (15, 20, 25, 30, 40, 45, 50, 60, 75, 90, 120)
ADDON_DURATION_PRESETS = (0, 10, 15, 20, 30, 45, 60)
PRICE_PRESETS = (200, 250, 300, 350, 400, 450, 500, 550, 600, 650, 700, 800, 900, 1000)
ADDON_PRICE_PRESETS = (50, 100, 150, 200, 250, 300, 350, 400, 500, 600)


def _chunk(buttons: list[InlineKeyboardButton], per_row: int) -> list[list[InlineKeyboardButton]]:
    return [buttons[i:i + per_row] for i in range(0, len(buttons), per_row)]


def service_edit_keyboard(service: Service) -> InlineKeyboardMarkup:
    toggle = (
        InlineKeyboardButton(text="Вимкнути", callback_data=f"admin:disable_service:{service.id}")
        if service.is_active
        else InlineKeyboardButton(text="Увімкнути", callback_data=f"admin:enable_service:{service.id}")
    )
    return InlineKeyboardMarkup(
        inline_keyboard=[
            [InlineKeyboardButton(text=f"✏️ Назва: {service.name}", callback_data=f"admin:svc_name:{service.id}")],
            [InlineKeyboardButton(text=f"⏱ Тривалість: {service.duration_minutes} хв", callback_data=f"admin:svc_dur:{service.id}")],
            [InlineKeyboardButton(text=f"💰 Ціна: {texts.format_price(service.price_uah)}", callback_data=f"admin:svc_price:{service.id}")],
            [toggle],
            [InlineKeyboardButton(text="Назад до послуг", callback_data="admin:services")],
        ]
    )


def duration_picker_keyboard(
    set_prefix: str,
    back_callback: str,
    presets: tuple[int, ...] = DURATION_PRESETS,
) -> InlineKeyboardMarkup:
    buttons = [
        InlineKeyboardButton(
            text=("без часу" if minutes == 0 else f"{minutes} хв"),
            callback_data=f"{set_prefix}:{minutes}",
        )
        for minutes in presets
    ]
    rows = _chunk(buttons, 4)
    rows.append([InlineKeyboardButton(text="Назад", callback_data=back_callback)])
    return InlineKeyboardMarkup(inline_keyboard=rows)


def price_picker_keyboard(
    set_prefix: str,
    manual_callback: str,
    back_callback: str,
    presets: tuple[int, ...] = PRICE_PRESETS,
) -> InlineKeyboardMarkup:
    buttons = [
        InlineKeyboardButton(text=f"{price}", callback_data=f"{set_prefix}:{price}")
        for price in presets
    ]
    rows = _chunk(buttons, 4)
    rows.append([InlineKeyboardButton(text="✍️ Інша сума", callback_data=manual_callback)])
    rows.append([InlineKeyboardButton(text="Назад", callback_data=back_callback)])
    return InlineKeyboardMarkup(inline_keyboard=rows)


# ── Add-ons (додаткові послуги) ────────────────────────────────────────────────

def booking_addons_keyboard(addons: list[Addon], selected_ids: set[int]) -> InlineKeyboardMarkup:
    rows: list[list[InlineKeyboardButton]] = []
    for addon in addons:
        mark = "☑️" if addon.id in selected_ids else "☐"
        extra = f"+{addon.price_uah}₴"
        if addon.duration_minutes:
            extra += f" / +{addon.duration_minutes}хв"
        rows.append(
            [InlineKeyboardButton(text=f"{mark} {addon.name} ({extra})", callback_data=f"addon:toggle:{addon.id}")]
        )
    done_text = "Готово ✅" if selected_ids else "Без додаткових ➡️"
    rows.append([InlineKeyboardButton(text=done_text, callback_data="addon:done")])
    rows.append([InlineKeyboardButton(text="Назад", callback_data="client:book")])
    return InlineKeyboardMarkup(inline_keyboard=rows)


def admin_addons_keyboard(addons: list[Addon]) -> InlineKeyboardMarkup:
    buttons = [[InlineKeyboardButton(text="Додати допослугу", callback_data="admin:add_addon")]]
    for addon in addons:
        status = "" if addon.is_active else " (вимкнено)"
        buttons.append([InlineKeyboardButton(text=f"{addon.name}{status}", callback_data=f"admin:addon:{addon.id}")])
    buttons.append([InlineKeyboardButton(text="Назад", callback_data="admin:menu")])
    return InlineKeyboardMarkup(inline_keyboard=buttons)


def addon_edit_keyboard(addon: Addon) -> InlineKeyboardMarkup:
    dur_label = f"{addon.duration_minutes} хв" if addon.duration_minutes else "без часу"
    toggle = (
        InlineKeyboardButton(text="Вимкнути", callback_data=f"admin:disable_addon:{addon.id}")
        if addon.is_active
        else InlineKeyboardButton(text="Увімкнути", callback_data=f"admin:enable_addon:{addon.id}")
    )
    return InlineKeyboardMarkup(
        inline_keyboard=[
            [InlineKeyboardButton(text=f"✏️ Назва: {addon.name}", callback_data=f"admin:adn_name:{addon.id}")],
            [InlineKeyboardButton(text=f"⏱ Тривалість: {dur_label}", callback_data=f"admin:adn_dur:{addon.id}")],
            [InlineKeyboardButton(text=f"💰 Ціна: {addon.price_uah} грн", callback_data=f"admin:adn_price:{addon.id}")],
            [toggle],
            [InlineKeyboardButton(text="Назад до допослуг", callback_data="admin:addons")],
        ]
    )


def request_actions(request_id: int, status: str = "pending") -> InlineKeyboardMarkup:
    rows: list[list[InlineKeyboardButton]] = []
    if status == "pending":
        rows.append(
            [
                InlineKeyboardButton(text="Підтвердити", callback_data=f"admin:confirm:{request_id}"),
                InlineKeyboardButton(text="Відхилити", callback_data=f"admin:decline:{request_id}"),
            ]
        )
        rows.append(
            [
                InlineKeyboardButton(text="Скасувати", callback_data=f"admin:cancel:{request_id}"),
                InlineKeyboardButton(text="Перенести", callback_data=f"admin:reschedule:{request_id}"),
            ]
        )
    elif status == "confirmed":
        rows.append(
            [
                InlineKeyboardButton(text="Скасувати", callback_data=f"admin:cancel:{request_id}"),
                InlineKeyboardButton(text="Перенести", callback_data=f"admin:reschedule:{request_id}"),
            ]
        )
    # Для скасованих/відхилених/прострочених заявок дій немає — лише вихід.
    rows.append([InlineKeyboardButton(text="← Адмін-меню", callback_data="admin:menu")])
    return InlineKeyboardMarkup(inline_keyboard=rows)


def client_confirmed_booking_keyboard(request_id: int) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        inline_keyboard=[
            [InlineKeyboardButton(text="Скасувати запис", callback_data=f"client:cancel_prompt:{request_id}")],
            [InlineKeyboardButton(text="Головне меню", callback_data="client:back")],
        ]
    )


def client_cancel_confirmation_keyboard(request_id: int) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        inline_keyboard=[
            [InlineKeyboardButton(text="Так, скасувати", callback_data=f"client:cancel:{request_id}")],
            [InlineKeyboardButton(text="Ні, залишити запис", callback_data="client:bookings")],
        ]
    )


def client_bookings_keyboard(requests: list[BookingRequest]) -> InlineKeyboardMarkup:
    buttons = []
    for request in requests:
        slot_start = datetime.fromisoformat(request.slot_start)
        buttons.append(
            [
                InlineKeyboardButton(
                    text=f"Скасувати #{request.id} · {slot_start.strftime('%d.%m %H:%M')}",
                    callback_data=f"client:cancel_prompt:{request.id}",
                )
            ]
        )
    buttons.append([InlineKeyboardButton(text="Записатися ще", callback_data="client:book")])
    buttons.append([InlineKeyboardButton(text="Меню", callback_data="client:back")])
    return InlineKeyboardMarkup(inline_keyboard=buttons)


def pending_requests_keyboard(requests: list[BookingRequest]) -> InlineKeyboardMarkup:
    buttons = [
        [InlineKeyboardButton(text=f"Заявка #{request.id} · {request.client_name}", callback_data=f"admin:req:{request.id}")]
        for request in requests
    ]
    buttons.append([InlineKeyboardButton(text="Назад", callback_data="admin:menu")])
    return InlineKeyboardMarkup(inline_keyboard=buttons)


def schedule_days_keyboard(hours_list: list[WorkingHours]) -> InlineKeyboardMarkup:
    by_weekday = {hours.weekday: hours for hours in hours_list}
    rows: list[list[InlineKeyboardButton]] = []
    for weekday in range(1, 8):
        hours = by_weekday.get(weekday)
        label = _SHORT_WEEKDAYS[weekday - 1]
        if hours is None:
            state = "не задано"
        elif hours.is_working:
            state = f"{hours.start_time}–{hours.end_time}"
        else:
            state = "вихідний"
        rows.append(
            [InlineKeyboardButton(text=f"{label}: {state}", callback_data=f"admin:hday:{weekday}")]
        )
    rows.append(
        [
            InlineKeyboardButton(text="Закрити дату", callback_data="admin:close_day"),
            InlineKeyboardButton(text="Відкрити дату", callback_data="admin:open_day"),
        ]
    )
    rows.append([InlineKeyboardButton(text="Назад", callback_data="admin:menu")])
    return InlineKeyboardMarkup(inline_keyboard=rows)


def hours_day_keyboard(weekday: int, hours: WorkingHours | None) -> InlineKeyboardMarkup:
    start = hours.start_time if hours else "10:00"
    end = hours.end_time if hours else "19:00"
    is_working = hours.is_working if hours else True
    toggle_text = "Зробити вихідним" if is_working else "Зробити робочим"
    return InlineKeyboardMarkup(
        inline_keyboard=[
            [InlineKeyboardButton(text=f"🕐 Початок: {start}", callback_data=f"admin:htime:{weekday}:s")],
            [InlineKeyboardButton(text=f"🕐 Кінець: {end}", callback_data=f"admin:htime:{weekday}:e")],
            [InlineKeyboardButton(text=toggle_text, callback_data=f"admin:htoggle:{weekday}")],
            [InlineKeyboardButton(text="← До графіка", callback_data="admin:schedule")],
        ]
    )


def time_grid_keyboard(weekday: int, which: str) -> InlineKeyboardMarkup:
    buttons: list[InlineKeyboardButton] = []
    minutes = 7 * 60  # 07:00
    end = 22 * 60     # 22:00 включно
    while minutes <= end:
        label = f"{minutes // 60:02d}:{minutes % 60:02d}"
        buttons.append(
            InlineKeyboardButton(text=label, callback_data=f"admin:hpick:{weekday}:{which}:{minutes:04d}")
        )
        minutes += 30
    rows = _chunk(buttons, 4)
    rows.append([InlineKeyboardButton(text="Назад", callback_data=f"admin:hday:{weekday}")])
    return InlineKeyboardMarkup(inline_keyboard=rows)


# ── Kept for admin manual booking flow ───────────────────────────────────────

def services_keyboard(services: list[Service], prefix: str = "service") -> InlineKeyboardMarkup:
    buttons = [
        [
            InlineKeyboardButton(
                text=f"{service.name} · {service.duration_minutes} хв · {service.price_uah} грн",
                callback_data=f"{prefix}:{service.id}",
            )
        ]
        for service in services
    ]
    buttons.append([InlineKeyboardButton(text="Назад", callback_data="client:back")])
    return InlineKeyboardMarkup(inline_keyboard=buttons)


def dates_keyboard(
    service_id: int,
    slots: list[datetime],
    back_callback: str | None = None,
) -> InlineKeyboardMarkup:
    reference_day = datetime.now(slots[0].tzinfo).date() if slots else date.today()
    unique_days: list[date] = []
    for slot in slots:
        day = slot.date()
        if day not in unique_days:
            unique_days.append(day)

    buttons = [
        [
            InlineKeyboardButton(
                text=format_date_label(day, reference_day),
                callback_data=f"date:{service_id}:{day.strftime('%Y%m%d')}",
            )
        ]
        for day in unique_days[:10]
    ]
    if back_callback:
        buttons.append([InlineKeyboardButton(text="Назад", callback_data=back_callback)])
    return InlineKeyboardMarkup(inline_keyboard=buttons)


def day_slots_keyboard(
    service_id: int,
    slots: list[datetime],
    back_callback: str | None = None,
) -> InlineKeyboardMarkup:
    slot_buttons = [
        InlineKeyboardButton(
            text=format_time(slot),
            callback_data=f"slot:{service_id}:{encode_slot(slot)}",
        )
        for slot in slots
    ]
    buttons = [slot_buttons[index:index + 3] for index in range(0, len(slot_buttons), 3)]
    if back_callback:
        buttons.append([InlineKeyboardButton(text="Назад", callback_data=back_callback)])
    return InlineKeyboardMarkup(inline_keyboard=buttons)


def slots_keyboard(service_id: int, slots: list[datetime]) -> InlineKeyboardMarkup:
    buttons = [
        [
            InlineKeyboardButton(
                text=format_slot(slot),
                callback_data=f"slot:{service_id}:{encode_slot(slot)}",
            )
        ]
        for slot in slots[:20]
    ]
    buttons.append([InlineKeyboardButton(text="Назад до послуг", callback_data="client:services")])
    return InlineKeyboardMarkup(inline_keyboard=buttons)
