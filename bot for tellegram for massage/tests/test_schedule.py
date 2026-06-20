from datetime import datetime
from zoneinfo import ZoneInfo

from massage_bot.database import Database
from massage_bot.schedule import decode_slot, encode_slot, generate_slots, slot_is_available


def test_slot_encoding_roundtrip() -> None:
    timezone = ZoneInfo("Europe/Kyiv")
    slot = datetime(2026, 6, 1, 10, 30, tzinfo=timezone)

    assert decode_slot(encode_slot(slot), timezone) == slot


def test_confirmed_slot_is_not_available(tmp_path) -> None:
    timezone = ZoneInfo("Europe/Kyiv")
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_id=123)
    service = db.list_services()[0]
    slot = datetime(2026, 6, 1, 10, 0, tzinfo=timezone)
    db.create_request(
        user_id=1,
        user_name="client",
        client_name="Олена",
        phone="+380000000000",
        service_id=service.id,
        slot_start=slot,
        slot_end=slot.replace(hour=11),
    )
    request = db.list_pending_requests()[0]
    db.set_request_status(request.id, "confirmed")

    assert not slot_is_available(db, service, slot, timezone)


def test_pending_slot_is_reserved(tmp_path) -> None:
    timezone = ZoneInfo("Europe/Kyiv")
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_id=123)
    service = db.list_services()[0]
    slot = datetime(2026, 6, 1, 10, 0, tzinfo=timezone)
    request_id = db.create_request_if_available(
        user_id=1,
        user_name="client",
        client_name="Олена",
        phone="+380000000000",
        service_id=service.id,
        slot_start=slot,
        slot_end=slot.replace(hour=11),
    )

    duplicate_id = db.create_request_if_available(
        user_id=2,
        user_name="client2",
        client_name="Ірина",
        phone="+380000000001",
        service_id=service.id,
        slot_start=slot,
        slot_end=slot.replace(hour=11),
    )

    assert request_id is not None
    assert duplicate_id is None
    assert not slot_is_available(db, service, slot, timezone)


def test_generates_working_day_slots(tmp_path) -> None:
    timezone = ZoneInfo("Europe/Kyiv")
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_id=123)
    service = db.list_services()[0]

    slots = generate_slots(db, service, timezone, days_ahead=14)

    assert all(slot.tzinfo == timezone for slot in slots)


def test_day_off_has_no_slots(tmp_path) -> None:
    timezone = ZoneInfo("Europe/Kyiv")
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_id=123)
    service = db.list_services()[0]
    slots = generate_slots(db, service, timezone, days_ahead=14)
    assert slots
    closed_day = slots[0].date()
    db.set_day_off(closed_day)

    updated_slots = generate_slots(db, service, timezone, days_ahead=14)

    assert all(slot.date() != closed_day for slot in updated_slots)
