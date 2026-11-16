import { ArrowRight, CircleAlert } from 'lucide-react';

export function ValidationSummary({ errors, label, onSelect }: { errors: Record<string, string>; label: (key: string) => string; onSelect: (key: string) => void }) {
    if (!Object.keys(errors).length) return null;
    return <section className="validation-summary" role="alert" aria-label="Campos por revisar"><div className="validation-heading"><CircleAlert size={23} aria-hidden="true" /><div><h2>Hay campos por completar</h2><p>Tus respuestas se conservan. Selecciona un campo para revisarlo.</p></div><span>{Object.keys(errors).length}</span></div><ul>{Object.entries(errors).map(([key, message]) => <li key={key}><button type="button" onClick={() => onSelect(key)}><span><strong>{label(key)}</strong><small>{message}</small></span><ArrowRight size={17} aria-hidden="true" /></button></li>)}</ul></section>;
}

export function SurveyProgress({ current, total, completed, pairs }: { current: number; total: number; completed: number; pairs: number }) {
    return <div className="survey-progress"><div aria-live="polite"><strong>Paso {current + 1} de {total}</strong><span>{completed} de {pairs} pares completos</span></div><progress max={pairs} value={completed} aria-label="Pares de respuestas completos" /><small>Un par incluye expectativa y percepción.</small></div>;
}
