export const perceptionReason = 'No corresponde porque la expectativa no tiene una calificación. Para habilitarla, selecciona del 1 al 7 en expectativas.';
export function skipsPerception(value: string | null) { return value === 'No aplica' || value === 'Sin respuesta'; }
type Answer = { expectativa: string; percepcion: string };
export function normalizeAnswer<T extends Answer>(answer: T): T {
    return skipsPerception(answer.expectativa) ? { ...answer, percepcion: answer.expectativa } : answer;
}
export function changeAnswer<T extends Answer>(answer: T, field: 'expectativa' | 'percepcion', value: string): T {
    if (field === 'percepcion') return normalizeAnswer({ ...answer, percepcion: value });
    return normalizeAnswer({ ...answer, expectativa: value, percepcion: skipsPerception(answer.expectativa) ? '' : answer.percepcion });
}
