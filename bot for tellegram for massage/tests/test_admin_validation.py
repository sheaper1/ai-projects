from datetime import datetime, timedelta
from zoneinfo import ZoneInfo

from massage_bot.handlers_admin import (
    _parse_hours_payload,
    _parse_manual_booking,
    _parse_service_payload,
)


def test_service_payload_has_sensible_limits() -> None:
    assert _parse_service_payload("Релакс; 60; 900") == ("Релакс", 60, 900)
    assert _parse_service_payload("Надто коротка; 5; 900") is None
    assert _parse_service_payload("Надто довга; 600; 900") is None


def test_working_hours_require_end_after_start() -> None:
    assert _parse_hours_payload("1; 10:00; 19:00; працюю") == (1, "10:00", "19:00", True)
    assert _parse_hours_payload("1; 19:00; 10:00; працюю") is None
    assert _parse_hours_payload("1; 10:00; 10:00; працюю") is None


def test_manual_booking_validates_phone_and_future_time() -> None:
    timezone = ZoneInfo("Europe/Kyiv")
    future = datetime.now(timezone) + timedelta(days=10)
    value = f"Олена; +380 (67) 123-45-67; 1; {future.strftime('%d.%m')}; 14:00"

    parsed = _parse_manual_booking(value, timezone)

    assert parsed is not None
    assert parsed[1] == "+380671234567"
    assert _parse_manual_booking(
        f"Олена; 123; 1; {future.strftime('%d.%m')}; 14:00",
        timezone,
    ) is None
