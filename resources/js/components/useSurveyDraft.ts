import { useEffect, useRef, useState } from 'react';

type Draft<T> = { schema: 1; data: T; step: number; savedAt: string };
// Un borrador por cuenta y establecimiento, únicamente en este navegador.
export function useSurveyDraft<T extends { inicio_token: string }>(key: string, data: T, step: number, dirty: boolean) {
    const [pending, setPending] = useState<Draft<T> | null>(null);
    const [ready, setReady] = useState(false);
    const [savedAt, setSavedAt] = useState<string | null>(null);
    const [failed, setFailed] = useState(false);
    const finished = useRef(false);
    useEffect(() => {
        try {
            const raw = localStorage.getItem(key);
            if (raw) {
                const draft = JSON.parse(raw);
                if (draft.schema === 1 && draft.data && typeof draft.data.inicio_token === 'string' && Array.isArray(draft.data.respuestas) && draft.data.respuestas.length === 22 && Number.isInteger(draft.step) && draft.step >= 0 && draft.step <= 3) setPending(draft);
                else localStorage.removeItem(key);
            }
        } catch { setFailed(true); }
        setReady(true);
    }, [key]);
    useEffect(() => {
        if (!ready || pending || !dirty || finished.current) return;
        try {
            const savedAt = new Date().toISOString();
            localStorage.setItem(key, JSON.stringify({ schema: 1, data, step, savedAt }));
            setSavedAt(savedAt); setFailed(false);
        } catch { setFailed(true); }
    }, [key, data, step, dirty, ready, pending]);
    function clear() {
        finished.current = true;
        try { localStorage.removeItem(key); } catch { setFailed(true); }
    }
    function discard() {
        try { localStorage.removeItem(key); setPending(null); setSavedAt(null); setFailed(false); }
        catch { setFailed(true); }
    }
    function restore() { const value = pending; setPending(null); return value; }
    return { pending, ready, savedAt, failed, clear, discard, restore };
}
