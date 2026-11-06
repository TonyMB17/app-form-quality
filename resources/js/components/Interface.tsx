import { Link, router } from '@inertiajs/react';
import type { ReactNode } from 'react';
export function Icon({ name, size = 22 }: { name: 'plus' | 'arrow' | 'chart' | 'list' | 'check'; size?: number }) {
    const paths = { plus: 'M12 5v14M5 12h14', arrow: 'M5 12h14m-6-6 6 6-6 6', chart: 'M5 20V10m7 10V4m7 16v-7', list: 'M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01', check: 'm5 12 4 4L19 6' };
    return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d={paths[name]} /></svg>;
}
export function AppHeader({ active = 'inicio', admin = false }: { admin?: boolean; active?: 'inicio' | 'encuesta' | 'resultados' | 'usuarios' | 'publicacion' }) {
    return <header className="app-header"><Link className="app-logo" href={admin ? "/tablero" : "/mi-establecimiento"}><span className="logo-symbol"><Icon name="plus" /></span><span>Calidad de atención<small>RED DE SALUD ABANCAY</small></span></Link><nav aria-label="Navegación principal">{admin ? <><Link aria-current={active === "resultados" ? "page" : undefined} href="/tablero">Resultados</Link><Link aria-current={active === "encuesta" ? "page" : undefined} href="/encuestas">Encuestas</Link><Link aria-current={active === "usuarios" ? "page" : undefined} href="/administracion/usuarios">Usuarios</Link><Link aria-current={active === "publicacion" ? "page" : undefined} href="/administracion/encuesta">Publicación</Link></> : <><Link aria-current={active === 'inicio' ? 'page' : undefined} href="/mi-establecimiento">Inicio</Link><Link aria-current={active === "encuesta" ? 'page' : undefined} href="/encuestas">Mi encuesta</Link></>}</nav><button className="quiet-button" onClick={() => router.post('/salir')}>Salir</button></header>;
}
export function ActionCard({ href, title, children, icon }: { href: string; title: string; children: ReactNode; icon: 'list' | 'chart' }) {
    return <Link className="action-card" href={href}><span className="card-icon"><Icon name={icon} /></span><div><h3>{title}</h3><p>{children}</p></div><Icon name="arrow" /></Link>;
}
export function Stepper({ steps, current, onSelect }: { steps: string[]; current: number; onSelect: (step: number) => void }) {
    return <nav className="stepper" aria-label="Pasos de la encuesta">{steps.map((name, i) => <button type="button" key={name} className={i === current ? 'current' : ''} aria-current={i === current ? 'step' : undefined} onClick={() => onSelect(i)}><span>{String(i + 1).padStart(2, '0')}</span><strong>{name}</strong></button>)}</nav>;
}
export function RatingQuestion({ id, code, text, value, onChange, error }: { id: string; code: string; text: string; value: string; onChange: (value: string) => void; error?: string }) {
    return <fieldset id={id} tabIndex={-1} className={`rating-question ${value ? 'answered' : ''}`} aria-describedby={error ? `${id}-error` : undefined}>
        <legend><span className="question-code">{code}</span>{text}</legend>
        <div className="rating-scale">{['1','2','3','4','5','6','7'].map(v => <label key={v} className={value === v ? 'selected' : ''}><input type="radio" name={id} value={v} checked={value === v} onChange={() => onChange(v)} required aria-label={`${code}: ${v}`} /><span>{v}</span></label>)}</div>
        <div className="scale-extremes"><span>Menor calificación</span><span>Mayor calificación</span></div>
        <div className="special-answers">{['No aplica','Sin respuesta'].map(v => <label key={v} className={value === v ? 'selected' : ''}><input type="radio" name={id} value={v} checked={value === v} onChange={() => onChange(v)} required /><span>{v}</span></label>)}<span className="answer-state">{value ? <><Icon name="check" size={14} /> Respondida</> : 'Selecciona una opción'}</span></div>
        {error && <p id={`${id}-error`} className="error" role="alert">{error}</p>}
    </fieldset>;
}
