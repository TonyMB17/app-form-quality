import { ChevronDown } from 'lucide-react';

type Bucket = { nombre: string; total: number };
type Account = { id: number; nombre: string; establecimiento: string; microred: string; encuestas: number; realizadas: number; meta: number };
export type RespondentData = { distribuciones: Record<string, Bucket[]>; microredes: Bucket[]; cuentas: Account[]; periodo_meta: { id: number; nombre: string } | null };

const nombres: Record<string, string> = {
    condicion: 'Condición del encuestado',
    sexo: 'Sexo',
    estudios: 'Nivel de estudios',
    seguro: 'Tipo de seguro',
};

const colores = ['#7650a9', '#9270bd', '#b08bd0', '#547f71', '#c18a42'];

export default function RespondentStats({ data, total }: { data: RespondentData; total: number }) {
    const charts: [string, Bucket[]][] = [
        ['Microred de atención', data.microredes],
        ...Object.entries(data.distribuciones).map(([key, rows]) => [nombres[key] ?? key, rows] as [string, Bucket[]]),
    ];
    const progress = (cuenta: Account) => cuenta.meta ? 100 * cuenta.realizadas / cuenta.meta : 0;
    const cuentas = [...data.cuentas].sort((a, b) => progress(a) - progress(b) || a.nombre.localeCompare(b.nombre, 'es'));
    const reached = cuentas.filter(cuenta => cuenta.realizadas >= cuenta.meta).length;
    const progressing = cuentas.filter(cuenta => cuenta.realizadas > 0 && cuenta.realizadas < cuenta.meta).length;
    const idle = cuentas.filter(cuenta => cuenta.realizadas === 0).length;
    return <section className="respondent-stats">
        <div className="accounts-heading"><div><p className="eyebrow">Actividad de digitación</p><h2>Avance de metas por digitador</h2><p>{data.periodo_meta ? `Avance en ${data.periodo_meta.nombre}. Quienes necesitan atención aparecen primero.` : 'Crea un periodo para comenzar a medir el avance.'}</p></div><span>{data.periodo_meta?.nombre ?? 'Sin periodo'}</span></div>
        {data.periodo_meta && <div className="goal-summary" aria-label="Resumen del avance de metas"><div className="is-reached"><strong>{reached}</strong><span>Meta alcanzada</span></div><div className="is-progress"><strong>{progressing}</strong><span>En curso</span></div><div className="is-idle"><strong>{idle}</strong><span>Sin avance</span></div></div>}
        {!cuentas.length
            ? <p className="notice">No hay cuentas digitadoras en este ámbito.</p>
            : <ol className="account-ranking account-goal-list">{cuentas.map((cuenta, index) => {
                const percentage = Math.round(progress(cuenta));
                const capped = Math.min(100, percentage);
                const status = !data.periodo_meta ? 'unavailable' : cuenta.realizadas >= cuenta.meta ? 'reached' : cuenta.realizadas > 0 ? 'progress' : 'idle';
                const label = status === 'reached' ? 'Meta alcanzada' : status === 'progress' ? `${cuenta.meta - cuenta.realizadas} pendientes` : status === 'idle' ? 'Sin avance' : 'Sin periodo';
                return <li className={`account-ranking-row account-progress-row is-${status}`} key={cuenta.id}>
                    <span className="account-rank">{index + 1}</span>
                    <div className="account-identity"><strong>{cuenta.nombre}</strong><span>{cuenta.establecimiento} · Microred {cuenta.microred}</span><small>{cuenta.encuestas} {cuenta.encuestas === 1 ? 'encuesta en los filtros' : 'encuestas en los filtros'}</small></div>
                    <div className="account-goal"><div className="account-goal-heading"><span>Avance de meta</span><strong>{cuenta.realizadas}<small> / {cuenta.meta}</small></strong></div><div className="account-goal-track" role="progressbar" aria-label={`Avance de ${cuenta.nombre}: ${cuenta.realizadas} de ${cuenta.meta} encuestas`} aria-valuemin={0} aria-valuemax={cuenta.meta} aria-valuenow={Math.min(cuenta.realizadas, cuenta.meta)}><span style={{width: `${capped}%`}} /></div><div className="account-goal-status"><span>{label}</span><strong>{percentage} %</strong></div></div>
                </li>;
            })}</ol>}
        <p className="account-footnote">El avance usa solo las encuestas del periodo indicado. El total de cada cuenta respeta los filtros generales del tablero.</p>
        <details className="respondent-profile"><summary><span><strong>Perfil de las personas encuestadas</strong><small>{total} encuestas · {data.microredes.length} microredes</small></span><ChevronDown className="profile-disclosure" size={20} aria-hidden="true" /></summary><div className="respondent-profile-body">        <div className="results-heading"><div><p>{total} encuestas en los filtros seleccionados · distribución sobre el total, no sobre personas únicas.</p></div></div>
        <div className="respondent-summary" aria-label="Resumen de los datos generales"><div><strong>{total}</strong><span>Encuestas analizadas</span></div><div><strong>{data.microredes.length}</strong><span>Microredes con registros</span></div><div><strong>{cuentas.length}</strong><span>Digitadores visibles</span></div></div>
        <div className="demographic-grid">{charts.map(([title, rows]) => {
            const chartTotal = rows.reduce((sum, row) => sum + row.total, 0);
            return <article className="chart-card demographic-card" key={title}>
                <header className="demographic-heading"><div><h3>{title}</h3><span>Distribución de respuestas</span></div><strong className="demographic-total">{chartTotal}</strong></header>
                {!rows.length
                    ? <p className="demographic-empty">Sin encuestas para estos filtros.</p>
                    : <div className="demographic-rows">{rows.map((row, rowIndex) => {
                        const percent = total ? 100 * row.total / total : 0;
                        return <div className="demographic-row" key={row.nombre}>
                            <div className="demographic-label"><span>{row.nombre}</span><strong>{row.total}<small>{percent.toFixed(1)} %</small></strong></div>
                            <div className="demographic-track" role="img" aria-label={`${row.nombre}: ${row.total} encuestas, ${percent.toFixed(1)} por ciento`}><span style={{ width: `${Math.min(100, percent)}%`, background: colores[rowIndex % colores.length] }} /></div>
                        </div>;
                    })}</div>}
            </article>;
        })}</div>

</div></details>
    </section>;
}
