from massage_bot.handlers_client import _address_text, _normalize_phone, _services_text, _validate_phone
from datetime import datetime, timedelta
from zoneinfo import ZoneInfo

from massage_bot.keyboards import (
    booking_services_keyboard,
    client_confirmed_booking_keyboard,
    day_slots_keyboard,
)
from massage_bot.database import Database


def test_services_text_and_keyboard_use_database_values(tmp_path) -> None:
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_id=123)
    service = db.list_services()[0]
    db.update_service(service.id, "Тестовий масаж", 75, 1234)

    text = _services_text(db.list_services())
    keyboard = booking_services_keyboard(db.list_services())
    buttons = [button for row in keyboard.inline_keyboard for button in row]

    assert "Тестовий масаж (75 хв) — 1234 грн" in text
    assert any(button.text == "Тестовий масаж · 1234 грн" for button in buttons)
    assert any(button.callback_data == f"bservice:{service.id}" for button in buttons)


def test_address_has_no_template_placeholders(tmp_path) -> None:
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_id=123)

    text = _address_text(db, "", "")

    assert "Адресу ще не вказано" in text
    assert "{" not in text
    assert "}" not in text


def test_phone_is_normalized_before_validation() -> None:
    phone = _normalize_phone("+380 (67) 123-45-67")

    assert phone == "+380671234567"
    assert _validate_phone(phone)


def test_all_day_slots_are_visible_in_compact_rows() -> None:
    timezone = ZoneInfo("Europe/Kyiv")
    start = datetime(2026, 6, 15, 10, 0, tzinfo=timezone)
    slots = [start + timedelta(minutes=30 * index) for index in range(17)]

    keyboard = day_slots_keyboard(1, slots)
    slot_buttons = [
        button
        for row in keyboard.inline_keyboard
        for button in row
        if button.callback_data and button.callback_data.startswith("slot:")
    ]

    assert len(slot_buttons) == 17
    assert all(len(row) <= 3 for row in keyboard.inline_keyboard)


def test_cancel_requires_confirmation() -> None:
    keyboard = client_confirmed_booking_keyboard(42)

    assert keyboard.inline_keyboard[0][0].callback_data == "client:cancel_prompt:42"
