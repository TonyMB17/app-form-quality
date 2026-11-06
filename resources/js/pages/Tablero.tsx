import QuestionCode from '../components/QuestionCode';
import RespondentStats, { type RespondentData } from '../components/RespondentStats';
import ResultsComparison, { type ComparisonRow } from '../components/ResultsComparison';
import ResultsOverview, { StatusBadge } from '../components/ResultsOverview';
import { AppHeader } from '../components/Interface';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
type Totales = { validos: number; satisfechos: number };
type Filtros = { microred?: string; eess?: string | number; version?: string };
type Props = {
    datos_generales: RespondentData;
    comparacion: ComparisonRow[];
    filtros: Filtros;
    catalogo: { id: number; nombre: string; codigo_renipress: string; microred: string }[];
    resumen: Totales & { encuestas: number; establecimientos: number; excluidos: number };
    dimensiones: (Totales & { nombre: string })[];
    items: (Totales & { numero: number; dimension: string; expectativa: string | null; percepcion: string | null; sin_respuesta: number; no_aplica: number; incompletos: number })[];
    encuestas: { data: { id: number; numero: number; fecha: string; establecimiento: string; version_instrumento: string }[]; total: number; current_page: number; last_page: number; next_page_url: string | null; prev_page_url: string | null };
    actualizado: string; es_digitador: boolean;
};
function porcentaje(t: Totales) { return t.validos ? `${(100 * t.satisfechos / t.validos).toFixed(1)} %` : 'Sin pares válidos'; }
export default function Tablero(p: Props) {
    const [tab, setTab] = useState<'general' | 'microredes' | 'establecimientos' | 'usuarios'>('general');
    const tabs = ['general', 'microredes', 'establecimientos', 'usuarios'] as const;
    const [automatico, setAutomatico] = useState(true);
    const [cargando, setCargando] = useState(false);
    const [fallo, setFallo] = useState(false);
    useEffect(() => {
        const start = router.on('start', () => setCargando(true));
        const finish = router.on('finish', () => setCargando(false));
        return () => { start(); finish(); };
    }, []);
    function actualizar() {
        router.reload({ only: ['resumen','dimensiones','items','encuestas','actualizado','comparacion','datos_generales'], onSuccess: () => setFallo(false), onError: () => setFallo(true) });
    }
    useEffect(() => {
        if (!automatico || cargando) return;
        const timer = setInterval(() => { if (!document.hidden) actualizar(); }, 30000);
        return () => clearInterval(timer);
    }, [automatico, cargando]);
    function filtrar(key: keyof Filtros, value: string) {
        const next: Filtros = { ...p.filtros, [key]: value };
        if (key === 'microred') next.eess = '';
        router.get('/tablero', Object.fromEntries(Object.entries(next).filter(([, v]) => v !== '' && v != null)), { preserveState: true, preserveScroll: true });
    }
    return <><AppHeader active="resultados" admin={!p.es_digitador} /><main className="workspace dashboard"><Head title="Tablero de encuestas" />

        <p className="eyebrow">SEGUIMIENTO DE LA ATENCIÓN</p><h1>Resultados</h1><p>Resultados del ámbito autorizado de tu cuenta.</p>
        <p className="notice">Entorno de prueba: incluye las encuestas ficticias de validación. Metodología provisional: satisfecho si P−E ≥ 0; se excluyen pares con No aplica, Sin respuesta o vacíos. Los porcentajes se calculan sobre pares válidos, no sobre personas.</p>
        <section className="auth-card filters" aria-label="Filtros del tablero">
            <div><label htmlFor="microred">Microred</label><select id="microred" value={p.filtros.microred ?? ''} onChange={e => filtrar('microred', e.target.value)}><option value="">Todas las autorizadas</option>{[...new Set(p.catalogo.map(o => o.microred))].map(m => <option key={m}>{m}</option>)}</select></div>
            <div><label htmlFor="eess">Establecimiento</label><select id="eess" value={p.filtros.eess ?? ''} onChange={e => filtrar('eess', e.target.value)}><option value="">Todos los autorizados</option>{p.catalogo.filter(o => !p.filtros.microred || o.microred === p.filtros.microred).map(o => <option value={o.id} key={o.id}>{o.codigo_renipress} · {o.nombre}</option>)}</select></div>
            <div><label htmlFor="version">Instrumento</label><select id="version" value={p.filtros.version ?? ''} onChange={e => filtrar('version', e.target.value)}><option value="">Todas las versiones</option><option value="abancay-2026">Abancay 2026</option><option value="servqual-provisional-v1">Provisional anterior</option></select></div>
            <button className="quiet-button" onClick={() => router.get('/tablero')}>Limpiar filtros</button>
        </section>
        <div className="actions refresh-toolbar"><label><input type="checkbox" checked={automatico} onChange={e => setAutomatico(e.target.checked)} /> Actualizar cada 30 segundos</label><button disabled={cargando} onClick={actualizar}>{cargando ? 'Actualizando…' : 'Actualizar ahora'}</button><span className="refresh-status" role="status">Última actualización: {p.actualizado} · Lima</span></div>
        {fallo && <p role="alert">No se pudo actualizar. Se conservan los últimos datos recibidos.</p>}
        <div className="results-tabs" role="tablist" aria-label="Estadísticas">{tabs.map((t, index) => <button key={t} id={`tab-${t}`} role="tab" aria-selected={tab === t} aria-controls={`panel-${t}`} tabIndex={tab === t ? 0 : -1} onClick={() => setTab(t)} onKeyDown={e => { const next = e.key === 'ArrowRight' ? (index + 1) % tabs.length : e.key === 'ArrowLeft' ? (index + tabs.length - 1) % tabs.length : e.key === 'Home' ? 0 : e.key === 'End' ? tabs.length - 1 : -1; if (next >= 0) { e.preventDefault(); setTab(tabs[next]); document.getElementById(`tab-${tabs[next]}`)?.focus(); } }}>{t === 'general' ? 'General' : t === 'microredes' ? 'Microredes' : t === 'establecimientos' ? 'Establecimientos' : 'Datos generales'}</button>)}</div>
        <div role="tabpanel" id={`panel-${tab}`} aria-labelledby={`tab-${tab}`} tabIndex={0}>
        {tab === 'usuarios' ? <RespondentStats data={p.datos_generales} total={p.resumen.encuestas} /> : tab !== 'general' ? <ResultsComparison key={tab} rows={p.comparacion} mode={tab} questions={p.items} version={p.filtros.version} /> : <>
        <ResultsOverview resumen={p.resumen} dimensiones={p.dimensiones} />
        <section><h2>Resultados por pregunta</h2><p>Los conteos de No aplica, Sin respuesta y vacíos pueden coincidir en un mismo par; no deben sumarse entre sí. Selecciona una versión para consultar sus enunciados.</p><div className="table-wrap"><table><thead><tr><th>Pregunta</th><th>Válidos</th><th>Satisfechos</th><th>Insatisfechos</th><th>Satisfacción</th><th>Semáforo de insatisfacción</th><th>No aplica</th><th>Sin respuesta</th><th>Vacíos anteriores</th></tr></thead><tbody>{p.items.map(i => <tr key={i.numero}><th><QuestionCode question={i} version={p.filtros.version} /></th><td>{i.validos}</td><td>{i.satisfechos}</td><td>{i.validos - i.satisfechos}</td><td>{porcentaje(i)}</td><td><StatusBadge totals={i} /></td><td>{i.no_aplica}</td><td>{i.sin_respuesta}</td><td>{i.incompletos}</td></tr>)}</tbody></table></div></section>
        <section><h2>Encuestas del resultado ({p.encuestas.total})</h2><div className="table-wrap"><table><thead><tr><th>Número</th><th>EESS</th><th>Fecha</th><th>Detalle</th></tr></thead><tbody>{p.encuestas.data.map(e => <tr key={e.id}><td>{e.numero}</td><td>{e.establecimiento}</td><td>{e.fecha.split('-').reverse().join('/')}</td><td><Link href={`/encuestas/${e.id}`}>Ver encuesta {e.numero}</Link></td></tr>)}</tbody></table></div><nav className="actions" aria-label="Paginación">{p.encuestas.prev_page_url && <Link href={p.encuestas.prev_page_url}>Anterior</Link>}<span>Página {p.encuestas.current_page} de {p.encuestas.last_page}</span>{p.encuestas.next_page_url && <Link href={p.encuestas.next_page_url}>Siguiente</Link>}</nav></section>
        </>}
        </div>
    </main></>;
}
