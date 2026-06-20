from __future__ import annotations

import sqlite3
from contextlib import contextmanager
from dataclasses import dataclass
from datetime import date, datetime, time, timedelta, timezone as dt_timezone
from pathlib import Path
from typing import Iterator


@dataclass(frozen=True)
class Service:
    id: int
    name: str
    duration_minutes: int
    price_uah: int
    is_active: bool


@dataclass(frozen=True)
class Addon:
    id: int
    name: str
    duration_minutes: int
    price_uah: int
    is_active: bool


@dataclass(frozen=True)
class AddonLine:
    """Знімок допослуги, збережений у заявці (не змінюється при редагуванні каталогу)."""

    addon_id: int
    name: str
    duration_minutes: int
    price_uah: int


@dataclass(frozen=True)
class WorkingHours:
    weekday: int
    start_time: str
    end_time: str
    is_working: bool


@dataclass(frozen=True)
class BookingRequest:
    id: int
    user_id: int
    user_name: str | None
    client_name: str
    phone: str
    service_id: int
    service_name: str
    slot_start: str
    slot_end: str
    status: str


class Database:
    def __init__(self, path: str) -> None:
        self.path = path
        Path(path).parent.mkdir(parents=True, exist_ok=True) if Path(path).parent != Path(".") else None

    @contextmanager
    def connect(self) -> Iterator[sqlite3.Connection]:
        conn = sqlite3.connect(self.path, timeout=10)
        conn.row_factory = sqlite3.Row
        conn.execute("PRAGMA foreign_keys = ON")
        conn.execute("PRAGMA busy_timeout = 10000")
        conn.execute("PRAGMA journal_mode = WAL")
        conn.execute("PRAGMA synchronous = NORMAL")
        try:
            yield conn
            conn.commit()
        finally:
            conn.close()

    def initialize(self, admin_id: int | None = None, admin_ids: tuple[int, ...] | None = None) -> None:
        admin_values = admin_ids or ((admin_id,) if admin_id is not None else ())
        with self.connect() as conn:
            conn.executescript(
                """
                CREATE TABLE IF NOT EXISTS settings (
                    key TEXT PRIMARY KEY,
                    value TEXT NOT NULL
                );

                CREATE TABLE IF NOT EXISTS services (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    duration_minutes INTEGER NOT NULL CHECK(duration_minutes > 0),
                    price_uah INTEGER NOT NULL CHECK(price_uah >= 0),
                    is_active INTEGER NOT NULL DEFAULT 1,
                    sort_order INTEGER NOT NULL DEFAULT 0
                );

                CREATE TABLE IF NOT EXISTS working_hours (
                    weekday INTEGER PRIMARY KEY CHECK(weekday BETWEEN 1 AND 7),
                    start_time TEXT NOT NULL,
                    end_time TEXT NOT NULL,
                    is_working INTEGER NOT NULL DEFAULT 1
                );

                CREATE TABLE IF NOT EXISTS days_off (
                    day TEXT PRIMARY KEY
                );

                CREATE TABLE IF NOT EXISTS booking_requests (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    user_name TEXT,
                    client_name TEXT NOT NULL,
                    phone TEXT NOT NULL,
                    service_id INTEGER NOT NULL REFERENCES services(id),
                    slot_start TEXT NOT NULL,
                    slot_end TEXT NOT NULL,
                    status TEXT NOT NULL DEFAULT 'pending',
                    reminder_sent_at TEXT,
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS addons (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    duration_minutes INTEGER NOT NULL DEFAULT 0 CHECK(duration_minutes >= 0),
                    price_uah INTEGER NOT NULL CHECK(price_uah >= 0),
                    is_active INTEGER NOT NULL DEFAULT 1
                );

                CREATE TABLE IF NOT EXISTS booking_addons (
                    request_id INTEGER NOT NULL REFERENCES booking_requests(id) ON DELETE CASCADE,
                    addon_id INTEGER NOT NULL REFERENCES addons(id),
                    name TEXT NOT NULL,
                    duration_minutes INTEGER NOT NULL,
                    price_uah INTEGER NOT NULL,
                    PRIMARY KEY (request_id, addon_id)
                );

                CREATE TABLE IF NOT EXISTS text_requests (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    user_name TEXT,
                    client_name TEXT NOT NULL,
                    phone TEXT NOT NULL,
                    service_name TEXT NOT NULL,
                    desired_time TEXT NOT NULL,
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS pending_bookings (
                    user_id INTEGER PRIMARY KEY,
                    chat_id INTEGER NOT NULL,
                    started_at TEXT NOT NULL,
                    followup_sent_at TEXT
                );
                """
            )
            columns = {
                str(row["name"])
                for row in conn.execute("PRAGMA table_info(booking_requests)").fetchall()
            }
            if "reminder_sent_at" not in columns:
                conn.execute("ALTER TABLE booking_requests ADD COLUMN reminder_sent_at TEXT")
            service_columns = {
                str(row["name"])
                for row in conn.execute("PRAGMA table_info(services)").fetchall()
            }
            if "sort_order" not in service_columns:
                conn.execute("ALTER TABLE services ADD COLUMN sort_order INTEGER NOT NULL DEFAULT 0")
            conn.execute(
                "INSERT OR REPLACE INTO settings(key, value) VALUES('admin_ids', ?)",
                (",".join(str(item) for item in admin_values),),
            )
            count = conn.execute("SELECT COUNT(*) FROM services").fetchone()[0]
            if count == 0:
                conn.executemany(
                    """
                    INSERT INTO services(name, duration_minutes, price_uah)
                    VALUES(?, ?, ?)
                    """,
                    [
                        ("Класичний загальний масаж", 60, 650),
                        ("Масаж спини", 30, 400),
                        ("Масаж шийно-комірцевої зони", 25, 350),
                        ("Антицелюлітний масаж", 45, 550),
                        ("Лімфодренажний масаж", 60, 650),
                    ],
                )
            addons_count = conn.execute("SELECT COUNT(*) FROM addons").fetchone()[0]
            if addons_count == 0:
                conn.executemany(
                    """
                    INSERT INTO addons(name, duration_minutes, price_uah)
                    VALUES(?, ?, ?)
                    """,
                    [
                        ("Гарячі камені", 15, 150),
                        ("Аромотерапія", 0, 100),
                        ("Скраб тіла", 20, 250),
                        ("Продовження сеансу 15 хв", 15, 120),
                    ],
                )
            hours_count = conn.execute("SELECT COUNT(*) FROM working_hours").fetchone()[0]
            if hours_count == 0:
                conn.executemany(
                    """
                    INSERT INTO working_hours(weekday, start_time, end_time, is_working)
                    VALUES(?, ?, ?, ?)
                    """,
                    [
                        (1, "10:00", "19:00", 1),
                        (2, "10:00", "19:00", 1),
                        (3, "10:00", "19:00", 1),
                        (4, "10:00", "19:00", 1),
                        (5, "10:00", "19:00", 1),
                        (6, "10:00", "16:00", 1),
                        (7, "10:00", "16:00", 0),
                    ],
                )

    def list_services(self, active_only: bool = True) -> list[Service]:
        query = "SELECT * FROM services"
        params: tuple[object, ...] = ()
        if active_only:
            query += " WHERE is_active = 1"
        query += " ORDER BY sort_order, name"
        with self.connect() as conn:
            rows = conn.execute(query, params).fetchall()
        return [self._service(row) for row in rows]

    def get_service(self, service_id: int) -> Service | None:
        with self.connect() as conn:
            row = conn.execute("SELECT * FROM services WHERE id = ?", (service_id,)).fetchone()
        return self._service(row) if row else None

    def add_service(self, name: str, duration_minutes: int, price_uah: int, sort_order: int = 0) -> None:
        with self.connect() as conn:
            conn.execute(
                "INSERT INTO services(name, duration_minutes, price_uah, sort_order) VALUES(?, ?, ?, ?)",
                (name, duration_minutes, price_uah, sort_order),
            )

    def update_service(self, service_id: int, name: str, duration_minutes: int, price_uah: int) -> None:
        with self.connect() as conn:
            conn.execute(
                """
                UPDATE services
                SET name = ?, duration_minutes = ?, price_uah = ?, is_active = 1
                WHERE id = ?
                """,
                (name, duration_minutes, price_uah, service_id),
            )

    def set_service_name(self, service_id: int, name: str) -> None:
        with self.connect() as conn:
            conn.execute("UPDATE services SET name = ? WHERE id = ?", (name, service_id))

    def set_service_duration(self, service_id: int, duration_minutes: int) -> None:
        with self.connect() as conn:
            conn.execute(
                "UPDATE services SET duration_minutes = ? WHERE id = ?",
                (duration_minutes, service_id),
            )

    def set_service_price(self, service_id: int, price_uah: int) -> None:
        with self.connect() as conn:
            conn.execute(
                "UPDATE services SET price_uah = ? WHERE id = ?",
                (price_uah, service_id),
            )

    def deactivate_service(self, service_id: int) -> None:
        with self.connect() as conn:
            conn.execute("UPDATE services SET is_active = 0 WHERE id = ?", (service_id,))

    def activate_service(self, service_id: int) -> None:
        with self.connect() as conn:
            conn.execute("UPDATE services SET is_active = 1 WHERE id = ?", (service_id,))

    # ── Add-ons (додаткові послуги) ──────────────────────────────────────────

    def list_addons(self, active_only: bool = True) -> list[Addon]:
        query = "SELECT * FROM addons"
        if active_only:
            query += " WHERE is_active = 1"
        query += " ORDER BY name"
        with self.connect() as conn:
            rows = conn.execute(query).fetchall()
        return [self._addon(row) for row in rows]

    def get_addon(self, addon_id: int) -> Addon | None:
        with self.connect() as conn:
            row = conn.execute("SELECT * FROM addons WHERE id = ?", (addon_id,)).fetchone()
        return self._addon(row) if row else None

    def add_addon(self, name: str, duration_minutes: int, price_uah: int) -> None:
        with self.connect() as conn:
            conn.execute(
                "INSERT INTO addons(name, duration_minutes, price_uah) VALUES(?, ?, ?)",
                (name, duration_minutes, price_uah),
            )

    def set_addon_name(self, addon_id: int, name: str) -> None:
        with self.connect() as conn:
            conn.execute("UPDATE addons SET name = ? WHERE id = ?", (name, addon_id))

    def set_addon_duration(self, addon_id: int, duration_minutes: int) -> None:
        with self.connect() as conn:
            conn.execute(
                "UPDATE addons SET duration_minutes = ? WHERE id = ?",
                (duration_minutes, addon_id),
            )

    def set_addon_price(self, addon_id: int, price_uah: int) -> None:
        with self.connect() as conn:
            conn.execute("UPDATE addons SET price_uah = ? WHERE id = ?", (price_uah, addon_id))

    def deactivate_addon(self, addon_id: int) -> None:
        with self.connect() as conn:
            conn.execute("UPDATE addons SET is_active = 0 WHERE id = ?", (addon_id,))

    def activate_addon(self, addon_id: int) -> None:
        with self.connect() as conn:
            conn.execute("UPDATE addons SET is_active = 1 WHERE id = ?", (addon_id,))

    def list_request_addons(self, request_id: int) -> list[AddonLine]:
        with self.connect() as conn:
            rows = conn.execute(
                """
                SELECT addon_id, name, duration_minutes, price_uah
                FROM booking_addons WHERE request_id = ?
                ORDER BY name
                """,
                (request_id,),
            ).fetchall()
        return [
            AddonLine(
                addon_id=int(row["addon_id"]),
                name=str(row["name"]),
                duration_minutes=int(row["duration_minutes"]),
                price_uah=int(row["price_uah"]),
            )
            for row in rows
        ]

    def list_working_hours(self) -> list[WorkingHours]:
        with self.connect() as conn:
            rows = conn.execute("SELECT * FROM working_hours ORDER BY weekday").fetchall()
        return [self._hours(row) for row in rows]

    def get_working_hours(self, weekday: int) -> WorkingHours | None:
        with self.connect() as conn:
            row = conn.execute("SELECT * FROM working_hours WHERE weekday = ?", (weekday,)).fetchone()
        return self._hours(row) if row else None

    def set_working_hours(self, weekday: int, start_time: str, end_time: str, is_working: bool) -> None:
        with self.connect() as conn:
            conn.execute(
                """
                INSERT INTO working_hours(weekday, start_time, end_time, is_working)
                VALUES(?, ?, ?, ?)
                ON CONFLICT(weekday) DO UPDATE SET
                    start_time = excluded.start_time,
                    end_time = excluded.end_time,
                    is_working = excluded.is_working
                """,
                (weekday, start_time, end_time, int(is_working)),
            )

    def is_day_off(self, day: date) -> bool:
        with self.connect() as conn:
            row = conn.execute("SELECT 1 FROM days_off WHERE day = ?", (day.isoformat(),)).fetchone()
        return row is not None

    def set_day_off(self, day: date) -> None:
        with self.connect() as conn:
            conn.execute(
                "INSERT OR IGNORE INTO days_off(day) VALUES(?)",
                (day.isoformat(),),
            )

    def remove_day_off(self, day: date) -> None:
        with self.connect() as conn:
            conn.execute("DELETE FROM days_off WHERE day = ?", (day.isoformat(),))

    def list_days_off(self, from_day: date | None = None) -> list[date]:
        query = "SELECT day FROM days_off"
        params: tuple[object, ...] = ()
        if from_day is not None:
            query += " WHERE day >= ?"
            params = (from_day.isoformat(),)
        query += " ORDER BY day"
        with self.connect() as conn:
            rows = conn.execute(query, params).fetchall()
        return [date.fromisoformat(str(row["day"])) for row in rows]

    def list_busy_slots(
        self,
        from_dt: datetime,
        to_dt: datetime,
        exclude_request_id: int | None = None,
    ) -> list[tuple[datetime, datetime]]:
        query = """
            SELECT slot_start, slot_end FROM booking_requests
            WHERE status IN ('pending', 'confirmed')
              AND slot_start < ?
              AND slot_end > ?
        """
        params: list[object] = [to_dt.isoformat(), from_dt.isoformat()]
        if exclude_request_id is not None:
            query += " AND id != ?"
            params.append(exclude_request_id)
        with self.connect() as conn:
            rows = conn.execute(query, tuple(params)).fetchall()
        return [(datetime.fromisoformat(row["slot_start"]), datetime.fromisoformat(row["slot_end"])) for row in rows]

    def list_confirmed_slots(self, from_dt: datetime, to_dt: datetime) -> list[tuple[datetime, datetime]]:
        with self.connect() as conn:
            rows = conn.execute(
                """
                SELECT slot_start, slot_end FROM booking_requests
                WHERE status = 'confirmed' AND slot_start < ? AND slot_end > ?
                """,
                (to_dt.isoformat(), from_dt.isoformat()),
            ).fetchall()
        return [(datetime.fromisoformat(row["slot_start"]), datetime.fromisoformat(row["slot_end"])) for row in rows]

    def create_request(
        self,
        user_id: int,
        user_name: str | None,
        client_name: str,
        phone: str,
        service_id: int,
        slot_start: datetime,
        slot_end: datetime,
        status: str = "pending",
        addons: list[Addon] | None = None,
    ) -> int:
        with self.connect() as conn:
            cursor = conn.execute(
                """
                INSERT INTO booking_requests(
                    user_id, user_name, client_name, phone, service_id, slot_start, slot_end, status
                )
                VALUES(?, ?, ?, ?, ?, ?, ?, ?)
                """,
                (
                    user_id,
                    user_name,
                    client_name,
                    phone,
                    service_id,
                    slot_start.isoformat(),
                    slot_end.isoformat(),
                    status,
                ),
            )
            request_id = int(cursor.lastrowid)
            self._insert_request_addons(conn, request_id, addons)
            return request_id

    def create_request_if_available(
        self,
        user_id: int,
        user_name: str | None,
        client_name: str,
        phone: str,
        service_id: int,
        slot_start: datetime,
        slot_end: datetime,
        addons: list[Addon] | None = None,
    ) -> int | None:
        with self.connect() as conn:
            conn.execute("BEGIN IMMEDIATE")
            conflict = conn.execute(
                """
                SELECT 1 FROM booking_requests
                WHERE status IN ('pending', 'confirmed')
                  AND slot_start < ?
                  AND slot_end > ?
                LIMIT 1
                """,
                (slot_end.isoformat(), slot_start.isoformat()),
            ).fetchone()
            if conflict:
                return None
            cursor = conn.execute(
                """
                INSERT INTO booking_requests(
                    user_id, user_name, client_name, phone, service_id, slot_start, slot_end, status
                )
                VALUES(?, ?, ?, ?, ?, ?, ?, 'pending')
                """,
                (
                    user_id,
                    user_name,
                    client_name,
                    phone,
                    service_id,
                    slot_start.isoformat(),
                    slot_end.isoformat(),
                ),
            )
            request_id = int(cursor.lastrowid)
            self._insert_request_addons(conn, request_id, addons)
            return request_id

    @staticmethod
    def _insert_request_addons(
        conn: sqlite3.Connection,
        request_id: int,
        addons: list[Addon] | None,
    ) -> None:
        if not addons:
            return
        conn.executemany(
            """
            INSERT OR IGNORE INTO booking_addons(request_id, addon_id, name, duration_minutes, price_uah)
            VALUES(?, ?, ?, ?, ?)
            """,
            [
                (request_id, addon.id, addon.name, addon.duration_minutes, addon.price_uah)
                for addon in addons
            ],
        )

    def get_request(self, request_id: int) -> BookingRequest | None:
        with self.connect() as conn:
            row = conn.execute(
                """
                SELECT br.*, s.name AS service_name
                FROM booking_requests br
                JOIN services s ON s.id = br.service_id
                WHERE br.id = ?
                """,
                (request_id,),
            ).fetchone()
        return self._request(row) if row else None

    def list_pending_requests(self) -> list[BookingRequest]:
        with self.connect() as conn:
            rows = conn.execute(
                """
                SELECT br.*, s.name AS service_name
                FROM booking_requests br
                JOIN services s ON s.id = br.service_id
                WHERE br.status = 'pending'
                ORDER BY br.created_at
                """
            ).fetchall()
        return [self._request(row) for row in rows]

    def list_user_requests(
        self,
        user_id: int,
        from_dt: datetime | None = None,
        active_only: bool = False,
    ) -> list[BookingRequest]:
        conditions = ["br.user_id = ?"]
        params: list[object] = [user_id]
        if from_dt is not None:
            conditions.append("br.slot_end >= ?")
            params.append(from_dt.isoformat())
        if active_only:
            conditions.append("br.status IN ('pending', 'confirmed')")
        with self.connect() as conn:
            rows = conn.execute(
                f"""
                SELECT br.*, s.name AS service_name
                FROM booking_requests br
                JOIN services s ON s.id = br.service_id
                WHERE {' AND '.join(conditions)}
                ORDER BY br.slot_start
                """,
                tuple(params),
            ).fetchall()
        return [self._request(row) for row in rows]

    def list_requests_for_day(self, day: date) -> list[BookingRequest]:
        day_start = datetime.combine(day, time.min)
        day_end = datetime.combine(day, time.max)
        with self.connect() as conn:
            rows = conn.execute(
                """
                SELECT br.*, s.name AS service_name
                FROM booking_requests br
                JOIN services s ON s.id = br.service_id
                WHERE br.slot_start BETWEEN ? AND ?
                ORDER BY br.slot_start
                """,
                (day_start.isoformat(), day_end.isoformat()),
            ).fetchall()
        return [self._request(row) for row in rows]

    def count_requests_by_day(self, start_day: date, days: int) -> dict[date, int]:
        end_day = start_day + timedelta(days=days - 1)
        day_start = datetime.combine(start_day, time.min)
        day_end = datetime.combine(end_day, time.max)
        with self.connect() as conn:
            rows = conn.execute(
                """
                SELECT substr(slot_start, 1, 10) AS day, COUNT(*) AS count
                FROM booking_requests
                WHERE slot_start BETWEEN ?
                  AND ?
                  AND status IN ('pending', 'confirmed')
                GROUP BY substr(slot_start, 1, 10)
                """,
                (day_start.isoformat(), day_end.isoformat()),
            ).fetchall()
        return {date.fromisoformat(row["day"]): int(row["count"]) for row in rows}

    def set_request_status(self, request_id: int, status: str) -> None:
        if status not in {"pending", "confirmed", "declined", "cancelled", "expired"}:
            raise ValueError("Невідомий статус заявки")
        with self.connect() as conn:
            conn.execute("UPDATE booking_requests SET status = ? WHERE id = ?", (status, request_id))

    def expire_pending_requests(self, older_than_minutes: int = 120) -> list[int]:
        cutoff = (
            datetime.now(dt_timezone.utc) - timedelta(minutes=older_than_minutes)
        ).strftime("%Y-%m-%d %H:%M:%S")
        with self.connect() as conn:
            rows = conn.execute(
                """
                SELECT id FROM booking_requests
                WHERE status = 'pending'
                  AND datetime(created_at) < datetime(?)
                """,
                (cutoff,),
            ).fetchall()
            request_ids = [int(row["id"]) for row in rows]
            if request_ids:
                placeholders = ",".join("?" for _ in request_ids)
                conn.execute(
                    f"UPDATE booking_requests SET status = 'expired' WHERE id IN ({placeholders})",
                    tuple(request_ids),
                )
        return request_ids

    def get_dashboard_counts(self, today: date) -> dict[str, int]:
        day_start = datetime.combine(today, time.min).isoformat()
        day_end = datetime.combine(today, time.max).isoformat()
        with self.connect() as conn:
            pending = conn.execute(
                "SELECT COUNT(*) FROM booking_requests WHERE status = 'pending'"
            ).fetchone()[0]
            today_confirmed = conn.execute(
                """
                SELECT COUNT(*) FROM booking_requests
                WHERE status = 'confirmed' AND slot_start BETWEEN ? AND ?
                """,
                (day_start, day_end),
            ).fetchone()[0]
            active_services = conn.execute(
                "SELECT COUNT(*) FROM services WHERE is_active = 1"
            ).fetchone()[0]
        return {
            "pending": int(pending),
            "today_confirmed": int(today_confirmed),
            "active_services": int(active_services),
        }

    def update_request_slot(self, request_id: int, slot_start: datetime, slot_end: datetime, status: str = "confirmed") -> None:
        if status not in {"pending", "confirmed"}:
            raise ValueError("Невідомий статус заявки")
        with self.connect() as conn:
            conn.execute(
                """
                UPDATE booking_requests
                SET slot_start = ?, slot_end = ?, status = ?
                WHERE id = ?
                """,
                (slot_start.isoformat(), slot_end.isoformat(), status, request_id),
            )

    def create_text_request(
        self,
        user_id: int,
        user_name: str | None,
        client_name: str,
        phone: str,
        service_name: str,
        desired_time: str,
    ) -> int:
        with self.connect() as conn:
            cursor = conn.execute(
                """
                INSERT INTO text_requests(user_id, user_name, client_name, phone, service_name, desired_time)
                VALUES(?, ?, ?, ?, ?, ?)
                """,
                (user_id, user_name, client_name, phone, service_name, desired_time),
            )
            return int(cursor.lastrowid)

    def upsert_pending_booking(self, user_id: int, chat_id: int) -> None:
        now = datetime.now(dt_timezone.utc).isoformat()
        with self.connect() as conn:
            conn.execute(
                """
                INSERT INTO pending_bookings(user_id, chat_id, started_at)
                VALUES(?, ?, ?)
                ON CONFLICT(user_id) DO UPDATE SET started_at = excluded.started_at, followup_sent_at = NULL
                """,
                (user_id, chat_id, now),
            )

    def clear_pending_booking(self, user_id: int) -> None:
        with self.connect() as conn:
            conn.execute("DELETE FROM pending_bookings WHERE user_id = ?", (user_id,))

    def get_abandoned_bookings(self, minutes: int = 60) -> list[tuple[int, int]]:
        cutoff = (datetime.now(dt_timezone.utc) - timedelta(minutes=minutes)).isoformat()
        today = datetime.now(dt_timezone.utc).date().isoformat()
        with self.connect() as conn:
            rows = conn.execute(
                """
                SELECT user_id, chat_id FROM pending_bookings
                WHERE started_at < ?
                  AND (followup_sent_at IS NULL OR DATE(followup_sent_at) < ?)
                """,
                (cutoff, today),
            ).fetchall()
        return [(int(row["user_id"]), int(row["chat_id"])) for row in rows]

    def mark_followup_sent(self, user_id: int) -> None:
        now = datetime.now(dt_timezone.utc).isoformat()
        with self.connect() as conn:
            conn.execute(
                "UPDATE pending_bookings SET followup_sent_at = ? WHERE user_id = ?",
                (now, user_id),
            )

    def list_due_reminders(self, now: datetime, until: datetime) -> list[BookingRequest]:
        with self.connect() as conn:
            rows = conn.execute(
                """
                SELECT br.*, s.name AS service_name
                FROM booking_requests br
                JOIN services s ON s.id = br.service_id
                WHERE br.status = 'confirmed'
                  AND br.reminder_sent_at IS NULL
                  AND br.slot_start > ?
                  AND br.slot_start <= ?
                ORDER BY br.slot_start
                """,
                (now.isoformat(), until.isoformat()),
            ).fetchall()
        return [self._request(row) for row in rows]

    def mark_reminder_sent(self, request_id: int) -> None:
        now = datetime.now(dt_timezone.utc).isoformat()
        with self.connect() as conn:
            conn.execute(
                "UPDATE booking_requests SET reminder_sent_at = ? WHERE id = ?",
                (now, request_id),
            )

    def get_setting(self, key: str, default: str = "") -> str:
        with self.connect() as conn:
            row = conn.execute("SELECT value FROM settings WHERE key = ?", (key,)).fetchone()
        return str(row["value"]) if row else default

    def set_setting(self, key: str, value: str) -> None:
        with self.connect() as conn:
            conn.execute(
                """
                INSERT INTO settings(key, value) VALUES(?, ?)
                ON CONFLICT(key) DO UPDATE SET value = excluded.value
                """,
                (key, value),
            )

    @staticmethod
    def _addon(row: sqlite3.Row) -> Addon:
        return Addon(
            id=int(row["id"]),
            name=str(row["name"]),
            duration_minutes=int(row["duration_minutes"]),
            price_uah=int(row["price_uah"]),
            is_active=bool(row["is_active"]),
        )

    @staticmethod
    def _service(row: sqlite3.Row) -> Service:
        return Service(
            id=int(row["id"]),
            name=str(row["name"]),
            duration_minutes=int(row["duration_minutes"]),
            price_uah=int(row["price_uah"]),
            is_active=bool(row["is_active"]),
        )

    @staticmethod
    def _hours(row: sqlite3.Row) -> WorkingHours:
        return WorkingHours(
            weekday=int(row["weekday"]),
            start_time=str(row["start_time"]),
            end_time=str(row["end_time"]),
            is_working=bool(row["is_working"]),
        )

    @staticmethod
    def _request(row: sqlite3.Row) -> BookingRequest:
        return BookingRequest(
            id=int(row["id"]),
            user_id=int(row["user_id"]),
            user_name=row["user_name"],
            client_name=str(row["client_name"]),
            phone=str(row["phone"]),
            service_id=int(row["service_id"]),
            service_name=str(row["service_name"]),
            slot_start=str(row["slot_start"]),
            slot_end=str(row["slot_end"]),
            status=str(row["status"]),
        )
