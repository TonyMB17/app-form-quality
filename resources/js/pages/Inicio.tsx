import { Head, Link } from '@inertiajs/react';

export default function Inicio() {
    return (
        <>
            <Head title="Inicio" />
            <main className="shell">
                <header className="brand"><span className="brand-mark" aria-hidden="true">+</span> Calidad de atención <span className="badge">En desarrollo</span></header>
                <section className="intro" aria-labelledby="page-title">
                    <p className="eyebrow">CONSULTA EXTERNA · PRIMER NIVEL DE ATENCIÓN</p>
                    <h1 id="page-title">Encuestas de satisfacción</h1>
                    <p className="lead">Un espacio para registrar la experiencia de atención y consultar los resultados de cada establecimiento de salud.</p>
                    <Link className="primary" href="/ingresar">Ingresar al aplicativo</Link>
                    <div className="notice"><strong>Registro de encuestas habilitado.</strong><p>Ingresa con tu cuenta para registrar y consultar las encuestas de tu establecimiento.</p></div>
                </section>
                <section className="steps" aria-label="Etapas de la encuesta">
                    {[
                        ['01', 'Datos generales', 'Información de la encuesta y de la atención recibida.'],
                        ['02', 'Expectativas', 'Lo que la persona espera del servicio de salud.'],
                        ['03', 'Percepciones', 'Su experiencia durante la atención.'],
                        ['04', 'Revisión', 'Verificación de las respuestas antes del envío.'],
                    ].map(([number, title, description]) => (
                        <article key={number}><span className="step-number">{number}</span><h2>{title}</h2><p>{description}</p></article>
                    ))}
                </section>
                <footer>SERVQUAL modificado · Entorno de desarrollo con datos ficticios</footer>
            </main>
        </>
    );
}
