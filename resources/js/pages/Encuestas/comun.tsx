import { Link } from '@inertiajs/react';
export type Eess = { nombre: string; codigo_renipress: string; microred: string };
export const campos = [
    ['numero', 'N.º de encuesta', 'number'], ['fecha', 'Fecha de aplicación', 'date'],
    ['hora_inicio', 'Hora de inicio', 'time'], ['hora_final', 'Hora final', 'time'],
    ['condicion', 'Condición del encuestado', ['Usuario', 'Acompañante']], ['edad', 'Edad en años', 'number'],
    ['sexo', 'Sexo', ['Masculino', 'Femenino']],
    ['estudios', 'Nivel de estudios', ['Analfabeto', 'Primaria', 'Secundaria', 'Superior técnico', 'Superior universitario']],
    ['seguro', 'Tipo de seguro', ['SIS', 'Ninguno', 'Otro']],
    ['paciente', 'Tipo de usuario', ['Nuevo', 'Continuador']], ['especialidad', 'Consultorio / área donde fue atendido', 'text'],
    ['personal', 'Personal que realizó la atención', ['Médico', 'Obstetra', 'Enfermera', 'Psicólogo', 'Odontólogo', 'Otros']],
] as const;
export type Campo = typeof campos[number][0];
export function etiqueta(campo: typeof campos[number], valor: unknown, version = 'abancay-2026') {
    if (valor === null || valor === undefined || valor === '') return 'Sin respuesta';
    if (campo[0] === 'seguro' && version === 'servqual-provisional-v1') return ['SIS', 'SOAT', 'Ninguno'][Number(valor) - 1];
    return Array.isArray(campo[2]) ? campo[2][Number(valor) - 1] : String(valor);
}
export function Cabecera({ eess }: { eess: Eess }) {
    return <><nav className="actions"><Link href="/mi-establecimiento">Mi establecimiento</Link><Link href="/encuestas">Mis encuestas</Link></nav><p className="eyebrow">{eess.codigo_renipress} · {eess.nombre} · Microred {eess.microred}</p></>;
}
