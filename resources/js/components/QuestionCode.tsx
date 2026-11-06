import { useState } from 'react';
import { createPortal } from 'react-dom';
import { useLayoutEffect, useRef } from 'react';
export type Question = { numero: number; expectativa: string | null; percepcion: string | null };
export default function QuestionCode({ question, version }: { question?: Question; version?: string }) {
    const [open, setOpen] = useState(false);
    const [position, setPosition] = useState({ left: 0, top: 0, ready: false });
    const trigger = useRef<HTMLButtonElement>(null);
    const popover = useRef<HTMLSpanElement>(null);
    const closeTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    useLayoutEffect(() => {
        if (!open || !question || !trigger.current || !popover.current) return;
        const updatePosition = () => {
            const anchor = trigger.current?.getBoundingClientRect();
            const tip = popover.current?.getBoundingClientRect();
            if (!anchor || !tip) return;
            const margin = 12;
            const left = Math.max(margin, Math.min(anchor.left, window.innerWidth - tip.width - margin));
            const below = anchor.bottom + margin;
            const top = below + tip.height <= window.innerHeight - margin
                ? below
                : Math.max(margin, anchor.top - tip.height - margin);
            setPosition({ left, top, ready: true });
        };
        updatePosition();
        window.addEventListener('resize', updatePosition);
        window.addEventListener('scroll', updatePosition, true);
        return () => {
            window.removeEventListener('resize', updatePosition);
            window.removeEventListener('scroll', updatePosition, true);
        };
    }, [open, question?.numero]);
    function keepOpen() {
        if (closeTimer.current) clearTimeout(closeTimer.current);
    }
    function closeSoon() {
        closeTimer.current = setTimeout(() => setOpen(false), 140);
    }
    function close() {
        if (closeTimer.current) clearTimeout(closeTimer.current);
        setOpen(false);
    }
    if (!question) return null;
    return <span className="question-code" onMouseEnter={keepOpen} onMouseLeave={closeSoon} onFocus={() => setOpen(true)} onBlur={closeSoon} onKeyDown={e => { if (e.key === 'Escape') { close(); e.stopPropagation(); } }}>
        <button ref={trigger} type="button" aria-expanded={open} aria-label={`Ver pregunta P${String(question.numero).padStart(2, '0')}`} onMouseEnter={keepOpen} onClick={() => setOpen(true)}>P{String(question.numero).padStart(2, '0')} <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><circle cx="12" cy="12" r="9" /><path d="M12 11v6" /><circle cx="12" cy="7.5" r=".8" fill="currentColor" stroke="none" /></svg></button>
        {open && createPortal(<span ref={popover} className="question-popover question-popover-portal" role="note" style={{ left: position.left, top: position.top, visibility: position.ready ? 'visible' : 'hidden' }} onMouseEnter={keepOpen} onMouseLeave={closeSoon}><strong>Pregunta {question.numero}</strong>{!version && <small>Enunciado de referencia: Abancay 2026. El resultado incluye todas las versiones.</small>}{question.expectativa ? <><span><b>Expectativa</b>{question.expectativa}</span><span><b>Percepción</b>{question.percepcion}</span></> : <span>No hay enunciados documentados para esta versión.</span>}</span>, document.body)}
    </span>;
}
