from datetime import date, datetime, time, timedelta
from zoneinfo import ZoneInfo

from massage_bot import keyboards
from massage_bot.database import Database
from massage_bot.schedule import slot_is_available

TZ = ZoneInfo("Europe/Kyiv")


def _db(tmp_path) -> Database:
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_ids=(1,))
    return db


def _next_weekday(target_iso: int = 1) -> date:
    day = date.today() + timedelta(days=1)
    while day.isoweekday() != target_iso:
        day += timedelta(days=1)
    return day


# ── Каталог допослуг (CRUD) ───────────────────────────────────────────────────

def test_default_addons_are_seeded(tmp_path) -> None:
    db = _db(tmp_path)
    addons = db.list_addons(active_only=True)
    assert addons
    # Серед стартових має бути допослуга без впливу на час (0 хв).
    assert any(addon.duration_minutes == 0 for addon in addons)


def test_addon_crud(tmp_path) -> None:
    db = _db(tmp_path)
    db.add_addon("Тест", 10, 99)
    created = next(addon for addon in db.list_addons() if addon.name == "Тест")

    db.set_addon_name(created.id, "Оновлено")
    db.set_addon_duration(created.id, 0)
    db.set_addon_price(created.id, 5)
    db.deactivate_addon(created.id)

    got = db.get_addon(created.id)
    assert got.name == "Оновлено"
    assert got.duration_minutes == 0
    assert got.price_uah == 5
    assert got.is_active is False
    assert created.id not in {a.id for a in db.list_addons(active_only=True)}


# ── Збереження допослуг у заявці (зі знімком) ─────────────────────────────────

def test_request_stores_addon_snapshot(tmp_path) -> None:
    db = _db(tmp_path)
    service = db.list_services()[0]
    addon = db.list_addons()[0]
    slot = datetime(2030, 6, 3, 12, 0, tzinfo=TZ)

    request_id = db.create_request(
        user_id=5, user_name="u", client_name="Олена", phone="+380000000000",
        service_id=service.id, slot_start=slot, slot_end=slot + timedelta(minutes=90),
        status="pending", addons=[addon],
    )
    lines = db.list_request_addons(request_id)
    assert len(lines) == 1
    assert lines[0].name == addon.name
    assert lines[0].price_uah == addon.price_uah

    # Зміна каталогу не повинна змінювати вже збережену заявку.
    db.set_addon_price(addon.id, addon.price_uah + 999)
    assert db.list_request_addons(request_id)[0].price_uah == addon.price_uah


# ── Допослуги впливають на тривалість і конфлікти ─────────────────────────────

def test_addon_duration_shrinks_available_window(tmp_path) -> None:
    db = _db(tmp_path)
    service = db.list_services()[0]
    db.set_service_duration(service.id, 60)
    service = db.get_service(service.id)

    monday = _next_weekday(1)  # робочий день 10:00–19:00
    slot = datetime.combine(monday, time(18, 0), tzinfo=TZ)

    # 60 хв закінчується о 19:00 — ще влізає.
    assert slot_is_available(db, service, slot, TZ)
    # +30 хв допослуги → 19:30, виходить за межі робочого дня.
    assert not slot_is_available(db, service, slot, TZ, extra_minutes=30)


def test_addon_duration_extends_conflict(tmp_path) -> None:
    db = _db(tmp_path)
    service = db.list_services()[0]
    db.set_service_duration(service.id, 60)
    service = db.get_service(service.id)

    monday = _next_weekday(1)
    busy_start = datetime.combine(monday, time(17, 30), tzinfo=TZ)
    db.create_request(
        user_id=9, user_name="b", client_name="Зайнято", phone="+380000000001",
        service_id=service.id, slot_start=busy_start, slot_end=busy_start + timedelta(minutes=60),
        status="confirmed",
    )

    slot = datetime.combine(monday, time(16, 0), tzinfo=TZ)
    # Базові 60 хв (16:00–17:00) не перетинаються із зайнятим 17:30.
    assert slot_is_available(db, service, slot, TZ)
    # +60 хв допослуги (16:00–18:00) перетинаються із 17:30 — недоступно.
    assert not slot_is_available(db, service, slot, TZ, extra_minutes=60)


# ── Клавіатури ────────────────────────────────────────────────────────────────

def test_booking_addons_keyboard_marks_selected(tmp_path) -> None:
    db = _db(tmp_path)
    addons = db.list_addons(active_only=True)
    selected = {addons[0].id}
    markup = keyboards.booking_addons_keyboard(addons, selected)
    flat = [(button.text, button.callback_data) for row in markup.inline_keyboard for button in row]
    callbacks = [cb for _, cb in flat]

    assert f"addon:toggle:{addons[0].id}" in callbacks
    assert "addon:done" in callbacks
    # Обрана допослуга позначена галочкою.
    selected_text = next(text for text, cb in flat if cb == f"addon:toggle:{addons[0].id}")
    assert "☑️" in selected_text


def test_admin_menu_exposes_addons_section() -> None:
    callbacks = [
        button.callback_data
        for row in keyboards.admin_menu().inline_keyboard
        for button in row
    ]
    assert "admin:addons" in callbacks
