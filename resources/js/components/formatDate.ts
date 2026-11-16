// Fechas del servidor en hora local de Lima, sin conversión de zona implícita.
export function formatDate(value: string) {
 const [date, time] = value.replace('T',' ').split(' ');
 const [year, month, day] = date.split('-');
 return year && month && day ? `${day}/${month}/${year}${time ? ` · ${time.slice(0,5)}` : ''}` : value;
}
