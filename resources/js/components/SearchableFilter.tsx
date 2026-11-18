import { useEffect, useRef, useState } from 'react';
import { Check, ChevronDown, Search } from 'lucide-react';

type Option = { value: string; label: string };
const normalize = (text: string) => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();

export default function SearchableFilter({ id, label, value, options, allLabel, disabled, onChange }: {
    id: string; label: string; value: string; options: Option[]; allLabel: string; disabled?: boolean; onChange: (value: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [active, setActive] = useState(0);
    const input = useRef<HTMLInputElement>(null);
    const selected = options.find(option => option.value === value)?.label ?? allLabel;
    const matches = [{ value: '', label: allLabel }, ...options].filter(option => normalize(option.label).includes(normalize(query)));
    useEffect(() => { setOpen(false); setQuery(''); setActive(0); }, [value, disabled]);
    function choose(option: Option) { setOpen(false); setQuery(''); onChange(option.value); input.current?.focus(); }
    return <div className="searchable-filter" onBlur={event => { if (!event.currentTarget.contains(event.relatedTarget)) { setOpen(false); setQuery(''); } }}>
        <label htmlFor={id}>{label}</label>
        <div className="searchable-filter-control"><Search size={16} aria-hidden="true" />
            <input ref={input} id={id} role="combobox" autoComplete="off" aria-autocomplete="list" aria-expanded={open} aria-controls={`${id}-options`} aria-activedescendant={open && matches[active] ? `${id}-option-${active}` : undefined} disabled={disabled} value={open ? query : selected} placeholder="Escribe para buscar…" onFocus={() => { setOpen(true); setQuery(''); setActive(0); }} onClick={() => setOpen(true)} onChange={event => { setQuery(event.target.value); setOpen(true); setActive(0); }} onKeyDown={event => {
                if (event.key === 'Escape') { event.preventDefault(); setOpen(false); setQuery(''); }
                else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') { event.preventDefault(); setOpen(true); setActive(index => Math.max(0, Math.min(matches.length - 1, index + (event.key === 'ArrowDown' ? 1 : -1)))); }
                else if (event.key === 'Enter' && open) { event.preventDefault(); if (matches[active]) choose(matches[active]); }
            }} />
            <button type="button" disabled={disabled} tabIndex={-1} aria-label={`Mostrar opciones de ${label}`} onMouseDown={event => event.preventDefault()} onClick={() => { if (open) setOpen(false); else { input.current?.focus(); setOpen(true); } }}><ChevronDown size={17} aria-hidden="true" /></button>
        </div>
        {open && <div className="searchable-filter-menu"><span className="searchable-filter-count" role="status">{matches.length ? `${matches.length} opciones` : 'No hay coincidencias'}</span><ul id={`${id}-options`} role="listbox" aria-label={label}>{matches.map((option, index) => <li key={option.value} id={`${id}-option-${index}`} role="option" aria-selected={option.value === value} className={index === active ? 'is-highlighted' : ''} ref={node => { if (node && index === active) node.scrollIntoView({ block: 'nearest' }); }} onMouseDown={event => event.preventDefault()} onClick={() => choose(option)}><span>{option.label}</span>{option.value === value && <Check size={16} aria-hidden="true" />}</li>)}</ul></div>}
    </div>;
}
