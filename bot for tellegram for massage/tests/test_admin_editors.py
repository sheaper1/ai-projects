from massage_bot import keyboards, texts
from massage_bot.database import Database, WorkingHours
from massage_bot.handlers_admin import (
    _ADMIN_HELP_TOPICS,
    _admin_help_topics_list,
    _hours_day_text,
    _parse_price,
    _valid_service_name,
)


def _db(tmp_path) -> Database:
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_ids=(1,))
    return db


def _callbacks(markup) -> list[str]:
    return [button.callback_data for row in markup.inline_keyboard for button in row]


# ── DB: гранулярні зміни послуги не чіпають is_active ────────────────────────

def test_granular_service_updates_keep_active_flag(tmp_path) -> None:
    db = _db(tmp_path)
    service = db.list_services()[0]
    db.deactivate_service(service.id)

    db.set_service_name(service.id, "Нова назва")
    db.set_service_duration(service.id, 45)
    db.set_service_price(service.id, 777)

    updated = db.get_service(service.id)
    assert updated.name == "Нова назва"
    assert updated.duration_minutes == 45
    assert updated.price_uah == 777
    # Редагування полів не повинно повертати вимкнену послугу клієнтам.
    assert updated.is_active is False


# ── Хелпери валідації ────────────────────────────────────────────────────────

def test_valid_service_name() -> None:
    assert _valid_service_name("Масаж")
    assert not _valid_service_name("")
    assert not _valid_service_name("x" * 81)


def test_parse_price() -> None:
    assert _parse_price("750") == 750
    assert _parse_price("0") == 0
    assert _parse_price("  900 ") == 900
    assert _parse_price("abc") is None
    assert _parse_price("-5") is None
    assert _parse_price("100001") is None


def test_hours_day_text_handles_states() -> None:
    working = WorkingHours(weekday=1, start_time="10:00", end_time="19:00", is_working=True)
    off = WorkingHours(weekday=7, start_time="10:00", end_time="16:00", is_working=False)
    assert "10:00" in _hours_day_text(1, working)
    assert "Вихідний" in _hours_day_text(7, off)
    assert _hours_day_text(2, None)  # не падає, коли графік ще не задано


# ── Клавіатури редактора ──────────────────────────────────────────────────────

def test_service_edit_keyboard_has_field_buttons(tmp_path) -> None:
    db = _db(tmp_path)
    service = db.list_services()[0]
    callbacks = _callbacks(keyboards.service_edit_keyboard(service))
    assert f"admin:svc_name:{service.id}" in callbacks
    assert f"admin:svc_dur:{service.id}" in callbacks
    assert f"admin:svc_price:{service.id}" in callbacks
    assert "admin:services" in callbacks  # назад до списку


def test_duration_and_price_pickers_build_expected_callbacks() -> None:
    dur = _callbacks(keyboards.duration_picker_keyboard("admin:add_dur", "admin:services"))
    assert "admin:add_dur:60" in dur
    assert "admin:services" in dur

    price = _callbacks(
        keyboards.price_picker_keyboard(
            "admin:svc_price_set:5", "admin:svc_price_manual:5", "admin:service:5"
        )
    )
    assert "admin:svc_price_set:5:650" in price
    assert "admin:svc_price_manual:5" in price
    assert "admin:service:5" in price


def test_schedule_days_keyboard_lists_all_days() -> None:
    hours = [
        WorkingHours(weekday=d, start_time="10:00", end_time="19:00", is_working=(d != 7))
        for d in range(1, 8)
    ]
    callbacks = _callbacks(keyboards.schedule_days_keyboard(hours))
    for d in range(1, 8):
        assert f"admin:hday:{d}" in callbacks
    assert "admin:close_day" in callbacks
    assert "admin:open_day" in callbacks


def test_hours_day_keyboard_buttons() -> None:
    hours = WorkingHours(weekday=1, start_time="10:00", end_time="19:00", is_working=True)
    callbacks = _callbacks(keyboards.hours_day_keyboard(1, hours))
    assert "admin:htime:1:s" in callbacks
    assert "admin:htime:1:e" in callbacks
    assert "admin:htoggle:1" in callbacks
    assert "admin:schedule" in callbacks


def test_price_zero_renders_as_negotiable() -> None:
    assert texts.format_price(0) == "за домовленістю"
    assert texts.format_price(800) == "800 грн"
    assert texts.format_total(0, 300) == "за домовленістю"  # базова «за домовленістю» → підсумок теж
    assert texts.format_total(800, 300) == "1100 грн"


def test_service_can_be_pinned_first_via_sort_order(tmp_path) -> None:
    db = _db(tmp_path)
    db.add_service("Яяя остання за алфавітом", 60, 500)
    db.add_service("Закріплена опція", 60, 0, sort_order=-100)
    names = [service.name for service in db.list_services(active_only=True)]
    assert names[0] == "Закріплена опція"


def test_admin_help_is_a_navigable_topic_menu() -> None:
    topics = _admin_help_topics_list()
    assert topics, "має бути хоча б одна тема"
    menu = _callbacks(keyboards.admin_help_menu_keyboard(topics))
    # Кожна тема має кнопку, і є вихід в меню.
    for key, _title in topics:
        assert f"admin:help:{key}" in menu
    assert "admin:menu" in menu

    # На сторінці теми є повернення до списку тем.
    back = _callbacks(keyboards.admin_help_topic_keyboard())
    assert "admin:help" in back
    assert "admin:menu" in back

    # Усі теми мають непорожній заголовок і текст.
    for _key, (title, body) in _ADMIN_HELP_TOPICS.items():
        assert title and body


def test_request_actions_always_has_back_and_hides_stale_actions() -> None:
    pending = _callbacks(keyboards.request_actions(7, "pending"))
    assert "admin:confirm:7" in pending and "admin:menu" in pending

    confirmed = _callbacks(keyboards.request_actions(7, "confirmed"))
    assert "admin:confirm:7" not in confirmed  # підтверджувати вже нічого
    assert "admin:cancel:7" in confirmed and "admin:menu" in confirmed

    cancelled = _callbacks(keyboards.request_actions(7, "cancelled"))
    # Жодних дій — лише вихід у меню, щоб не застрягнути.
    assert cancelled == ["admin:menu"]


def test_time_grid_callbacks_decode_to_valid_times() -> None:
    callbacks = _callbacks(keyboards.time_grid_keyboard(3, "s"))
    pick_callbacks = [c for c in callbacks if c.startswith("admin:hpick:")]
    assert pick_callbacks, "має бути сітка часу"
    for cb in pick_callbacks:
        _, _, weekday, which, minutes_raw = cb.split(":")
        assert weekday == "3" and which == "s"
        total = int(minutes_raw)
        assert 0 <= total % 60 < 60
        assert 7 * 60 <= total <= 22 * 60
