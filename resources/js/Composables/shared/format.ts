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
