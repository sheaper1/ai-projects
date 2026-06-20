from __future__ import annotations

import time
from collections import defaultdict, deque
from typing import Any, Awaitable, Callable

from aiogram import BaseMiddleware
from aiogram.types import CallbackQuery, Message, TelegramObject


class ThrottleMiddleware(BaseMiddleware):
    """Простий анти-флуд: не більше `limit` подій від користувача за `window` секунд.

    Захищає від спаму ботом і від завалювання майстра пересланими повідомленнями.
    Адміністратори не обмежуються. Стан тримається в пам'яті процесу.
    """

    def __init__(self, admin_ids: tuple[int, ...] = (), limit: int = 20, window: float = 30.0) -> None:
        self._admin_ids = set(admin_ids)
        self._limit = limit
        self._window = window
        self._hits: dict[int, deque[float]] = defaultdict(deque)

    async def __call__(
        self,
        handler: Callable[[TelegramObject, dict[str, Any]], Awaitable[Any]],
        event: TelegramObject,
        data: dict[str, Any],
    ) -> Any:
        user = getattr(event, "from_user", None)
        if user is None or user.id in self._admin_ids:
            return await handler(event, data)

        now = time.monotonic()
        hits = self._hits[user.id]
        while hits and now - hits[0] > self._window:
            hits.popleft()

        if len(hits) >= self._limit:
            # Тихо ігноруємо подію, лише делікатно повідомляємо.
            if isinstance(event, CallbackQuery):
                try:
                    await event.answer("Забагато дій поспіль. Зачекайте кілька секунд 🙏", show_alert=False)
                except Exception:
                    pass
            elif isinstance(event, Message):
                try:
                    await event.answer("Забагато повідомлень поспіль. Зачекайте трохи 🙏")
                except Exception:
                    pass
            return None

        hits.append(now)
        return await handler(event, data)
