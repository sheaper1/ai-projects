import asyncio
import types
from datetime import datetime
from zoneinfo import ZoneInfo

from aiogram import F

from massage_bot import keyboards, texts
from massage_bot.database import Database
from massage_bot.handlers_admin import build_admin_router
from massage_bot.handlers_client import _REPLY_BUTTON_LABELS, build_client_router
from massage_bot.schedule import slot_is_available


def _db(tmp_path) -> Database:
    db = Database(str(tmp_path / "bot.sqlite3"))
    db.initialize(admin_ids=(1, 2))
    return db


# ── Bug 1: перенос на час, що перетинається з власним слотом заявки ──────────

def test_busy_slots_can_exclude_a_request(tmp_path) -> None:
    timezone = ZoneInfo("Europe/Kyiv")
    db = _db(tmp_path)
    service = db.list_services()[0]
    slot = datetime(2026, 6, 1, 10, 0, tzinfo=timezone)
    request_id = db.create_request(
        user_id=10,
        user_name="client",
        client_name="Олена",
        phone="+380000000000",
        service_id=service.id,
        slot_start=slot,
        slot_end=slot.replace(hour=11),
        status="confirmed",
    )
    window_from = slot.replace(hour=0)
    window_to = slot.replace(hour=23)

    assert len(db.list_busy_slots(window_from, window_to)) == 1
    assert db.list_busy_slots(window_from, window_to, exclude_request_id=request_id) == []


def test_reschedule_near_own_slot_is_available_when_excluded(tmp_path) -> None:
    timezone = ZoneInfo("Europe/Kyiv")
    db = _db(tmp_path)
    service = db.list_services()[0]  # 60 хв
    slot = datetime(2026, 6, 1, 10, 0, tzinfo=timezone)
    request_id = db.create_request(
        user_id=10,
        user_name="client",
        client_name="Олена",
        phone="+380000000000",
        service_id=service.id,
        slot_start=slot,
        slot_end=slot.replace(hour=11),
        status="confirmed",
    )
    # Зсув на 30 хв перетинається з власним поточним слотом заявки.
    new_slot = slot.replace(minute=30)

    # Без виключення — час "зайнятий" самою ж заявкою (стара поведінка, баг).
    assert not slot_is_available(db, service, new_slot, timezone)
    # З виключенням поточної заявки — перенос можливий (фікс).
    assert slot_is_available(db, service, new_slot, timezone, exclude_request_id=request_id)


def test_excluding_request_does_not_free_a_different_booking(tmp_path) -> None:
    timezone = ZoneInfo("Europe/Kyiv")
    db = _db(tmp_path)
    service = db.list_services()[0]
    slot = datetime(2026, 6, 1, 10, 0, tzinfo=timezone)
    mine = db.create_request(
        user_id=10, user_name="a", client_name="Олена", phone="+380000000000",
        service_id=service.id, slot_start=slot, slot_end=slot.replace(hour=11),
        status="confirmed",
    )
    other_slot = datetime(2026, 6, 1, 14, 0, tzinfo=timezone)
    db.create_request(
        user_id=11, user_name="b", client_name="Ірина", phone="+380000000001",
        service_id=service.id, slot_start=other_slot, slot_end=other_slot.replace(hour=15),
        status="confirmed",
    )
    # Виключаємо свою заявку, але чужий слот о 14:00 має лишатися зайнятим.
    assert not slot_is_available(db, service, other_slot, timezone, exclude_request_id=mine)


# ── Bug 3: адмін-роутер не перехоплює повідомлення звичайних клієнтів ─────────

def _message_passes_admin_router(router, user_id: int) -> bool:
    fake = types.SimpleNamespace(from_user=types.SimpleNamespace(id=user_id))
    passed, _ = asyncio.run(router.message.check_root_filters(fake))
    return bool(passed)


def test_admin_router_message_filter_allows_only_admins(tmp_path) -> None:
    db = _db(tmp_path)
    router = build_admin_router(db, admin_ids=(1, 2), timezone=ZoneInfo("Europe/Kyiv"))

    assert _message_passes_admin_router(router, 1) is True
    assert _message_passes_admin_router(router, 2) is True
    # Звичайний клієнт не повинен потрапляти в адмін-роутер —
    # його повідомлення піде далі, у клієнтський роутер (пересилання майстру).
    assert _message_passes_admin_router(router, 999) is False


# ── Застаріла reply-клавіатура у не-адміна не йде майстру, а веде в меню ──────

def test_home_button_is_handled_before_masseur_forward(tmp_path) -> None:
    db = _db(tmp_path)
    router = build_client_router(db, admin_ids=(1, 2), timezone=ZoneInfo("Europe/Kyiv"))
    names = [handler.callback.__name__ for handler in router.message.handlers]

    # «Головне меню» має ловитися спеціальним обробником, а не пересиланням майстру.
    assert "stale_reply_button" in names
    assert names.index("stale_reply_button") < names.index("unknown_text")
    assert names.index("stale_reply_button") < names.index("unsupported_content")


def test_reply_button_filter_matches_buttons_not_questions() -> None:
    matcher = F.text.in_(_REPLY_BUTTON_LABELS)
    assert matcher.resolve(types.SimpleNamespace(text="Головне меню")) is True
    assert matcher.resolve(types.SimpleNamespace(text="Адмін-меню")) is True
    assert matcher.resolve(types.SimpleNamespace(text="коли ви працюєте?")) is False


def test_all_persistent_admin_buttons_are_intercepted() -> None:
    # Будь-яка статична кнопка адмінської reply-клавіатури має бути у наборі
    # перехоплення — інакше застаріла клавіатура знову «протече» майстру.
    button_texts = {
        button.text
        for row in keyboards.home_keyboard(True).keyboard
        for button in row
    }
    assert button_texts <= _REPLY_BUTTON_LABELS


# ── Клієнт у повідомленнях майстру завжди опізнаваний і контактний ───────────

def test_contact_block_uses_username_when_present() -> None:
    block = texts.client_contact_block(555, "olena")
    assert "@olena" in block


def test_contact_block_falls_back_to_tappable_deep_link() -> None:
    block = texts.client_contact_block(555, None)
    # Без username має бути клікабельне посилання, що відкриває чат із клієнтом.
    assert 'href="tg://user?id=555"' in block


def test_contact_block_includes_phone_and_escapes_html() -> None:
    block = texts.client_contact_block(555, "<b>hax</b>", phone="+380501112233")
    assert "+380501112233" in block
    # Динаміка має бути екранована, щоб не ламати HTML-розмітку повідомлення.
    assert "<b>hax</b>" not in block
    assert "&lt;b&gt;hax&lt;/b&gt;" in block
