import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { AppHeader, Icon } from '../components/Interface';

export default function SurveySettings({ settings }: { settings: { published: boolean | number; closes_at: string | null } }) {
    const [saved, setSaved] = useState(false);
    const form = useForm({ published: !!settings.published, closes_at: settings.closes_at?.replace(' ', 'T').slice(0, 16) ?? '' });
    const status = form.data.published ? 'Publicado' : 'Cerrado';

    return <><AppHeader admin active="publicacion" /><Head title="Publicación de la encuesta" /><main className="workspace publication-page">
        <p className="eyebrow">ADMINISTRACIÓN · CONFIGURACIÓN GLOBAL</p>
        <div className="publication-heading">
            <div><h1>Publicación de la encuesta</h1><p>Controla cuándo los digitadores pueden enviar o modificar su encuesta.</p></div>
            <span className={`publication-status ${form.data.published ? 'is-published' : 'is-closed'}`}><span aria-hidden="true" />{status}</span>
        </div>
        <div className="publication-layout">
            <form className="auth-card publication-form" onSubmit={event => {
                event.preventDefault();
                form.put('/administracion/encuesta', { onSuccess: () => setSaved(true) });
            }}>
                <section className="publication-field">
                    <span className="publication-step">01</span>
                    <div className="publication-field-content">
                        <label htmlFor="publication">Estado de la encuesta</label>
                        <p id="publication-help">Al cerrar, se bloquean nuevos envíos y las modificaciones de respuestas.</p>
                        <select id="publication" aria-describedby="publication-help" value={form.data.published ? 'published' : 'closed'} onChange={event => {
                            setSaved(false);
                            form.setData('published', event.target.value === 'published');
                        }}>
                            <option value="published">Publicado · Permitir respuestas</option>
                            <option value="closed">Cerrado · Bloquear respuestas</option>
                        </select>
                        {form.errors.published && <p className="error" role="alert">{form.errors.published}</p>}
                    </div>
                </section>
                <section className="publication-field">
                    <span className="publication-step">02</span>
                    <div className="publication-field-content">
                        <label htmlFor="closing">Fecha y hora límite</label>
                        <p id="closing-help">Hora local de Lima (UTC−5). Al vencer, la encuesta se cierra automáticamente aunque el estado sea Publicado.</p>
                        <input id="closing" type="datetime-local" aria-describedby="closing-help" value={form.data.closes_at} onChange={event => {
                            setSaved(false);
                            form.setData('closes_at', event.target.value);
                        }} />
                        {form.errors.closes_at && <p className="error" role="alert">{form.errors.closes_at}</p>}
                    </div>
                </section>
                <div className="publication-actions"><button className="primary" disabled={form.processing}>{form.processing ? 'Guardando…' : 'Guardar configuración'}</button>{saved && <p className="notice success-notice" role="status"><Icon name="check" size={16} /> Configuración guardada correctamente.</p>}</div>
            </form>
            <aside className="publication-guide">
                <span className="publication-guide-icon"><Icon name="check" size={21} /></span>
                <h2>¿Cómo funciona?</h2>
                <ul>
                    <li>Cada cuenta puede enviar una sola encuesta.</li>
                    <li>Mientras esté publicada y dentro del plazo, puede modificar sus respuestas.</li>
                    <li>Sin fecha límite, permanecerá disponible hasta que la cierres aquí.</li>
                </ul>
                <p>Los cambios se aplican de inmediato a todos los establecimientos.</p>
            </aside>
        </div>
    </main></>;
}
