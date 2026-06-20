from aiogram.fsm.state import State, StatesGroup


class BookingState(StatesGroup):
    choosing_service = State()
    choosing_addons = State()
    choosing_date = State()
    choosing_slot = State()
    entering_name = State()
    entering_phone = State()
    confirming = State()


class ServiceAdminState(StatesGroup):
    # Додавання нової послуги — покроково (назва текстом, далі кнопками).
    adding_name = State()
    adding_duration = State()
    adding_price = State()
    adding_price_manual = State()
    # Редагування наявної послуги.
    editing_name = State()
    editing_price_manual = State()


class AddonAdminState(StatesGroup):
    adding_name = State()
    adding_duration = State()
    adding_price = State()
    adding_price_manual = State()
    editing_name = State()
    editing_price_manual = State()


class ScheduleAdminState(StatesGroup):
    editing_day_off = State()


class AdminRequestState(StatesGroup):
    viewing = State()
    reschedule_date = State()
    reschedule_slot = State()


class ManualBookingState(StatesGroup):
    entering = State()


class BusinessSettingsState(StatesGroup):
    editing_location = State()
