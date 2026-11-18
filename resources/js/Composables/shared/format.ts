// useGrouping true = 'always': group even 4-digit amounts (₡5.900,00).
const crc = new Intl.NumberFormat("es-CR", {
	style: "currency",
	currency: "CRC",
	useGrouping: true,
});

/** Cents to colones, "₡10.865,00". CLDR groups with a space; the spec wants ".", so spacing literals are dropped. */
export const formatCRC = (cents: number): string =>
	crc
		.formatToParts(cents / 100)
		.map((p) =>
			p.type === "group"
				? "."
				: p.type === "literal" && !p.value.trim()
					? ""
					: p.value,
		)
		.join("");

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
