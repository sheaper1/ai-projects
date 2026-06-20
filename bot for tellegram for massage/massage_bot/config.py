from __future__ import annotations

import os
from dataclasses import dataclass
from zoneinfo import ZoneInfo

from dotenv import load_dotenv


@dataclass(frozen=True)
class Config:
    bot_token: str
    admin_ids: tuple[int, ...]
    timezone: ZoneInfo
    database_path: str
    business_address: str
    map_url: str
    pending_request_ttl_minutes: int


def load_config() -> Config:
    load_dotenv(encoding="utf-8-sig")

    bot_token = os.getenv("BOT_TOKEN", "").strip()
    admin_ids_raw = os.getenv("ADMIN_IDS", "").strip()
    admin_id_raw = os.getenv("ADMIN_ID", os.getenv("ADMIN_CHAT_ID", "")).strip()
    timezone_name = os.getenv("TIMEZONE", "Europe/Kyiv").strip()
    database_path = os.getenv("DATABASE_PATH", "massage_bot.sqlite3").strip()
    business_address = os.getenv("BUSINESS_ADDRESS", "").strip()
    map_url = os.getenv("MAP_URL", "").strip()
    pending_request_ttl_raw = os.getenv("PENDING_REQUEST_TTL_MINUTES", "120").strip()

    if not bot_token:
        raise RuntimeError("BOT_TOKEN не заданий у .env")
    admin_ids = _parse_admin_ids(admin_ids_raw or admin_id_raw)
    if not admin_ids:
        raise RuntimeError("ADMIN_IDS або ADMIN_ID має містити хоча б один числовий Telegram ID")
    if not pending_request_ttl_raw.isdigit() or int(pending_request_ttl_raw) < 15:
        raise RuntimeError("PENDING_REQUEST_TTL_MINUTES має бути числом не менше 15")

    return Config(
        bot_token=bot_token,
        admin_ids=admin_ids,
        timezone=ZoneInfo(timezone_name),
        database_path=database_path,
        business_address=business_address,
        map_url=map_url,
        pending_request_ttl_minutes=int(pending_request_ttl_raw),
    )


def _parse_admin_ids(value: str) -> tuple[int, ...]:
    ids: list[int] = []
    for part in value.replace(";", ",").split(","):
        item = part.strip()
        if not item:
            continue
        if not item.isdigit():
            raise RuntimeError("ADMIN_IDS має містити тільки числові Telegram ID через кому")
        admin_id = int(item)
        if admin_id not in ids:
            ids.append(admin_id)
    return tuple(ids)
