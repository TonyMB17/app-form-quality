type Totals = { validos: number; satisfechos: number };
export function resultStatus(t: Totals) {
    if (!t.validos) return { tone: 'neutral', label: 'Sin datos válidos' };
    const value = 100 * (t.validos - t.satisfechos) / t.validos;
    return value > 60 ? { tone: 'danger', label: 'Por mejorar' } : value >= 40 ? { tone: 'warning', label: 'En proceso' } : { tone: 'success', label: 'Aceptable' };
}
const percent = (value: number, total: number) => total ? `${(100 * value / total).toFixed(1)} %` : '—';
export function StatusBadge({ totals }: { totals: Totals }) {
    const status = resultStatus(totals);
    return <span className={`result-badge ${status.tone}`}><span aria-hidden="true">●</span> {status.label}</span>;
}
function Donut({ value, total, label, color }: { value: number; total: number; label: string; color: string }) {
    const amount = total ? 100 * value / total : 0;
    return <div className="result-donut"><svg viewBox="0 0 120 120" role="img" aria-label={`${label}: ${percent(value, total)}; ${value} de ${total} pares`}><circle cx="60" cy="60" r="48" fill="none" stroke="#ede9f0" strokeWidth="12" /><circle cx="60" cy="60" r="48" fill="none" stroke={color} strokeWidth="12" pathLength="100" strokeDasharray={`${amount} ${100 - amount}`} transform="rotate(-90 60 60)" /></svg><div><strong>{percent(value, total)}</strong><span>{label}</span></div></div>;
}
export default function ResultsOverview({ resumen: r, dimensiones }: { resumen: Totals & { encuestas: number; establecimientos: number }; dimensiones: (Totals & { nombre: string })[] }) {
    return <>
        <section className="results-overview" aria-labelledby="general-title">
            <div className="results-heading"><div><p className="eyebrow">VISTA GENERAL</p><h2 id="general-title">Resultados generales</h2><p>Resumen de todas las encuestas que coinciden con los filtros.</p></div></div>
            <div className="results-summary-grid">
                <div className="results-kpis"><article><span>Encuestas enviadas</span><strong>{r.encuestas}</strong><small>Registros incluidos en el análisis</small></article><article><span>Establecimientos con encuestas</span><strong>{r.establecimientos}</strong><small>Dentro del ámbito seleccionado</small></article></div>
                <div className="results-charts"><article className="chart-card"><h3>Satisfacción general</h3><p>La percepción alcanza o supera la expectativa.</p><Donut value={r.satisfechos} total={r.validos} label="Satisfacción" color="#278160" /><div className="chart-legend"><span><i className="legend-green" />Respuestas satisfechas <b>{r.satisfechos}</b></span></div><p className="chart-footnote">Base: {r.validos} pares válidos de expectativa y percepción.</p></article></div>
            </div>
            {!r.validos && <p className="notice">No hay pares válidos para calcular porcentajes. Ajusta los filtros o registra encuestas con respuestas numéricas.</p>}
        </section>
        <section aria-labelledby="dimensions-title"><div className="results-heading"><div><p className="eyebrow">ÁREAS DE ATENCIÓN</p><h2 id="dimensions-title">Resultados por dimensión</h2><p>Compara la insatisfacción para identificar dónde concentrar las mejoras.</p></div></div><div className="dimension-cards">{dimensiones.map(d => <article className={`dimension-card ${resultStatus(d).tone}`} key={d.nombre}><h3>{d.nombre}</h3><StatusBadge totals={d} /><strong className="dimension-value">{percent(d.validos - d.satisfechos, d.validos)}</strong><p>Insatisfacción</p><div className="dimension-track" role="img" aria-label={`${d.nombre}: ${percent(d.validos - d.satisfechos, d.validos)} de insatisfacción`}><span style={{ width: `${d.validos ? 100 * (d.validos - d.satisfechos) / d.validos : 0}%` }} /></div><small>{d.validos - d.satisfechos} insatisfechos de {d.validos} pares válidos</small><div className="dimension-satisfaction">Satisfacción <b>{percent(d.satisfechos, d.validos)}</b></div></article>)}</div></section>
    </>;
}
