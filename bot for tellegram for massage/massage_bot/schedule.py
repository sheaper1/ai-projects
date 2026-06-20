from __future__ import annotations

from datetime import date, datetime, time, timedelta
from zoneinfo import ZoneInfo

from massage_bot.database import Database, Service


def parse_hhmm(value: str) -> time:
    hour, minute = value.split(":", maxsplit=1)
    return time(hour=int(hour), minute=int(minute))


def format_slot(dt: datetime) -> str:
    return dt.strftime("%d.%m о %H:%M")


def format_time(dt: datetime) -> str:
    return dt.strftime("%H:%M")


def format_date_label(day: date, reference_day: date | None = None) -> str:
    today = reference_day or datetime.now().date()
    if day == today:
        return "Сьогодні"
    if day == today + timedelta(days=1):
        return "Завтра"

    weekdays = ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Нд"]
    return f"{weekdays[day.weekday()]} {day.strftime('%d.%m')}"


def encode_slot(dt: datetime) -> str:
    return dt.strftime("%Y%m%d%H%M")


def decode_slot(value: str, timezone: ZoneInfo) -> datetime:
    return datetime.strptime(value, "%Y%m%d%H%M").replace(tzinfo=timezone)


def generate_slots(
    db: Database,
    service: Service,
    timezone: ZoneInfo,
    days_ahead: int = 14,
    step_minutes: int = 30,
    extra_minutes: int = 0,
) -> list[datetime]:
    now = datetime.now(timezone)
    start_day = now.date()
    end_day = start_day + timedelta(days=days_ahead)
    busy_slots = db.list_busy_slots(
        datetime.combine(start_day, time.min, tzinfo=timezone),
        datetime.combine(end_day + timedelta(days=1), time.max, tzinfo=timezone),
    )

    total_minutes = service.duration_minutes + extra_minutes
    slots: list[datetime] = []
    current_day = start_day
    while current_day <= end_day:
        slots.extend(_generate_day_slots(db, current_day, total_minutes, timezone, now, busy_slots, step_minutes))
        current_day += timedelta(days=1)
    return slots


def filter_slots_by_day(slots: list[datetime], day: date) -> list[datetime]:
    return [slot for slot in slots if slot.date() == day]


def slot_is_available(
    db: Database,
    service: Service,
    slot_start: datetime,
    timezone: ZoneInfo,
    exclude_request_id: int | None = None,
    extra_minutes: int = 0,
) -> bool:
    weekday_hours = db.get_working_hours(slot_start.isoweekday())
    if weekday_hours is None or not weekday_hours.is_working or db.is_day_off(slot_start.date()):
        return False

    day_start = datetime.combine(slot_start.date(), parse_hhmm(weekday_hours.start_time), tzinfo=timezone)
    day_end = datetime.combine(slot_start.date(), parse_hhmm(weekday_hours.end_time), tzinfo=timezone)
    slot_end = slot_start + timedelta(minutes=service.duration_minutes + extra_minutes)
    if slot_start < day_start or slot_end > day_end:
        return False

    conflicts = db.list_busy_slots(slot_start, slot_end, exclude_request_id=exclude_request_id)
    return not any(slot_start < busy_end and slot_end > busy_start for busy_start, busy_end in conflicts)


def _generate_day_slots(
    db: Database,
    day: date,
    total_minutes: int,
    timezone: ZoneInfo,
    now: datetime,
    confirmed: list[tuple[datetime, datetime]],
    step_minutes: int,
) -> list[datetime]:
    hours = db.get_working_hours(day.isoweekday())
    if hours is None or not hours.is_working or db.is_day_off(day):
        return []

    start = datetime.combine(day, parse_hhmm(hours.start_time), tzinfo=timezone)
    end = datetime.combine(day, parse_hhmm(hours.end_time), tzinfo=timezone)
    slot_length = timedelta(minutes=total_minutes)
    step = timedelta(minutes=step_minutes)

    slots: list[datetime] = []
    current = start
    while current + slot_length <= end:
        slot_end = current + slot_length
        if current > now and not _has_conflict(current, slot_end, confirmed):
            slots.append(current)
        current += step
    return slots


def _has_conflict(start: datetime, end: datetime, confirmed: list[tuple[datetime, datetime]]) -> bool:
    return any(start < busy_end and end > busy_start for busy_start, busy_end in confirmed)
