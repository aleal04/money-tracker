const pad = (n: number) => String(n).padStart(2, "0");

/** Dates of a calendar quincena: I = 1–15, II = 16–last day of the month ("YYYY-MM-DD"). */
export function quincenaDates(
	year: number,
	month: number,
	half: 1 | 2,
): { startDate: string; endDate: string } {
	const ym = `${year}-${pad(month)}`;
	const last = new Date(year, month, 0).getDate();
	return half === 1
		? { startDate: `${ym}-01`, endDate: `${ym}-15` }
		: { startDate: `${ym}-16`, endDate: `${ym}-${last}` };
}
