import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Target, Check, Save, Search } from 'lucide-react';
import { AppHeader } from '../../components/Interface';

type Establishment = { id:number; nombre:string; codigo_renipress:string; microred:string|null; meta_encuestas:number; digitadores:{id:number;name:string;realizadas:number}[] };
function TargetCard({ establishment:e, hasPeriod }: { establishment:Establishment;hasPeriod:boolean }) {
    const form = useForm({meta_encuestas:String(e.meta_encuestas)});
    const [saved,setSaved] = useState(false);
    const achieved = e.digitadores.filter(u=>Number(u.realizadas)>=e.meta_encuestas).length;
    return <article className="auth-card target-card"><header><div><p className="eyebrow">{e.codigo_renipress} · {e.microred || 'Sin microred'}</p><h2>{e.nombre}</h2></div><Target size={24} aria-hidden="true"/></header>
        <form onSubmit={event=>{event.preventDefault();setSaved(false);form.put(`/administracion/metas/${e.id}`,{preserveScroll:true,onSuccess:()=>{form.setDefaults();setSaved(true);}});}}>
            <div><label htmlFor={`target-${e.id}`}>Encuestas por digitador</label><input id={`target-${e.id}`} type="number" inputMode="numeric" min={1} max={100000} required value={form.data.meta_encuestas} onChange={event=>{form.setData('meta_encuestas',event.target.value);setSaved(false);}} aria-invalid={!!form.errors.meta_encuestas} aria-describedby={form.errors.meta_encuestas?`target-error-${e.id}`:undefined}/></div>
            <button className="primary" disabled={form.processing || !form.isDirty}><Save size={17} aria-hidden="true"/>{form.processing?'Guardando…':'Guardar meta'}</button>
            {form.errors.meta_encuestas && <p id={`target-error-${e.id}`} className="error" role="alert">{form.errors.meta_encuestas}</p>}
            {saved && <p className="target-saved" role="status"><Check size={16} aria-hidden="true"/>Meta guardada</p>}
        </form>
        <p className="target-summary">{e.digitadores.length} {e.digitadores.length === 1 ? 'digitador' : 'digitadores'} · Objetivo conjunto: {e.digitadores.length * e.meta_encuestas} encuestas por periodo.</p>
        {hasPeriod && e.digitadores.length>0 && <p>{achieved} de {e.digitadores.length} {e.digitadores.length === 1 ? 'digitador alcanzó' : 'digitadores alcanzaron'} su meta.</p>}
        <details><summary>Ver avance individual ({e.digitadores.length})</summary>{!e.digitadores.length?<p className="field-hint">Todavía no hay digitadores asignados a este establecimiento.</p>:!hasPeriod?<p className="field-hint">Crea un periodo para consultar el avance.</p>:<ul className="target-users">{e.digitadores.map(u=><li key={u.id}><div><strong>{u.name}</strong><span className={`result-badge ${Number(u.realizadas)>=e.meta_encuestas?'success':'neutral'}`}>{Number(u.realizadas)>=e.meta_encuestas?'Meta alcanzada':`${Math.max(0,e.meta_encuestas-Number(u.realizadas))} pendientes`}</span></div><progress value={Math.min(Number(u.realizadas),e.meta_encuestas)} max={e.meta_encuestas} aria-label={`Avance de ${u.name}: ${u.realizadas} de ${e.meta_encuestas}`}/><span>{u.realizadas} / {e.meta_encuestas} encuestas</span></li>)}</ul>}</details>
    </article>;
}
export default function Metas({establecimientos,periodos,periodo_id}:{establecimientos:Establishment[];periodos:{id:number;nombre:string}[];periodo_id:number|null}) {
    const [search,setSearch]=useState('');
    const filtered=establecimientos.filter(e=>`${e.nombre} ${e.codigo_renipress} ${e.microred??''}`.toLocaleLowerCase('es').includes(search.toLocaleLowerCase('es').trim()));
    return <><Head title="Metas por establecimiento"/><AppHeader admin active="metas"/><main className="workspace"><p className="eyebrow">ORGANIZACIÓN DE LA CAPTURA</p><h1>Metas por establecimiento</h1><p className="page-description">Define cuántas encuestas debe registrar cada digitador por periodo. La meta inicial es 5 y no limita nuevos registros.</p><p className="notice">La meta es compartida por los digitadores de un establecimiento. Cambiarla actualiza el objetivo de todos. El periodo seleccionado solo filtra el avance; la meta configurada se mantiene para los siguientes periodos.</p><section className="auth-card target-filters" aria-label="Filtros de metas"><div><label htmlFor="target-search"><Search size={16} aria-hidden="true"/>Buscar establecimiento</label><input id="target-search" type="search" placeholder="Nombre, código o microred" value={search} onChange={e=>setSearch(e.target.value)}/></div><div><label htmlFor="target-period">Periodo del avance</label><select id="target-period" value={periodo_id??''} disabled={!periodos.length} onChange={e=>router.get('/administracion/metas',{periodo:e.target.value},{preserveScroll:true})}>{!periodos.length && <option value="">Sin periodos</option>}{periodos.map(p=><option key={p.id} value={p.id}>{p.nombre}</option>)}</select></div></section><p role="status">{filtered.length} establecimientos</p><div className="target-grid">{filtered.map(e=><TargetCard key={e.id} establishment={e} hasPeriod={periodo_id!==null}/>)}</div>{!filtered.length && <section className="empty-state"><h2>No hay coincidencias</h2><p>Prueba otro nombre, código o microred.</p></section>}</main></>;
}
