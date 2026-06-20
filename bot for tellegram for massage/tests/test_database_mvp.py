from datetime import datetime, timedelta
from zoneinfo import ZoneInfo

from massage_bot.database import Database


def _create_request(db: Database, user_id: int, status: str = "pending") -> int:
    timezone = ZoneInfo("Europe/Kyiv")
    service = db.list_services()[0]
    slot = datetime.now(timezone) + timedelta(hours=12)
    return db.create_request(
        user_id=user_id,
        user_name="client",
        client_name="Олена",
        phone="+380000000000",
        service_id=service.id,
        slot_start=slot,
        slot_end=slot + timedelta(minutes=service.duration_minutes),
        status=status,
    )


def test_user_can_list_only_own_active_requests(tmp_path) -> None:
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_id=123)
    own_id = _create_request(db, user_id=1)
    _create_request(db, user_id=2)

    requests = db.list_user_requests(user_id=1, active_only=True)

    assert [request.id for request in requests] == [own_id]


def test_due_reminder_is_returned_once(tmp_path) -> None:
    timezone = ZoneInfo("Europe/Kyiv")
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_id=123)
    request_id = _create_request(db, user_id=1, status="confirmed")
    now = datetime.now(timezone)

    due = db.list_due_reminders(now, now + timedelta(hours=24))
    db.mark_reminder_sent(request_id)
    due_after_mark = db.list_due_reminders(now, now + timedelta(hours=24))

    assert [request.id for request in due] == [request_id]
    assert due_after_mark == []


def test_business_settings_persist(tmp_path) -> None:
    path = str(tmp_path / "bot.sqlite3")
    db = Database(path)
    db.initialize(admin_id=123)
    db.set_setting("business_address", "Київ, тестова адреса")

    reopened = Database(path)

    assert reopened.get_setting("business_address") == "Київ, тестова адреса"


def test_old_pending_request_expires_and_releases_slot(tmp_path) -> None:
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_id=123)
    request_id = _create_request(db, user_id=1)
    with db.connect() as conn:
        conn.execute(
            "UPDATE booking_requests SET created_at = datetime('now', '-3 hours') WHERE id = ?",
            (request_id,),
        )

    expired = db.expire_pending_requests(120)

    assert expired == [request_id]
    assert db.get_request(request_id).status == "expired"


def test_day_off_can_be_closed_and_reopened(tmp_path) -> None:
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_id=123)
    selected_day = datetime.now().date() + timedelta(days=5)

    db.set_day_off(selected_day)
    assert db.is_day_off(selected_day)
    assert selected_day in db.list_days_off(datetime.now().date())

    db.remove_day_off(selected_day)
    assert not db.is_day_off(selected_day)


def test_dashboard_counts(tmp_path) -> None:
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_id=123)
    _create_request(db, user_id=1)

    counts = db.get_dashboard_counts(datetime.now().date())

    assert counts["pending"] == 1
    assert counts["active_services"] > 0
