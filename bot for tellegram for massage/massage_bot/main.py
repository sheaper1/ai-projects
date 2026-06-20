from __future__ import annotations

import asyncio
import logging
from datetime import datetime, timedelta

from aiogram import Bot, Dispatcher
from aiogram.exceptions import TelegramBadRequest
from aiogram.fsm.storage.memory import MemoryStorage
from aiogram.types import BotCommand, ErrorEvent

from massage_bot import keyboards, texts
from massage_bot.config import load_config
from massage_bot.database import Database
from massage_bot.handlers_admin import build_admin_router
from massage_bot.handlers_client import build_client_router

logger = logging.getLogger(__name__)


async def _followup_loop(bot: Bot, db: Database) -> None:
    while True:
        await asyncio.sleep(60)
        try:
            abandoned = db.get_abandoned_bookings(minutes=60)
            for user_id, chat_id in abandoned:
                try:
                    await bot.send_message(
                        chat_id,
                        texts.FOLLOWUP_TEXT,
                        reply_markup=keyboards.main_menu(),
                    )
                    db.mark_followup_sent(user_id)
                    logger.info("Follow-up sent to user %s", user_id)
                except Exception:
                    logger.exception("Failed to send follow-up to user %s", user_id)
        except Exception:
            logger.exception("Follow-up loop error")


async def _appointment_reminder_loop(
    bot: Bot,
    db: Database,
    timezone,
    admin_ids: tuple[int, ...],
) -> None:
    while True:
        try:
            now = datetime.now(timezone)
            requests = db.list_due_reminders(now, now + timedelta(hours=24))
            for request in requests:
                if request.user_id in admin_ids:
                    db.mark_reminder_sent(request.id)
                    continue
                try:
                    slot_start = datetime.fromisoformat(request.slot_start)
                    await bot.send_message(
                        request.user_id,
                        "Нагадуємо про ваш запис 💆\n\n"
                        f"Послуга: {request.service_name}\n"
                        f"Час: {slot_start.strftime('%d.%m о %H:%M')}\n\n"
                        "Якщо плани змінилися, скасуйте запис кнопкою нижче.",
                        reply_markup=keyboards.client_confirmed_booking_keyboard(request.id),
                    )
                    db.mark_reminder_sent(request.id)
                    logger.info("Appointment reminder sent for request %s", request.id)
                except Exception:
                    logger.exception("Failed to send reminder for request %s", request.id)
        except Exception:
            logger.exception("Appointment reminder loop error")
        await asyncio.sleep(60)


async def _expire_pending_loop(bot: Bot, db: Database, ttl_minutes: int) -> None:
    while True:
        try:
            request_ids = db.expire_pending_requests(ttl_minutes)
            for request_id in request_ids:
                request = db.get_request(request_id)
                if request is None:
                    continue
                try:
                    await bot.send_message(
                        request.user_id,
                        "Час очікування підтвердження заявки минув, тому слот знову доступний.\n"
                        "Будь ласка, створіть новий запис, якщо він ще актуальний.",
                        reply_markup=keyboards.main_menu(),
                    )
                except Exception:
                    logger.exception("Failed to notify user about expired request %s", request_id)
            if request_ids:
                logger.info("Expired pending requests: %s", request_ids)
        except Exception:
            logger.exception("Pending request expiration loop error")
        await asyncio.sleep(60)


async def main() -> None:
    logging.basicConfig(level=logging.INFO)
    config = load_config()
    db = Database(config.database_path)
    db.initialize(admin_ids=config.admin_ids)
    if config.business_address and not db.get_setting("business_address"):
        db.set_setting("business_address", config.business_address)
    if config.map_url and not db.get_setting("map_url"):
        db.set_setting("map_url", config.map_url)

    bot = Bot(token=config.bot_token)
    await bot.set_my_commands(
        [
            BotCommand(command="start", description="Головне меню"),
            BotCommand(command="book", description="Записатися на масаж"),
            BotCommand(command="my_bookings", description="Мої записи"),
        ]
    )
    dispatcher = Dispatcher(storage=MemoryStorage())

    @dispatcher.error()
    async def on_error(event: ErrorEvent) -> None:
        exc = event.exception
        callback = event.update.callback_query
        # Повторне натискання тієї ж кнопки — контент не змінився, це не помилка.
        if isinstance(exc, TelegramBadRequest) and "message is not modified" in str(exc).lower():
            if callback is not None:
                try:
                    await callback.answer()
                except Exception:
                    pass
            return
        logger.exception("Unhandled update error", exc_info=exc)
        # Якщо це callback (зокрема застаре повідомлення без .message), даємо
        # користувачу зрозумілий сигнал замість «тихого» зависання.
        if callback is not None:
            try:
                await callback.answer("Сталася помилка. Спробуйте ще раз або /start.", show_alert=True)
            except Exception:
                pass

    dispatcher.include_router(build_admin_router(db, config.admin_ids, config.timezone))
    dispatcher.include_router(
        build_client_router(
            db,
            config.admin_ids,
            config.timezone,
            business_address=config.business_address,
            map_url=config.map_url,
        )
    )

    followup_task = asyncio.create_task(_followup_loop(bot, db))
    reminder_task = asyncio.create_task(
        _appointment_reminder_loop(bot, db, config.timezone, config.admin_ids)
    )
    expiration_task = asyncio.create_task(
        _expire_pending_loop(bot, db, config.pending_request_ttl_minutes)
    )
    try:
        await dispatcher.start_polling(bot)
    finally:
        followup_task.cancel()
        reminder_task.cancel()
        expiration_task.cancel()
        await asyncio.gather(
            followup_task,
            reminder_task,
            expiration_task,
            return_exceptions=True,
        )


if __name__ == "__main__":
    asyncio.run(main())
