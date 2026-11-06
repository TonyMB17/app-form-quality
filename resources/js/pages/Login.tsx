import { Head, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { Icon } from '../components/Interface';

export default function Login() {
    const [visible, setVisible] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ dni: '', password: '' });
    function submit(event: FormEvent) {
        event.preventDefault();
        post('/ingresar', { onFinish: () => reset('password') });
    }
    return <main className="login-page"><aside className="login-intro"><span className="logo-symbol"><Icon name="plus" /></span><p className="eyebrow">RED DE SALUD ABANCAY</p><h1>Calidad de atención</h1><p>Registra encuestas y consulta la experiencia de atención de tu establecimiento.</p><span className="login-caption">Escuchar para mejorar.</span></aside><section className="login-content">
        <Head title="Ingresar" />
        <p className="eyebrow">ACCESO DEL PERSONAL</p>
        <h2>Bienvenido de nuevo</h2>
        <p>Ingresa para trabajar con tu establecimiento asignado.</p>
        <form className="auth-card" onSubmit={submit}>
            <label htmlFor="dni">DNI</label>
            <input id="dni" name="dni" inputMode="numeric" autoComplete="username" maxLength={8} pattern="[0-9]{8}" required value={data.dni} onChange={e => setData('dni', e.target.value)} aria-invalid={!!errors.dni} aria-describedby={errors.dni ? 'dni-error' : undefined} />
            {errors.dni && <p id="dni-error" className="error" role="alert">{errors.dni}</p>}
            <label htmlFor="password">Contraseña</label>
            <div className="password-field"><input id="password" name="password" type={visible ? "text" : "password"} autoComplete="current-password" required value={data.password} onChange={e => setData('password', e.target.value)} aria-invalid={!!errors.password} aria-describedby={errors.password ? 'password-error' : undefined} />
            <button type="button" className="password-toggle" aria-label={visible ? "Ocultar contraseña" : "Mostrar contraseña"} aria-pressed={visible} onClick={() => setVisible(!visible)}>{visible ? "Ocultar" : "Mostrar"}</button></div>
            {errors.password && <p id="password-error" className="error" role="alert">{errors.password}</p>}
            <button className="primary" disabled={processing}>{processing ? 'Ingresando…' : 'Ingresar'}</button>
        </form>
    <p className="login-help">Si necesitas ayuda para acceder, contacta al administrador de tu establecimiento.</p></section></main>;
}
