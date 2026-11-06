import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AppHeader } from '../../../components/Interface';

type Eess = { id: number; nombre: string; codigo_renipress: string };
type Usuario = { id: number; dni: string | null; name: string; establecimiento_id: number | null; role: 'administrador' | 'digitador' };

export default function Formulario({ usuario, establecimientos }: { usuario: Usuario | null; establecimientos: Eess[] }) {
    const form = useForm({
        dni: usuario?.dni ?? '',
        name: usuario?.name ?? '',
        establecimiento_id: usuario?.establecimiento_id?.toString() ?? '',
        password: '',
        current_password: '',
    });
    const editando = usuario !== null;
    const administrador = usuario?.role === 'administrador';
    function guardar(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (editando) form.put(`/administracion/usuarios/${usuario.id}`);
        else form.post('/administracion/usuarios');
    }

    return <><AppHeader admin active="usuarios" /><Head title={administrador ? 'Editar administrador' : editando ? 'Editar usuario' : 'Crear usuario'} /><main className="workspace user-management">
        <Link className="detail-back-button" href="/administracion/usuarios">← Volver a usuarios</Link>
        <p className="eyebrow">ADMINISTRACIÓN · {administrador ? 'CUENTA ADMINISTRATIVA' : 'CUENTAS DIGITADORAS'}</p>
        <h1>{administrador ? 'Editar administrador' : editando ? 'Editar usuario' : 'Crear usuario'}</h1>
        <p className="user-form-intro">{administrador ? 'Actualiza los datos de acceso de esta cuenta administrativa. Para cambiar su contraseña, confirma la contraseña actual del administrador que realiza el cambio.' : 'El DNI será el nombre de usuario. Solo podrás asignar establecimientos dentro de tu ámbito administrativo.'}</p>
        <form className="auth-card user-form" onSubmit={guardar}>
            {administrador ? <>
                <div><label htmlFor="admin-name">Nombre completo</label><input id="admin-name" value={form.data.name} onChange={event => form.setData('name', event.target.value)} maxLength={255} autoComplete="name" required />{form.errors.name && <p className="error">{form.errors.name}</p>}</div>
                <div><label htmlFor="admin-dni">DNI / Usuario</label><input id="admin-dni" value={form.data.dni} readOnly /><p className="field-hint">El DNI de la cuenta administrativa no se modifica desde este módulo.</p></div>
                <div><label htmlFor="admin-password">Nueva contraseña (opcional)</label><input id="admin-password" type="password" value={form.data.password} onChange={event => form.setData('password', event.target.value)} minLength={8} maxLength={255} autoComplete="new-password" />{form.errors.password && <p className="error">{form.errors.password}</p>}</div>
                <div><label htmlFor="admin-current-password">Contraseña actual del administrador que confirma</label><input id="admin-current-password" type="password" value={form.data.current_password} onChange={event => form.setData('current_password', event.target.value)} autoComplete="current-password" required={form.data.password.length > 0} />{form.data.password && <p className="field-hint">Se requiere para confirmar el cambio de contraseña.</p>}{form.errors.current_password && <p className="error">{form.errors.current_password}</p>}</div>
            </> : <>
                <div><label htmlFor="user-dni">DNI / Usuario</label><input id="user-dni" value={form.data.dni} onChange={event => form.setData('dni', event.target.value)} inputMode="numeric" maxLength={8} autoComplete="off" required />{form.errors.dni && <p className="error">{form.errors.dni}</p>}</div>
                <div><label htmlFor="user-name">Nombre completo</label><input id="user-name" value={form.data.name} onChange={event => form.setData('name', event.target.value)} maxLength={255} autoComplete="name" required />{form.errors.name && <p className="error">{form.errors.name}</p>}</div>
                <div><label htmlFor="user-establecimiento">Establecimiento</label><select id="user-establecimiento" value={form.data.establecimiento_id} onChange={event => form.setData('establecimiento_id', event.target.value)} required><option value="" disabled>Selecciona un establecimiento</option>{establecimientos.map(eess => <option value={eess.id} key={eess.id}>{eess.codigo_renipress} · {eess.nombre}</option>)}</select>{form.errors.establecimiento_id && <p className="error">{form.errors.establecimiento_id}</p>}</div>
                <div><label htmlFor="user-password">{editando ? 'Nueva contraseña (opcional)' : 'Contraseña inicial'}</label><input id="user-password" type="password" value={form.data.password} onChange={event => form.setData('password', event.target.value)} minLength={8} maxLength={255} autoComplete="new-password" required={!editando} />{editando && <p className="field-hint">Déjala vacía para conservar la contraseña actual.</p>}{form.errors.password && <p className="error">{form.errors.password}</p>}</div>
            </>}
            <div className="actions"><button className="primary" disabled={form.processing}>{form.processing ? 'Guardando…' : editando ? 'Guardar cambios' : 'Crear usuario'}</button><Link className="detail-back-button" href="/administracion/usuarios">Cancelar</Link></div>
        </form>
    </main></>;
}
