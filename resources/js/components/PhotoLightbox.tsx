import { useEffect, useRef, useState } from 'react';
import { X, ZoomIn, ZoomOut, Maximize, RotateCcw, LoaderCircle, ImageOff } from 'lucide-react';

export default function PhotoLightbox({ src, onClose }: { src: string; onClose: () => void }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const viewport = useRef<HTMLDivElement>(null);
    const [zoom, setZoom] = useState(1);
    const [status, setStatus] = useState<'loading'|'ready'|'error'>('loading');
    const [attempt, setAttempt] = useState(0);
    const [size, setSize] = useState({width:0,height:0});
    const [bounds, setBounds] = useState({width:1,height:1});
    useEffect(() => {
        const previous = document.activeElement as HTMLElement | null;
        const overflow = document.body.style.overflow;
        dialog.current?.showModal(); document.body.style.overflow = 'hidden';
        const observer = new ResizeObserver(entries => { const r = entries[0].contentRect; setBounds({width:r.width,height:r.height}); });
        if(viewport.current) observer.observe(viewport.current);
        return () => { observer.disconnect(); document.body.style.overflow = overflow; previous?.focus(); };
    }, []);
    useEffect(() => { setStatus('loading'); setZoom(1); setSize({width:0,height:0}); }, [src]);
    const fit = size.width ? Math.min(bounds.width / size.width, bounds.height / size.height, 1) : 1;
    function adjust(delta:number) { setZoom(z=>Math.max(1,Math.min(4,z+delta))); }
    function reset() { setZoom(1); viewport.current?.scrollTo({left:0,top:0}); }
    return <dialog ref={dialog} className="photo-lightbox" aria-labelledby="lightbox-title" onCancel={e => { e.preventDefault(); onClose(); }} onClick={e => { if (e.target === e.currentTarget) onClose(); }} onKeyDown={e=>{if(e.key==='Tab'){const nodes=Array.from(e.currentTarget.querySelectorAll<HTMLElement>('button:not(:disabled), [tabindex="0"]')).filter(n=>n.getClientRects().length);const first=nodes[0],last=nodes[nodes.length-1];if(e.shiftKey && document.activeElement===first){e.preventDefault();last?.focus();}else if(!e.shiftKey && document.activeElement===last){e.preventDefault();first?.focus();}}if(e.key==='+' || e.key==='='){e.preventDefault();if(status==='ready')adjust(.5);}if(e.key==='-'){e.preventDefault();adjust(-.5);}if(e.key==='0'){e.preventDefault();reset();}}}>
        <div className="lightbox-panel"><header><h2 id="lightbox-title">Foto de respaldo</h2><button type="button" autoFocus onClick={onClose} aria-label="Cerrar foto ampliada"><X size={22} aria-hidden="true" /></button></header>
        <div className="lightbox-tools" role="group" aria-label="Ampliación de fotografía"><button type="button" disabled={status!=='ready'||zoom<=1} onClick={()=>adjust(-.5)} aria-label="Reducir zoom"><ZoomOut size={19} aria-hidden="true" /></button><output aria-live="polite">{Math.round(zoom*100)} %</output><button type="button" disabled={status!=='ready'||zoom>=4} onClick={()=>adjust(.5)} aria-label="Aumentar zoom"><ZoomIn size={19} aria-hidden="true" /></button><button type="button" disabled={status!=='ready'} onClick={reset}><Maximize size={17} aria-hidden="true" />Ajustar</button></div>
        <div ref={viewport} className="lightbox-image zoom-viewport" aria-busy={status === 'loading'} tabIndex={0} aria-label="Fotografía; usa las barras o desliza para recorrer la imagen ampliada">
            {status==='loading' && <div className="lightbox-state" role="status"><LoaderCircle className="photo-spinner" size={28} aria-hidden="true"/><p>Cargando fotografía…</p></div>}
            {status==='error' && <div className="lightbox-state" role="alert"><ImageOff size={34} aria-hidden="true"/><h3>No se pudo cargar la fotografía</h3><p>Comprueba tu conexión y vuelve a intentarlo.</p><button type="button" onClick={()=>{setStatus('loading');reset();setAttempt(n=>n+1);}}><RotateCcw size={17} aria-hidden="true"/>Reintentar</button></div>}
            {status!=='error' && <div className="zoom-canvas" style={{width:status==='ready'?Math.max(bounds.width,size.width*fit*zoom):'100%',height:status==='ready'?Math.max(bounds.height,size.height*fit*zoom):'100%',display:status==='ready'?'grid':'none'}}><img key={`${src}-${attempt}`} src={src} alt="Fotografía ampliada del formulario de encuesta" onLoad={e=>{setSize({width:e.currentTarget.naturalWidth,height:e.currentTarget.naturalHeight});setStatus('ready');}} onError={()=>setStatus('error')} style={{width:size.width*fit*zoom,height:size.height*fit*zoom}} /></div>}
        </div><p>Usa los botones para ampliar y desliza para recorrer la foto. En teclado: +, − y 0. Escape cierra el visor.</p></div>
    </dialog>;
}
