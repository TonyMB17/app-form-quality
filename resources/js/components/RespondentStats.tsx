type Bucket = { nombre: string; total: number };
export type RespondentData = { distribuciones: Record<string, Bucket[]>; microredes: Bucket[]; cuentas: { id: number; nombre: string; establecimiento: string; microred: string; encuestas: number }[] };

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
    const cuentas = [...data.cuentas].sort((a, b) => b.encuestas - a.encuestas || a.nombre.localeCompare(b.nombre, 'es'));
    return <section className="respondent-stats">
        <div className="results-heading"><div><p className="eyebrow">DATOS GENERALES</p><h2>Perfil de las personas encuestadas</h2><p>{total} encuestas en los filtros seleccionados · distribución sobre el total, no sobre personas únicas.</p></div></div>
        <div className="demographic-grid">{charts.map(([title, rows], chartIndex) => {
            const chartTotal = rows.reduce((sum, row) => sum + row.total, 0);
            return <article className="chart-card demographic-card" key={title}>
                <header className="demographic-heading"><div><h3>{title}</h3><span>{chartTotal} respuestas</span></div><span className="demographic-icon" aria-hidden="true">{String(chartIndex + 1).padStart(2, '0')}</span></header>
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

        <div className="accounts-heading"><div><p className="eyebrow">ACTIVIDAD DE DIGITACIÓN</p><h2>Encuestas por digitador</h2><p>Ordenados de mayor a menor cantidad de encuestas enviadas.</p></div><span>{cuentas.length} cuentas</span></div>
        {!cuentas.length
            ? <p className="notice">No hay cuentas digitadoras en este ámbito.</p>
            : <ol className="account-ranking">{cuentas.map((cuenta, index) => <li className="account-ranking-row" key={cuenta.id}>
                <span className={`account-rank ${index < 3 && cuenta.encuestas > 0 ? 'top-rank' : ''}`}>{index + 1}</span>
                <div className="account-identity"><strong>{cuenta.nombre}</strong><span>{cuenta.establecimiento} · Microred {cuenta.microred}</span></div>
                <strong className="account-total">{cuenta.encuestas}<small>{cuenta.encuestas === 1 ? 'encuesta' : 'encuestas'}</small></strong>
            </li>)}</ol>}
        <p className="account-footnote">Las cuentas digitadoras registran encuestas; no representan a las personas encuestadas ni a un padrón de personas pendientes.</p>
    </section>;
}
