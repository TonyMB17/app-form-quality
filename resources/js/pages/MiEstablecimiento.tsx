import { Head, Link } from '@inertiajs/react';
import { AppHeader, ActionCard, Icon } from '../components/Interface';
type Perfil = { nombre: string; dni: string; establecimiento: { codigo: string; nombre: string } };
export default function MiEstablecimiento({ perfil }: { perfil: Perfil }) {
    return <><Head title="Inicio" /><AppHeader /><main className="workspace home-page">
        <div className="welcome-line"><span className="eyebrow">TU ESPACIO DE TRABAJO</span></div>
        <section className="welcome"><h1>La calidad empieza<br />por escuchar.</h1><p>Completa tu encuesta y consulta tus resultados. Podrás modificar tus respuestas mientras esté disponible.</p></section>
        <section className="digitador-profile" aria-label="Datos de tu cuenta">
            <div className="profile-avatar" aria-hidden="true">{perfil.nombre.trim().charAt(0).toLocaleUpperCase()}</div>
            <div className="profile-identity"><span className="profile-label">CUENTA DIGITADORA</span><strong>{perfil.nombre}</strong><span>DNI {perfil.dni}</span></div>
            <div className="profile-establishment"><span className="profile-label">ESTABLECIMIENTO ASIGNADO</span><strong>{perfil.establecimiento.nombre}</strong><span>Código RENIPRESS {perfil.establecimiento.codigo}</span></div>
        </section>
        <section className="home-grid"><div className="start-card"><span className="soft-label">ENCUESTA DE SATISFACCIÓN</span><div className="start-icon"><Icon name="plus" size={32}/></div><h2>Tu experiencia,<br />una mejor atención.</h2><p>Accede a tu única encuesta, revisa el detalle y modifica tus respuestas si el plazo sigue abierto.</p><Link href="/encuestas/nueva" className="primary start-button">Abrir mi encuesta <Icon name="arrow" size={20}/></Link><div className="start-foot"><Icon name="check" size={16}/> Establecimiento y fechas automáticos</div><div className="decor-circle" aria-hidden="true" /></div>
        <div className="home-secondary"><div className="section-caption">TU ENCUESTA</div><ActionCard href="/encuestas" title="Mi encuesta" icon="list">Abre el detalle de tu encuesta y modifica respuestas si sigue disponible.</ActionCard><aside className="help-note"><span>Una respuesta a la vez.</span><p>Si una pregunta no corresponde, elige <strong>No aplica</strong>. Si la persona no desea responder, elige <strong>Sin respuesta</strong>.</p></aside></div></section>
        <section className="journey"><div><span className="eyebrow">UN PROCESO SENCILLO</span><h2>Cuatro pasos. Todo en orden.</h2></div><ol>{['Datos generales','Expectativas','Percepciones','Revisión y envío'].map((s,i) => <li key={s}><span>{String(i+1).padStart(2,'0')}</span>{s}</li>)}</ol></section>
        <footer className="workspace-footer"><span>Red de Salud Abancay · Calidad de atención</span><span>Entorno de prueba</span></footer>
    </main></>;
}
