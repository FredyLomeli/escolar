export interface WeekdayOption {
    value: number;
    short: string;
    label: string;
}

export const WEEKDAYS: WeekdayOption[] = [
    { value: 1, short: 'Lun', label: 'Lunes' },
    { value: 2, short: 'Mar', label: 'Martes' },
    { value: 3, short: 'Mié', label: 'Miércoles' },
    { value: 4, short: 'Jue', label: 'Jueves' },
    { value: 5, short: 'Vie', label: 'Viernes' },
    { value: 6, short: 'Sáb', label: 'Sábado' },
    { value: 7, short: 'Dom', label: 'Domingo' },
];

export function weekdayShort(day: number): string {
    return WEEKDAYS.find((item) => item.value === day)?.short ?? String(day);
}

export function formatHour(time: string): string {
    return time.slice(0, 5);
}
