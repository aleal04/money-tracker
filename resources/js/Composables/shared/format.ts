/** Cents to colones, "₡10.865,00" / "-₡30.900,00"; same output as the backend MoneyHelper::format, with no ICU dependency. */
export function formatCRC(cents: number): string {
	const abs = Math.abs(Math.round(cents));
	const int = String(Math.floor(abs / 100)).replace(/\B(?=(\d{3})+$)/g, ".");
	return `${cents < 0 ? "-" : ""}₡${int},${String(abs % 100).padStart(2, "0")}`;
}

/** Signed difference label, or null when there is no actual or no difference. */
export function diffLabel(
	estimated: number,
	actual: number | null,
): string | null {
	if (actual === null || actual === estimated) return null;
	const d = actual - estimated;
	return (d > 0 ? "+" : "") + formatCRC(d);
}

/** Cents to a plain editable number: 1086500 -> "10865", 1086550 -> "10865,50". */
export const plainAmount = (c: number): string =>
	c % 100 === 0 ? String(c / 100) : (c / 100).toFixed(2).replace(".", ",");

const MONTHS_SHORT = [
	"ene",
	"feb",
	"mar",
	"abr",
	"may",
	"jun",
	"jul",
	"ago",
	"sep",
	"oct",
	"nov",
	"dic",
];

/** "12 nov – 26 nov 2026" (the year goes on each side only when they differ); input "YYYY-MM-DD". */
export function dateRange(start: string, end: string): string {
	const part = (d: string, year: boolean) => {
		const [y, m, day] = d.split("-").map(Number);
		return `${day} ${MONTHS_SHORT[m - 1]}${year ? ` ${y}` : ""}`;
	};
	const sameYear = start.slice(0, 4) === end.slice(0, 4);
	return `${part(start, !sameYear)} – ${part(end, true)}`;
}
