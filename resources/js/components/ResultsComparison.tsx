import QuestionCode, { type Question } from './QuestionCode';
import { useState } from 'react';
import { resultStatus, StatusBadge } from './ResultsOverview';
type Item = { numero: number; dimension: string; validos: number; satisfechos: number };
export type ComparisonRow = { id: number; nombre: string; codigo: string; microred: string; encuestas: number; items: Item[] };
export default function ResultsComparison({ rows, mode, questions, version }: { questions: Question[]; version?: string; rows: ComparisonRow[]; mode: 'microredes' | 'establecimientos' }) {
    const [detail, setDetail] = useState('dimensiones');
    const [search, setSearch] = useState('');
    const grouped = mode === 'establecimientos' ? rows.map(r => ({ ...r, nombre: `${r.codigo} · ${r.nombre}` })) : Object.entries(rows.reduce<Record<string, ComparisonRow[]>>((groups, r) => { const key = r.microred || 'Sin microred'; (groups[key] ??= []).push(r); return groups; }, {})).map(([nombre, members]) => {
        const group = members!;
        return { nombre, microred: nombre, encuestas: group.reduce((n, r) => n + r.encuestas, 0), items: group[0].items.map(i => ({ ...i, validos: group.reduce((n, r) => n + (r.items.find(x => x.numero === i.numero)?.validos ?? 0), 0), satisfechos: group.reduce((n, r) => n + (r.items.find(x => x.numero === i.numero)?.satisfechos ?? 0), 0) })) };
    });
    const columns = detail === 'dimensiones' ? [...new Set(rows[0]?.items.map(i => i.dimension) ?? [])] : (rows[0]?.items.map(i => `Ítem ${i.numero}`) ?? []);
    const visible = grouped.filter(r => `${r.nombre} ${r.microred}`.toLocaleLowerCase().includes(search.toLocaleLowerCase()));
    const totals = (items: Item[]) => items.reduce((a, i) => ({ validos: a.validos + i.validos, satisfechos: a.satisfechos + i.satisfechos }), { validos: 0, satisfechos: 0 });
    const rate = (t: ReturnType<typeof totals>) => t.validos ? `${(100 * (t.validos - t.satisfechos) / t.validos).toFixed(1)} %` : '—';
    return <section><div className="results-heading"><div><p className="eyebrow">COMPARACIÓN TERRITORIAL</p><h2>Estadísticas de {mode}</h2><p>Insatisfacción por dimensión o pregunta. Los porcentajes se ponderan por pares válidos, no se promedian entre establecimientos.</p></div></div>
        <div className={`comparison-controls ${mode === 'microredes' ? 'microred-controls' : ''}`}><div><label htmlFor="comparison-detail">Mostrar</label><select id="comparison-detail" value={detail} onChange={e => setDetail(e.target.value)}><option value="dimensiones">Dimensiones</option><option value="preguntas">Preguntas (22 ítems)</option></select></div>{mode === 'establecimientos' && <label>Buscar establecimientos<input type="search" value={search} onChange={e => setSearch(e.target.value)} placeholder="Nombre o código" /></label>}</div>
        <p className="comparison-key">Insatisfacción: verde &lt;40 % · ámbar 40–60 % · rojo &gt;60 %. Gris: sin pares válidos. Cada tarjeta muestra insatisfechos / válidos.</p>
        <p className="comparison-count" role="status">{visible.length} {mode} · {detail === 'dimensiones' ? 'Comparación de dimensiones' : 'Comparación de preguntas'}</p>
        <div className="comparison-results">{visible.map(r => { const total = totals(r.items); return <article className="territory-card" key={r.nombre}>
            <header className="territory-heading"><div><h3>{r.nombre}</h3>{mode === 'establecimientos' && <p>Microred: {r.microred}</p>}<span className="territory-count">{r.encuestas} encuestas</span></div><div className="territory-total"><span>Insatisfacción general</span><strong>{rate(total)}</strong><StatusBadge totals={total} /><small>{total.validos - total.satisfechos} / {total.validos} pares válidos</small></div></header>
            <div className="territory-grid">{columns.map((c, index) => { const selected = r.items.filter(i => detail === 'dimensiones' ? i.dimension === c : i.numero === index + 1); const t = totals(selected); const status = resultStatus(t); return <div key={c} className={`comparison-cell ${status.tone}`}><h4>{detail === 'preguntas' ? <QuestionCode question={questions.find(q => q.numero === index + 1)} version={version} /> : c}</h4><strong>{rate(t)}</strong><small>Insatisfacción · {status.label}</small><div className="comparison-meter" aria-hidden="true"><span style={{width: `${t.validos ? 100 * (t.validos - t.satisfechos) / t.validos : 0}%`}} /></div><small>{t.validos - t.satisfechos} insatisfechos / {t.validos} pares válidos</small></div>; })}</div>
        </article>; })}</div>
        {!visible.length && <p className="notice">No hay {mode} que coincidan con los filtros y la búsqueda.</p>}
        <p className="chart-footnote">Solo se muestran establecimientos autorizados. Las unidades sin encuestas permanecen visibles con estado neutral. La clasificación es provisional.</p>
    </section>;
}
