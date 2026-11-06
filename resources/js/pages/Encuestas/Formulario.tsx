import { useSurveyDraft } from '../../components/useSurveyDraft';
import { AppHeader, Stepper, RatingQuestion } from '../../components/Interface';
import { Head, useForm, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Cabecera, campos, etiqueta, type Campo, type Eess } from './comun';
const camposCaptura = campos.filter(c => !['numero', 'fecha', 'hora_inicio', 'hora_final'].includes(c[0]));
type Item = { numero: number; dimension: string; expectativa: string; percepcion: string };
type Datos = Record<Campo, string> & { inicio_token: string; personal_otro: string; respuestas: { item: number; expectativa: string; percepcion: string }[] };
export default function Formulario({ eess, inicio_token, items, draft_key }: { eess: Eess; inicio_token: string | null; items: Item[]; draft_key: string }) {
    const [paso, setPaso] = useState(0);
    const [localErrors, setLocalErrors] = useState<Record<string, string>>({});
    const initial = Object.fromEntries(camposCaptura.map(([key]) => [key, ''])) as Record<Campo, string>;
    const form = useForm<Datos>({ ...initial, personal_otro: '', inicio_token: inicio_token ?? '', respuestas: items.map(i => ({ item: i.numero, expectativa: '', percepcion: '' })) });
    const draft = useSurveyDraft(draft_key, form.data, paso, form.isDirty);
    useEffect(() => {
        if (!form.isDirty || (draft.savedAt && !draft.failed)) return;
        const unload = (event: BeforeUnloadEvent) => { event.preventDefault(); event.returnValue = ''; };
        window.addEventListener('beforeunload', unload);
        const stop = router.on('before', event => {
            const visit = event.detail.visit;
            if (visit.method === 'post' && visit.url.pathname === '/encuestas') return;
            if (!window.confirm('Tienes una encuesta sin enviar. Si sales, perderás las respuestas. ¿Deseas salir?')) event.preventDefault();
        });
        return () => { window.removeEventListener('beforeunload', unload); stop(); };
    }, [form.isDirty, draft.savedAt, draft.failed]);
    function enfocar(key: string) {
        ir(key.includes('expectativa') ? 1 : key.includes('percepcion') ? 2 : 0, key);
    }
    const errors: Record<string, string> = { ...form.errors, ...localErrors };
    const pasos = ['Datos generales', 'Expectativas', 'Percepciones', 'Revisión'];
    const completos = form.data.respuestas.filter(r => r.expectativa !== '' && r.percepcion !== '').length;
    function ir(p: number, id?: string) { setPaso(p); setTimeout(() => { if (id) document.getElementById(id)?.focus(); else window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' }); }, 0); }
    function validar(todo = false) {
        const e: Record<string, string> = {};
        if (paso === 0 || todo) {
            camposCaptura.forEach(([key]) => { if (!form.data[key].trim()) e[key] = 'Completa este campo.'; });
            if (form.data.personal === '6' && !form.data.personal_otro.trim()) e.personal_otro = 'Especifica el personal.';
        }
        form.data.respuestas.forEach((r, i) => {
            for (const field of ['expectativa', 'percepcion'] as const) {
                if ((todo || paso === (field === 'expectativa' ? 1 : 2)) && !r[field]) e[`respuestas.${i}.${field}`] = 'Selecciona una opción.';
            }
        });
        if (form.data.edad !== '' && (!Number.isInteger(Number(form.data.edad)) || Number(form.data.edad) < 0 || Number(form.data.edad) > 100)) e.edad = 'Ingresa una edad de 0 a 100.';
        setLocalErrors(e);
        const first = Object.keys(e)[0];
        if (first) enfocar(first);
        return !first;
    }
    function enviar() {
        if (!validar(true)) return;
        form.post('/encuestas', { preserveScroll: true, onSuccess: () => draft.clear(), onError: errors => { const first = Object.keys(errors)[0]; if (first) enfocar(first); } });
    }
    const error = (key: string) => errors[key] && <p className="error" id={`${key}-error`} role="alert">{errors[key]}</p>;
    return <><AppHeader active="encuesta" /><main className="workspace survey-page"><Head title="Nueva encuesta" /><p className="eyebrow">{eess.codigo_renipress} · {eess.nombre}</p><h1>Nueva encuesta</h1><p className="page-description">Completa cada sección. Podrás revisar todo antes de enviar.</p>
        {draft.pending && <section className="notice" role="alert"><h2>Tienes una encuesta sin enviar</h2><p>Guardada en este navegador: {new Date(draft.pending.savedAt).toLocaleString('es-PE', { timeZone: 'America/Lima' })}.</p><div className="actions"><button className="primary" type="button" onClick={() => { const saved = draft.restore(); if (saved) { form.setData(saved.data); setPaso(saved.step); } }}>Continuar borrador</button><button type="button" onClick={() => { if (window.confirm('¿Descartar el borrador guardado y empezar de nuevo?')) draft.discard(); }}>Descartar y empezar</button></div></section>}
        {draft.failed ? <p role="alert" className="error">No se pudo guardar el borrador en este navegador. Mantén esta página abierta hasta enviar.</p> : draft.savedAt && <p className="notice success-notice" role="status">Borrador guardado en este navegador. Puedes continuar al volver a ingresar con tu cuenta.</p>}
        <p className="draft-help">El borrador se conserva en este dispositivo. No uses datos reales durante las pruebas ni navegadores compartidos. Los borradores no aparecen en los resultados.</p>
        <div inert={!!draft.pending || !draft.ready}>
        <Stepper steps={pasos} current={paso} onSelect={ir} />
        <p aria-live="polite">Paso {paso + 1} de 4 · Pares completos: {completos} · Incompletos: {22 - completos}</p>
        <p className="notice">1 es la menor calificación y 7 la mayor. Todas las preguntas requieren una elección. Usa No aplica cuando no corresponda o Sin respuesta si no desea responder.</p>
        {!inicio_token && <p role="alert">La captura no está habilitada. Contacta al administrador.</p>}
        {Object.keys(errors).length > 0 && <section className="notice" role="alert"><strong>Revisa estos campos. Tus respuestas se conservan.</strong><ul>{Object.entries(errors).map(([key, msg]) => <li key={key}><button type="button" onClick={() => ir(key.includes('expectativa') ? 1 : key.includes('percepcion') ? 2 : 0, key)}>{key}: {msg}</button></li>)}</ul></section>}
        <form noValidate onSubmit={e => { e.preventDefault(); if (paso === 3) enviar(); else if (validar()) ir(paso + 1); }}>
        {paso === 0 && <section className="auth-card form-grid"><h2>Datos generales</h2>
            {camposCaptura.map(([key, label, type]) => <div key={key}><label htmlFor={key}>{label}{['numero', 'fecha'].includes(key) ? ' *' : ''}</label>
                {Array.isArray(type) ? <select required id={key} value={form.data[key]} onChange={e => form.setData(key, e.target.value)}><option value="" disabled>Seleccione una opción</option>{type.map((v, i) => <option key={v} value={i + 1}>{v}</option>)}</select>
                : <input id={key} type={type as string} value={form.data[key]} required min={key === 'numero' ? 1 : key === 'edad' ? 0 : key === 'fecha' ? '2020-01-01' : undefined} max={key === 'numero' ? 1000 : key === 'edad' ? 100 : key === 'fecha' ? '2030-12-31' : undefined} maxLength={255} onChange={e => form.setData(key, e.target.value)} />}{error(key)}</div>)}
        {form.data.personal === '6' && <div><label htmlFor="personal_otro">Otros: especifique</label><input required id="personal_otro" maxLength={255} value={form.data.personal_otro} onChange={e => form.setData('personal_otro', e.target.value)} />{error('personal_otro')}</div>}
        </section>}
        {(paso === 1 || paso === 2) && <section><h2>{pasos[paso]}</h2><p className="notice">{paso === 1 ? "Califique la importancia que otorga a la atención que espera recibir en consulta externa." : "Califique la atención que ha recibido en consulta externa."} Utilice la escala del 1 al 7: 1 es la menor calificación y 7 la mayor.</p>{[...new Set(items.map(i => i.dimension))].map(d => <fieldset className="auth-card" key={d}><legend>{d}</legend><div className="question-list">{items.filter(i => i.dimension === d).map(i => {
            const index = form.data.respuestas.findIndex(r => r.item === i.numero); const field = paso === 1 ? 'expectativa' : 'percepcion'; const key = `respuestas.${index}.${field}`;
            return <RatingQuestion key={i.numero} id={key} code={`${paso === 1 ? 'E' : 'P'}${i.numero}`} text={i[field]} value={form.data.respuestas[index][field]} error={errors[key]} onChange={value => form.setData('respuestas', form.data.respuestas.map((r, n) => n === index ? { ...r, [field]: value } : r))} />;
        })}</div></fieldset>)}</section>}
        {paso === 3 && <section className="auth-card review-panel"><h2>Revisión</h2><p>Comprueba la información antes de enviarla.</p><dl className="review-grid">{camposCaptura.map(c => <div key={c[0]}><dt>{c[1]}</dt><dd>{form.data[c[0]] ? etiqueta(c, form.data[c[0]]) : 'Pendiente de completar'} <button className="edit-button" type="button" aria-label={`Editar ${c[1]}`} onClick={() => ir(0, c[0])}>Editar <span aria-hidden="true">↗</span></button></dd></div>)}</dl>
            {form.data.personal === '6' && <p>Otros: {form.data.personal_otro || 'Sin especificar'} <button type="button" onClick={() => ir(0, 'personal_otro')}>Editar</button></p>}
            <div className="table-wrap review-table"><table><thead><tr><th>Ítem</th><th>Expectativa</th><th>Percepción</th></tr></thead><tbody>{form.data.respuestas.map((r, index) => <tr key={r.item}><th><span className="question-code">{r.item}</span></th><td><p>{items[index].expectativa}</p><button className="review-answer" aria-label={`Editar expectativa ${r.item}: ${r.expectativa || "pendiente"}`} type="button" onClick={() => ir(1, `respuestas.${index}.expectativa`)}>{r.expectativa || 'Pendiente de seleccionar'}</button></td><td><p>{items[index].percepcion}</p><button className="review-answer" aria-label={`Editar percepción ${r.item}: ${r.percepcion || "pendiente"}`} type="button" onClick={() => ir(2, `respuestas.${index}.percepcion`)}>{r.percepcion || 'Pendiente de seleccionar'}</button></td></tr>)}</tbody></table></div></section>}
        <div className="actions survey-actions">{paso > 0 && <button type="button" onClick={() => ir(paso - 1)}>Anterior</button>}<button className="primary" disabled={form.processing || !form.data.inicio_token || items.length !== 22}>{form.processing ? 'Guardando…' : paso === 3 ? 'Enviar encuesta' : 'Continuar'}</button></div>
        </form></div></main></>;
}
