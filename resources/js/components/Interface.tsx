import { Plus, ArrowRight, ChartColumn, List, Check, BarChart3, ClipboardList, Users, Target, CalendarDays, LogOut } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import type { ReactNode } from 'react';
export function Icon({ name, size = 22 }: { name: 'plus' | 'arrow' | 'chart' | 'list' | 'check'; size?: number }) {
    const Component = { plus: Plus, arrow: ArrowRight, chart: ChartColumn, list: List, check: Check }[name];
    return <Component size={size} strokeWidth={1.7} aria-hidden="true" />;
}
export function AppHeader({ active = 'inicio', admin = false }: { admin?: boolean; active?: 'inicio' | 'encuesta' | 'resultados' | 'usuarios' | 'publicacion' | 'metas' }) {
    return <><a className="skip-content" href="#main-content" onClick={e=>{e.preventDefault();const main=document.querySelector('main');if(main){main.id='main-content';main.tabIndex=-1;main.focus();main.scrollIntoView();}}}>Ir al contenido principal</a><header className={`app-header ${admin ? 'admin-app-header' : ''}`}><Link className="app-logo" href={admin ? "/tablero" : "/mi-establecimiento"}><span className="logo-symbol"><Icon name="plus" /></span><span>Calidad de atención<small>Red de Salud Abancay</small></span></Link><nav aria-label="Navegación principal">{admin ? <><Link aria-current={active === "resultados" ? "page" : undefined} href="/tablero"><BarChart3 size={17} aria-hidden="true"/><span>Resultados</span></Link><Link aria-current={active === "encuesta" ? "page" : undefined} href="/encuestas"><ClipboardList size={17} aria-hidden="true"/><span>Encuestas</span></Link><Link aria-current={active === "usuarios" ? "page" : undefined} href="/administracion/usuarios"><Users size={17} aria-hidden="true"/><span>Usuarios</span></Link><Link aria-current={active === "metas" ? "page" : undefined} href="/administracion/metas"><Target size={17} aria-hidden="true"/><span>Metas</span></Link><Link aria-current={active === "publicacion" ? "page" : undefined} href="/administracion/encuesta"><CalendarDays size={17} aria-hidden="true"/><span>Publicación</span></Link></> : <><Link aria-current={active === 'inicio' ? 'page' : undefined} href="/mi-establecimiento">Inicio</Link><Link aria-current={active === "encuesta" ? 'page' : undefined} href="/encuestas">Encuestas</Link></>}</nav><button className="quiet-button header-logout" onClick={() => router.post('/salir')}><LogOut size={16} aria-hidden="true"/><span>Salir</span></button></header></>;
}
export function AdminPageHeader({ context, title, description, actions, support }: { context: string; title: string; description: ReactNode; actions?: ReactNode; support?: ReactNode }) {
    return <header className="admin-page-heading"><div className="admin-page-copy"><p className="admin-page-context">{context}</p><h1>{title}</h1><p className="admin-page-description">{description}</p>{support && <div className="admin-page-support">{support}</div>}</div>{actions && <div className="admin-page-actions">{actions}</div>}</header>;
}
export function AdminSectionHeading({ title, description, aside, id }: { title: string; description?: ReactNode; aside?: ReactNode; id?: string }) {
    return <div className="admin-section-heading"><div><h2 id={id}>{title}</h2>{description && <p>{description}</p>}</div>{aside && <div className="admin-section-aside">{aside}</div>}</div>;
}
export function Pagination({ currentPage, lastPage, previous, next }: { currentPage: number; lastPage: number; previous: string | null; next: string | null }) {
    if (lastPage <= 1) return null;
    return <nav className="admin-pagination" aria-label="Paginación">{previous ? <Link href={previous}>Anterior</Link> : <span aria-hidden="true" /> }<span>Página <strong>{currentPage}</strong> de {lastPage}</span>{next ? <Link href={next}>Siguiente</Link> : <span aria-hidden="true" />}</nav>;
}
export function ActionCard({ href, title, children, icon }: { href: string; title: string; children: ReactNode; icon: 'list' | 'chart' }) {
    return <Link className="action-card" href={href}><span className="card-icon"><Icon name={icon} /></span><div><h3>{title}</h3><p>{children}</p></div><Icon name="arrow" /></Link>;
}
export function Stepper({ steps, current, onSelect }: { steps: string[]; current: number; onSelect: (step: number) => void }) {
    return <nav className="stepper" aria-label="Pasos de la encuesta">{steps.map((name, i) => <button type="button" key={name} className={i === current ? 'current' : ''} aria-current={i === current ? 'step' : undefined} onClick={() => onSelect(i)}><span>{String(i + 1).padStart(2, '0')}</span><strong>{name}</strong></button>)}</nav>;
}
export function RatingQuestion({ id, code, text, value, onChange, error, disabledReason }: { id: string; code: string; text: string; value: string; onChange: (value: string) => void; error?: string; disabledReason?: string }) {
    return <fieldset disabled={!!disabledReason} id={id} tabIndex={-1} aria-invalid={!!error} className={`rating-question ${value ? 'answered' : ''} ${disabledReason ? 'rating-unavailable' : ''}`} aria-describedby={disabledReason ? `${id}-reason` : error ? `${id}-error` : undefined}>
        <legend><span className="question-code">{code}</span>{text}</legend>
        {disabledReason && <p className="rating-unavailable-note" id={`${id}-reason`}>{disabledReason}</p>}
        <div className="rating-scale">{['1','2','3','4','5','6','7'].map(v => <label key={v} className={value === v ? 'selected' : ''}><input type="radio" name={id} value={v} checked={value === v} onChange={() => onChange(v)} required aria-label={`${code}: ${v}`} /><span>{v}</span></label>)}</div>
        <div className="scale-extremes"><span>Menor calificación</span><span>Mayor calificación</span></div>
        <div className="special-answers">{['No aplica','Sin respuesta'].map(v => <label key={v} className={value === v ? 'selected' : ''}><input type="radio" name={id} value={v} checked={value === v} onChange={() => onChange(v)} required /><span>{v}</span></label>)}<span className="answer-state">{disabledReason ? 'No corresponde' : value ? <><Icon name="check" size={14} /> Respondida</> : 'Selecciona una opción'}</span></div>
        {error && <p id={`${id}-error`} className="error" role="alert">{error}</p>}
    </fieldset>;
}
