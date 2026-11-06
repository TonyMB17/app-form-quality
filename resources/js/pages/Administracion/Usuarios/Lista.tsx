import { Head, Link } from '@inertiajs/react';
import { AppHeader } from '../../../components/Interface';

type Usuario = {
    id: number;
    name: string;
    dni: string;
    establecimiento: string;
    codigo_renipress: string;
    encuestas_count: number;
};
type Page<T> = { data: T[]; total: number; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null };
type Eess = { id: number; nombre: string; codigo_renipress: string };
type Administrador = { id: number; name: string; dni: string | null };

export default function ListaUsuarios({ usuarios, administradores, establecimientos }: { usuarios: Page<Usuario>; administradores: Administrador[]; establecimientos: Eess[] }) {
    return <><AppHeader admin active="usuarios" /><Head title="Gestión de usuarios" /><main className="workspace user-management">
        <p className="eyebrow">ADMINISTRACIÓN</p>
        <div className="page-heading"><div><h1>Gestión de usuarios</h1><p>Cuentas digitadoras de los establecimientos de tu ámbito autorizado.</p></div><Link className="primary" href="/administracion/usuarios/nuevo">Crear usuario</Link></div>
        <p className="notice user-scope-note">Puedes crear y actualizar cuentas digitadoras. El DNI es el usuario de acceso. Las encuestas existentes se conservan al editar una cuenta.</p>
        <section className="admin-accounts">
            <div className="admin-accounts-heading"><div><h2>Cuentas administrativas</h2><p>Actualiza el nombre o la contraseña de una cuenta administrativa.</p></div><span>{administradores.length} {administradores.length === 1 ? 'administrador' : 'administradores'}</span></div>
            <div className="admin-account-list">{administradores.map(admin => <article className="admin-account-row" key={admin.id}>
                <span className="admin-account-avatar" aria-hidden="true">{admin.name.trim().charAt(0).toLocaleUpperCase()}</span>
                <div className="admin-account-identity"><strong>{admin.name}</strong><span>DNI {admin.dni ?? 'No registrado'}</span></div>
                <Link className="detail-link" href={`/administracion/usuarios/${admin.id}/editar`}>Editar administrador</Link>
            </article>)}</div>
            <p className="admin-account-hint">Para cambiar la contraseña se solicitará la contraseña actual del administrador que confirma el cambio.</p>
        </section>
        <p className="result-count">{usuarios.total} {usuarios.total === 1 ? 'usuario' : 'usuarios'} · {establecimientos.length} establecimientos autorizados</p>
        {usuarios.data.length === 0 ? <section className="empty-state"><h2>No hay usuarios digitadores</h2><p>Crea una cuenta para asignarla a un establecimiento autorizado.</p></section> : <div className="table-wrap user-table"><table><thead><tr><th>Usuario (DNI)</th><th>Nombre</th><th>Establecimiento</th><th>Encuestas</th><th>Acción</th></tr></thead><tbody>{usuarios.data.map(user => <tr key={user.id}><td data-label="Usuario (DNI)"><strong>{user.dni}</strong></td><td data-label="Nombre">{user.name}</td><td data-label="Establecimiento">{user.codigo_renipress} · {user.establecimiento}</td><td data-label="Encuestas">{user.encuestas_count}</td><td data-label="Acción"><Link className="detail-link" href={`/administracion/usuarios/${user.id}/editar`}>Editar cuenta</Link></td></tr>)}</tbody></table></div>}
        <nav className="actions pagination" aria-label="Paginación">{usuarios.prev_page_url && <Link href={usuarios.prev_page_url}>Anterior</Link>}<span>Página {usuarios.current_page} de {usuarios.last_page}</span>{usuarios.next_page_url && <Link href={usuarios.next_page_url}>Siguiente</Link>}</nav>
    </main></>;
}
