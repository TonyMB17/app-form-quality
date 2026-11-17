import { Head, Link, useForm } from '@inertiajs/react';
import { AdminPageHeader, AdminSectionHeading, AppHeader, Pagination } from '../../../components/Interface';

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

export default function ListaUsuarios({ usuarios, administradores, establecimientos, filtros }: { filtros: {buscar?:string;eess?:number}; usuarios: Page<Usuario>; administradores: Administrador[]; establecimientos: Eess[] }) {
    const form = useForm({buscar:filtros.buscar ?? '',eess:filtros.eess ?? ''});
    return <><AppHeader admin active="usuarios" /><Head title="Gestión de usuarios" /><main className="workspace admin-view user-management">
        <AdminPageHeader context="Administración de accesos" title="Gestión de usuarios" description="Cuentas digitadoras de los establecimientos de tu ámbito autorizado." actions={<Link className="primary" href="/administracion/usuarios/nuevo">Crear usuario</Link>} />
        <p className="notice user-scope-note">Puedes crear y actualizar cuentas digitadoras. El DNI es el usuario de acceso. Las encuestas existentes se conservan al editar una cuenta.</p>
        <section className="admin-accounts">
            <AdminSectionHeading title="Cuentas administrativas" description="Actualiza el nombre o la contraseña de una cuenta administrativa." aside={<span className="admin-count-badge">{administradores.length} {administradores.length === 1 ? 'administrador' : 'administradores'}</span>} />
            <div className="admin-account-list">{administradores.map(admin => <article className="admin-account-row" key={admin.id}>
                <span className="admin-account-avatar" aria-hidden="true">{admin.name.trim().charAt(0).toLocaleUpperCase()}</span>
                <div className="admin-account-identity"><strong>{admin.name}</strong><span>DNI {admin.dni ?? 'No registrado'}</span></div>
                <Link className="detail-link" href={`/administracion/usuarios/${admin.id}/editar`}>Editar administrador</Link>
            </article>)}</div>
            <p className="admin-account-hint">Para cambiar la contraseña se solicitará la contraseña actual del administrador que confirma el cambio.</p>
        </section>
        <form className="auth-card account-filters" onSubmit={e=>{e.preventDefault();form.get('/administracion/usuarios',{preserveState:true});}}><div><label htmlFor="user-search">Nombre o DNI</label><input type="search" id="user-search" placeholder="Buscar digitador" value={form.data.buscar} onChange={e=>form.setData('buscar',e.target.value)}/></div><div><label htmlFor="user-eess">Establecimiento</label><select id="user-eess" value={form.data.eess} onChange={e=>form.setData('eess',e.target.value)}><option value="">Todos los autorizados</option>{establecimientos.map(e=><option key={e.id} value={e.id}>{e.codigo_renipress} · {e.nombre}</option>)}</select></div><div className="filter-actions"><button className="primary" disabled={form.processing}>Buscar</button><Link href="/administracion/usuarios">Limpiar</Link></div>{Object.values(form.errors).map(e=><p className="error" role="alert" key={e}>{e}</p>)}</form>
        <p className="result-count">{usuarios.total} {usuarios.total === 1 ? 'usuario' : 'usuarios'} · {establecimientos.length} establecimientos autorizados</p>
        {usuarios.data.length === 0 ? <section className="empty-state"><h2>{filtros.buscar || filtros.eess ? "No hay coincidencias" : "No hay usuarios digitadores"}</h2><p>{filtros.buscar || filtros.eess ? "Prueba otro nombre, DNI o establecimiento." : "Crea una cuenta para asignarla a un establecimiento autorizado."}</p><Link className="detail-link" href={filtros.buscar || filtros.eess ? "/administracion/usuarios" : "/administracion/usuarios/nuevo"}>{filtros.buscar || filtros.eess ? "Limpiar filtros" : "Crear usuario"}</Link></section> : <div className="table-wrap user-table"><table><thead><tr><th>Usuario (DNI)</th><th>Nombre</th><th>Establecimiento</th><th>Encuestas</th><th>Acción</th></tr></thead><tbody>{usuarios.data.map(user => <tr key={user.id}><td data-label="Usuario (DNI)"><strong>{user.dni}</strong></td><td data-label="Nombre">{user.name}</td><td data-label="Establecimiento">{user.codigo_renipress} · {user.establecimiento}</td><td data-label="Encuestas">{user.encuestas_count}</td><td data-label="Acción"><Link className="detail-link" href={`/administracion/usuarios/${user.id}/editar`}>Editar cuenta</Link></td></tr>)}</tbody></table></div>}
        <Pagination currentPage={usuarios.current_page} lastPage={usuarios.last_page} previous={usuarios.prev_page_url} next={usuarios.next_page_url} />
    </main></>;
}
