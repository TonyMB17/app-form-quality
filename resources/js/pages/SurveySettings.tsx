import { formatDate } from '../components/formatDate';
import { Head, router, useForm } from '@inertiajs/react';
import { CalendarDays, Check, Clock3, Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { AdminPageHeader, AdminSectionHeading, AppHeader } from '../components/Interface';

type Period = {
    id: number;
    nombre: string;
    estado: 'borrador' | 'publicado' | 'cerrado';
    abre_at: string;
    cierra_at: string;
    abierto: boolean;
    estado_visual: 'Borrador' | 'Cerrado' | 'Programado' | 'Finalizado' | 'Abierto';
    encuestas: number;
};

const mostrarFecha = formatDate;
function nombreEstado(periodo: Period) { return periodo.estado_visual; }
function claseEstado(periodo: Period) {
    return periodo.estado_visual === 'Abierto' ? 'is-published' : periodo.estado_visual === 'Programado' ? 'is-scheduled' : periodo.estado_visual === 'Borrador' ? '' : 'is-closed';
}

function fechaLocal(value: string) {
    return value.replace(' ', 'T').slice(0, 16);
}

export default function SurveySettings({ periodos }: { periodos: Period[] }) {
    const [saved, setSaved] = useState(false);
    const [savedState, setSavedState] = useState(false);
    const [stateProcessing, setStateProcessing] = useState(false);
    const [stateErrors, setStateErrors] = useState<Record<string, string>>({});
    const [deleteError, setDeleteError] = useState('');
    const [selected, setSelected] = useState<Period | null>(null);
    const form = useForm({ id: null as number | null, nombre: '', estado: 'borrador', abre_at: '', cierra_at: '' });
    const abiertos = periodos.filter(periodo => periodo.abierto).length;
    const borradores = periodos.filter(periodo => periodo.estado === 'borrador').length;

    function select(periodo?: Period) {
        form.clearErrors();
        setSaved(false);
        setSelected(null);
        form.setData(periodo
            ? { id: periodo.id, nombre: periodo.nombre, estado: periodo.estado, abre_at: fechaLocal(periodo.abre_at), cierra_at: fechaLocal(periodo.cierra_at) }
            : { id: null, nombre: '', estado: 'borrador', abre_at: '', cierra_at: '' });
        document.getElementById('period-name')?.focus();
    }

    function changeState(periodo: Period) {
        const nextState = periodo.estado === 'publicado' ? 'cerrado' : 'publicado';
        setSavedState(false);
        setStateErrors({});
        router.put('/administracion/encuesta', {
            id: periodo.id,
            nombre: periodo.nombre,
            estado: nextState,
            abre_at: fechaLocal(periodo.abre_at),
            cierra_at: fechaLocal(periodo.cierra_at),
        }, {
            preserveScroll: true,
            onStart: () => setStateProcessing(true),
            onFinish: () => setStateProcessing(false),
            onError: errors => setStateErrors(errors),
            onSuccess: () => setSavedState(true),
        });
    }

    function remove(periodo: Period) {
        if (periodo.encuestas > 0) return;
        if (window.confirm(`¿Eliminar el periodo “${periodo.nombre}”? Esta acción no se puede deshacer.`)) {
            setDeleteError('');
            router.delete(`/administracion/encuesta/${periodo.id}`, {
                preserveScroll: true,
                onSuccess: () => setSelected(null),
                onError: errors => setDeleteError(errors.periodo ?? 'No se pudo eliminar el periodo.'),
            });
        }
    }

    return <><AppHeader admin active="publicacion" /><Head title="Publicación de periodos" />
        <main className="workspace admin-view publication-page">
            <AdminPageHeader context="Calendario de recolección" title="Publicación" description="Programa, revisa y administra los periodos de encuesta." actions={<button className="primary publication-new" type="button" onClick={() => select()}><Plus size={18} aria-hidden="true" />Nuevo periodo</button>} />

            <section className="publication-summary" aria-label="Resumen de periodos">
                <article><span>Periodos registrados</span><strong>{periodos.length}</strong><small>En todo el sistema</small></article>
                <article><span>Abiertos ahora</span><strong>{abiertos}</strong><small>Admiten registros dentro del plazo</small></article>
                <article><span>Borradores</span><strong>{borradores}</strong><small>Aún no publicados</small></article>
            </section>

            {(Object.entries(form.errors).map(([key, error]) => <p key={key} className="error publication-error" role="alert">{error}</p>))}
            {Object.entries(stateErrors).map(([key, error]) => <p key={key} className="error publication-error" role="alert">{error}</p>)}
            {deleteError && <p className="error publication-error" role="alert">{deleteError}</p>}
            {savedState && <p className="notice success-notice" role="status">Estado del periodo actualizado.</p>}

            <div className="publication-layout">
                <section className="publication-period-list" aria-labelledby="period-list-title">
                    <AdminSectionHeading id="period-list-title" title="Periodos registrados" description="Consulta sus fechas y cambia su estado cuando sea necesario." aside={<span className="admin-count-badge">{periodos.length}</span>} />
                    {periodos.length === 0
                        ? <div className="publication-empty"><CalendarDays size={30} aria-hidden="true" /><h3>Aún no hay periodos</h3><p>Crea uno para programar la recolección de encuestas.</p><button type="button" className="quiet-button" onClick={() => select()}>Crear periodo</button></div>
                        : <div className="publication-periods">{periodos.map(periodo => <article className="publication-period" key={periodo.id}>
                            <div className="publication-period-top"><span className={`publication-status ${claseEstado(periodo)}`}><span />{nombreEstado(periodo)}</span><span className="publication-survey-count">{periodo.encuestas} encuestas</span></div>
                            <h3>{periodo.nombre}</h3>
                            <p className="publication-date"><CalendarDays size={15} aria-hidden="true" />{mostrarFecha(periodo.abre_at)} <span aria-hidden="true">→</span> {mostrarFecha(periodo.cierra_at)}</p>
                            <div className="publication-period-actions">
                                <button type="button" className="quiet-button" onClick={() => setSelected(periodo)}><Eye size={16} aria-hidden="true" />Ver</button>
                                <button type="button" className="quiet-button" onClick={() => select(periodo)}><Pencil size={16} aria-hidden="true" />Editar</button>
                                <button type="button" className="quiet-button" onClick={() => changeState(periodo)} disabled={stateProcessing} aria-label={`${periodo.estado === 'publicado' ? 'Cerrar' : 'Publicar'} ${periodo.nombre}`}>{periodo.estado === 'publicado' ? 'Cerrar' : 'Publicar'}</button>
                                <button type="button" className="quiet-button publication-delete" onClick={() => remove(periodo)} disabled={periodo.encuestas > 0 || stateProcessing} title={periodo.encuestas > 0 ? 'No se puede eliminar: ya tiene encuestas registradas.' : undefined} aria-label={`Eliminar ${periodo.nombre}`}><Trash2 size={16} aria-hidden="true" /></button>
                            </div>
                            {periodo.encuestas > 0 && <small className="publication-protected">Los registros asociados se conservan; este periodo no se puede eliminar.</small>}
                        </article>)}</div>}
                </section>

                <aside className="publication-guide">
                    <span className="publication-guide-icon"><Clock3 size={20} aria-hidden="true" /></span>
                    <h2>Cómo funciona la publicación</h2>
                    <ul>
                        <li><strong>Borrador:</strong> todavía no admite encuestas.</li>
                        <li><strong>Programado:</strong> publicado, pero aún no llega su fecha de inicio.</li><li><strong>Finalizado:</strong> terminó el plazo; no permite registros ni cambios.</li><li><strong>Abierto:</strong> permite registrar y modificar encuestas entre las fechas configuradas.</li>
                        <li><strong>Cerrado:</strong> bloquea inmediatamente nuevos registros y cambios.</li>
                    </ul>
                    <p>Las fechas y horas se interpretan según la hora de Lima. Los periodos con encuestas no se pueden eliminar. Publicar no cambia las fechas: si el plazo venció, seguirá finalizado.</p>
                </aside>
            </div>

            <section className="auth-card publication-form" aria-labelledby="period-form-title">
                <div className="publication-form-heading"><div><p className="admin-page-context">{form.data.id ? 'Configuración' : 'Nuevo periodo'}</p><h2 id="period-form-title">{form.data.id ? 'Editar periodo' : 'Crear periodo'}</h2></div>{form.data.id && <button className="quiet-button" type="button" onClick={() => select()}>Cancelar edición</button>}</div>
                <form onSubmit={event => { event.preventDefault(); form.put('/administracion/encuesta', { preserveScroll: true, onSuccess: () => setSaved(true) }); }}>
                    <div className="publication-fields">
                        <div><label htmlFor="period-name">Nombre del periodo</label><input id="period-name" required maxLength={150} value={form.data.nombre} onChange={event => form.setData('nombre', event.target.value)} placeholder="Ej. Campaña de noviembre" /></div>
                        <div><label htmlFor="period-state">Estado</label><select id="period-state" value={form.data.estado} onChange={event => form.setData('estado', event.target.value)}><option value="borrador">Borrador</option><option value="publicado">Abierto / publicado</option><option value="cerrado">Cerrado</option></select></div>
                        <div><label htmlFor="period-start">Fecha y hora de apertura · Lima</label><input id="period-start" type="datetime-local" required value={form.data.abre_at} onChange={event => form.setData('abre_at', event.target.value)} /></div>
                        <div><label htmlFor="period-end">Fecha y hora de cierre · Lima</label><input id="period-end" type="datetime-local" required value={form.data.cierra_at} onChange={event => form.setData('cierra_at', event.target.value)} /></div>
                    </div>
                    <button className="quiet-button publication-duration" type="button" disabled={!form.data.abre_at} onClick={() => {
                        const start = new Date(`${form.data.abre_at}:00-05:00`);
                        start.setUTCDate(start.getUTCDate() + 14);
                        form.setData('cierra_at', new Date(start.getTime() - 5 * 60 * 60 * 1000).toISOString().slice(0, 16));
                    }}>Establecer duración de dos semanas</button>
                    <p className="publication-hint">Los periodos publicados se abren y cierran según el horario indicado. No pueden solaparse con otro periodo publicado.</p>
                    <div className="publication-actions"><button className="primary" disabled={form.processing}><Check size={17} aria-hidden="true" />{form.processing ? 'Guardando…' : 'Guardar periodo'}</button>{saved && <p role="status" className="notice success-notice">Periodo guardado correctamente.</p>}</div>
                </form>
            </section>

            {selected && <div className="publication-modal-backdrop" onMouseDown={event => { if (event.target === event.currentTarget) setSelected(null); }}>
                <section className="publication-modal" role="dialog" aria-modal="true" aria-labelledby="period-view-title" tabIndex={-1} onKeyDown={event => { if (event.key === 'Escape') setSelected(null); }}>
                    <button className="publication-modal-close quiet-button" type="button" autoFocus onClick={() => setSelected(null)} aria-label="Cerrar detalle">×</button>
                    <p className="admin-page-context">Detalle del periodo</p><h2 id="period-view-title">{selected.nombre}</h2>
                    <span className={`publication-status ${claseEstado(selected)}`}><span />{nombreEstado(selected)}</span>
                    <dl className="publication-details"><div><dt>Inicio · hora de Lima</dt><dd>{mostrarFecha(selected.abre_at)}</dd></div><div><dt>Cierre · hora de Lima</dt><dd>{mostrarFecha(selected.cierra_at)}</dd></div><div><dt>Encuestas registradas</dt><dd>{selected.encuestas}</dd></div></dl>
                    <div className="publication-period-actions"><button type="button" className="quiet-button" onClick={() => select(selected)}><Pencil size={16} aria-hidden="true" />Editar</button><button type="button" className="quiet-button publication-delete" onClick={() => remove(selected)} disabled={selected.encuestas > 0}><Trash2 size={16} aria-hidden="true" />Eliminar</button></div>
                    {deleteError && <p className="error publication-error" role="alert">{deleteError}</p>}
                    {selected.encuestas > 0 && <p className="publication-protected">Este periodo contiene encuestas y no se puede eliminar.</p>}
                </section>
            </div>}
        </main>
    </>;
}
