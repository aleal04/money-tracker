import type { Summary } from "@/Types/Pages/Period/Period";

export interface SummaryCard {
	key: string;
	label: string;
	value: number;
	/** Restante is red when negative and teal otherwise; the rest have no tone. */
	tone: "negative" | "positive" | null;
}

/** The five cards of a period: Saldo anterior, Entró, Gastado, Restante, Falta por pagar. */
export const summaryCards = (s: Summary): SummaryCard[] => [
	{
		key: "saldoAnterior",
		label: "Saldo anterior",
		value: s.saldoAnterior,
		tone: null,
	},
	{ key: "entro", label: "Entró", value: s.entro, tone: null },
	{ key: "gastado", label: "Gastado", value: s.gastado, tone: null },
	{
		key: "restante",
		label: "Restante",
		value: s.restante,
		tone: s.restante < 0 ? "negative" : "positive",
	},
	{
		key: "faltaPorPagar",
		label: "Falta por pagar",
		value: s.faltaPorPagar,
		tone: null,
	},
];
