import { changeAnswer, normalizeAnswer, skipsPerception, perceptionReason } from '../../components/answerDependency';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ArrowLeft, Save } from 'lucide-react';
import { AppHeader, RatingQuestion, Stepper } from '../../components/Interface';
import { SurveyProgress, ValidationSummary } from '../../components/SurveyFeedback';
type Answer = { item: number; expectativa: string | null; percepcion: string | null };
export default function Editar({ encuesta_id, items, respuestas }: { encuesta_id: number; items: { numero: number; dimension: string; expectativa: string | null; percepcion: string | null }[]; respuestas: Answer[] }) {
    const [step, setStep] = useState(0);
    const [localErrors, setLocalErrors] = useState<Record<string,string>>({});
    const form = useForm({ respuestas: items.map(i => { const r = respuestas.find(r => r.item === i.numero); return normalizeAnswer({ item: i.numero, expectativa: r?.expectativa ?? '', percepcion: r?.percepcion ?? '' }); }) });
    const steps = ['Expectativas', 'Percepciones', 'Revisión'];
    const errors: Record<string,string> = { ...form.errors, ...localErrors };
    const completed = form.data.respuestas.filter(r => r.expectativa && r.percepcion).length;
    useEffect(() => {
        if (!form.isDirty) return;
        const unload = (e: BeforeUnloadEvent) => { e.preventDefault(); e.returnValue = ''; };
        window.addEventListener('beforeunload', unload);
        const stop = router.on('before', e => { if (e.detail.visit.method === 'put' && e.detail.visit.url.pathname === `/encuestas/${encuesta_id}`) return; if (!window.confirm('Tienes cambios sin guardar. ¿Deseas salir?')) e.preventDefault(); });
        return () => { window.removeEventListener('beforeunload', unload); stop(); };
    }, [form.isDirty, encuesta_id]);
    function go(next: number, id?: string) { setStep(next); setTimeout(() => { const target = id ? document.getElementById(id) : document.getElementById('edit-stage'); target?.focus({preventScroll:true}); target?.scrollIntoView({block:'start',behavior:'auto'}); }, 0); }
    function focusError(key: string) { go(key.includes('percepcion') ? 1 : 0, key); }
    function errorLabel(key: string) { const match = key.match(/^respuestas\.(\d+)\.(expectativa|percepcion)$/); return match ? `${match[2] === 'expectativa' ? 'Expectativa' : 'Percepción'} · Pregunta ${Number(match[1])+1}` : 'Disponibilidad de la encuesta'; }
    function submit() {
        const missing: Record<string,string> = {};
        form.data.respuestas.forEach((r,i) => { for (const field of ['expectativa','percepcion'] as const) if ((step===2 || step===(field==='expectativa'?0:1)) && !r[field]) missing[`respuestas.${i}.${field}`]='Selecciona una opción.'; });
        setLocalErrors(missing);
        if (Object.keys(missing).length) { focusError(Object.keys(missing)[0]); return; }
        if(step<2) { go(step+1); return; }
        form.put(`/encuestas/${encuesta_id}`, {preserveScroll:true,onError:e=>focusError(Object.keys(e)[0]),onSuccess:()=>form.setDefaults()});
    }
    return <><AppHeader active="encuesta" /><Head title="Modificar respuestas" /><main className="workspace survey-page edit-survey"><Link className="detail-return" href={`/encuestas/${encuesta_id}`}><ArrowLeft size={16} aria-hidden="true" />Volver al detalle</Link><h1>Modificar respuestas</h1><p className="page-description">Revisa tus cambios antes de guardarlos. Se conserva la misma encuesta y su fotografía.</p><Stepper steps={steps} current={step} onSelect={go} /><SurveyProgress current={step} total={steps.length} completed={completed} pairs={items.length} /><ValidationSummary errors={errors} label={errorLabel} onSelect={focusError} /><form noValidate onSubmit={e=>{e.preventDefault();submit();}}><h2 id="edit-stage" tabIndex={-1}>{steps[step]}</h2>
    {step<2 && [...new Set(items.map(i=>i.dimension))].map(d=><fieldset className="auth-card" key={d}><legend>{d}</legend><div className="question-list">{items.filter(i=>i.dimension===d).map(item=>{const index=items.findIndex(i=>i.numero===item.numero);const field=step===0?'expectativa':'percepcion';const key=`respuestas.${index}.${field}`;return <RatingQuestion key={item.numero} id={key} code={`${step===0?'E':'P'}${item.numero}`} text={item[field] ?? 'Enunciado no disponible en la versión anterior'} value={form.data.respuestas[index][field]} disabledReason={field === 'percepcion' && skipsPerception(form.data.respuestas[index].expectativa) ? perceptionReason : undefined} error={errors[key]} onChange={value=>{form.setData('respuestas',form.data.respuestas.map((r,n)=>n===index?changeAnswer(r, field, value):r));setLocalErrors(previous=>{const next={...previous};delete next[key];return next;});}} />;})}</div></fieldset>)}
    {step===2 && <div className="edit-review-list">{items.map((item,index)=><article className="edit-review-row" key={item.numero}><h3>Pregunta {item.numero}<small>{item.dimension}</small></h3>{(['expectativa','percepcion'] as const).map(field=><div key={field}><span>{field==='expectativa'?'Expectativa':'Percepción'}</span><p>{item[field]}</p><button type="button" className="edit-answer" onClick={()=>go(field==='expectativa'?0:1,`respuestas.${index}.${field}`)} aria-label={`Editar ${field} ${item.numero}`}>{field === 'percepcion' && skipsPerception(form.data.respuestas[index].expectativa) ? `No corresponde · Expectativa: ${form.data.respuestas[index].expectativa}` : form.data.respuestas[index][field] || 'Pendiente'} <small>Editar</small></button></div>)}</article>)}</div>}
    <div className="actions survey-actions">{step>0 && <button type="button" onClick={()=>go(step-1)}>Anterior</button>}<button className="primary" disabled={form.processing || (step===2 && !form.isDirty)}>{step===2 && <Save size={17} aria-hidden="true" />}{form.processing?'Guardando…':step===2?'Guardar cambios':'Continuar'}</button></div></form></main></>;
}
