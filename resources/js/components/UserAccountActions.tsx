import { useEffect, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Power, Trash2, X } from 'lucide-react';

type Account = { id: number; name: string; dni: string; activo: boolean; encuestas_count: number };

export default function UserAccountActions({ user }: { user: Account }) {
    const [action, setAction] = useState<'state' | 'delete' | null>(null);
    return <>
        <button type="button" className={`account-action-button ${user.activo ? 'account-action-neutral' : 'account-action-reactivate'}`} aria-label={`${user.activo ? 'Desactivar' : 'Reactivar'} a ${user.name}`} onClick={() => setAction('state')}><Power size={16} aria-hidden="true" />{user.activo ? 'Desactivar' : 'Reactivar'}</button>
        <button type="button" className="account-action-button account-action-delete" aria-label={`Eliminar a ${user.name}`} onClick={() => setAction('delete')}><Trash2 size={16} aria-hidden="true" />Eliminar</button>
        {action && <AccountDialog user={user} action={action} onClose={() => setAction(null)} />}
    </>;
}

function AccountDialog({ user, action, onClose }: { user: Account; action: 'state' | 'delete'; onClose: () => void }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const input = useRef<HTMLInputElement>(null);
    const [step, setStep] = useState(1);
    const form = useForm({ activo: !user.activo, confirmado: false, confirmacion: '', encuestas_count: user.encuestas_count });
    useEffect(() => {
        const previous = document.activeElement as HTMLElement | null;
        const overflow = document.body.style.overflow;
        dialog.current?.showModal();
        document.body.style.overflow = 'hidden';
        return () => { document.body.style.overflow = overflow; previous?.focus(); };
    }, []);
    useEffect(() => { if (step === 2) input.current?.focus(); }, [step]);
    const deleting = action === 'delete';
    const phrase = `ELIMINAR ${user.dni}`;
    function submit() {
        if (deleting && step === 1) { form.setData('confirmado', true); setStep(2); return; }
        const options = { preserveScroll: true, onSuccess: onClose };
        if (deleting) form.delete(`/administracion/usuarios/${user.id}`, options);
        else form.patch(`/administracion/usuarios/${user.id}/estado`, options);
    }
    return <dialog ref={dialog} className="account-dialog" aria-labelledby="account-action-title" aria-describedby="account-action-description" onCancel={event => { event.preventDefault(); if (!form.processing) onClose(); }}>
        <form onSubmit={event => { event.preventDefault(); submit(); }}>
            <header><span className={`account-action-icon ${deleting ? 'is-danger' : ''}`}>{deleting ? <AlertTriangle aria-hidden="true" /> : <Power aria-hidden="true" />}</span><button type="button" className="quiet-button" aria-label="Cerrar confirmación" disabled={form.processing} onClick={onClose}><X size={20} aria-hidden="true" /></button></header>
            {deleting && <p className="eyebrow">Confirmación {step} de 2</p>}
            <h2 id="account-action-title">{deleting ? (step === 1 ? 'Eliminar cuenta y encuestas' : 'Confirma la eliminación definitiva') : user.activo ? 'Desactivar acceso' : 'Reactivar acceso'}</h2>
            <p className="account-action-person"><strong>{user.name}</strong><span>DNI {user.dni}</span></p>
            <p id="account-action-description">{deleting ? `Se eliminará esta cuenta y sus ${user.encuestas_count} encuestas, con todas sus respuestas y fotografías. Los resultados y avances se recalcularán sin esos registros. Esta acción no se puede deshacer.` : user.activo ? 'Se cerrará el acceso de esta cuenta, incluidas sus sesiones abiertas. Sus encuestas y fotografías se conservarán. Podrás reactivarla después.' : 'La persona podrá volver a ingresar con su DNI y contraseña. Deberá iniciar una nueva sesión.'}</p>
            {deleting && step === 2 && <div className="account-delete-confirm"><label htmlFor="delete-account-phrase">Escribe <strong>{phrase}</strong> para confirmar</label><input ref={input} id="delete-account-phrase" value={form.data.confirmacion} onChange={event => form.setData('confirmacion', event.target.value)} autoComplete="off" spellCheck={false} disabled={form.processing} aria-invalid={Boolean(form.errors.confirmacion)} /></div>}
            {Object.values(form.errors).map(error => <p className="error" role="alert" key={error}>{error}</p>)}
            <footer><button type="button" className="quiet-button" autoFocus disabled={form.processing} onClick={onClose}>Cancelar</button><button type="submit" className={deleting ? 'account-danger-button' : 'primary'} disabled={form.processing || (deleting && step === 2 && form.data.confirmacion !== phrase)}>{form.processing ? 'Procesando…' : deleting ? (step === 1 ? 'Continuar con la eliminación' : 'Eliminar definitivamente') : user.activo ? 'Desactivar usuario' : 'Reactivar usuario'}</button></footer>
        </form>
    </dialog>;
}

export function AccountStatus({ active }: { active: boolean }) {
    return <span className={`account-status ${active ? 'is-active' : ''}`}>{active ? <CheckCircle2 size={13} aria-hidden="true" /> : <Power size={13} aria-hidden="true" />}{active ? 'Activo' : 'Inactivo'}</span>;
}
