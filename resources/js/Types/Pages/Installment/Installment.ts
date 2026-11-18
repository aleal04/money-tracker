export type InstallmentType = "tasa0" | "minicuotas";
export type InstallmentStatus = "activa" | "por_terminar" | "terminada";

/** Money is integer cents. */
export interface Installment {
	id: number;
	cardId: number;
	merchant: string;
	item: string;
	type: InstallmentType;
	totalInstallments: number;
	paidInstallments: number;
	initialBalance: number;
	installmentAmount: number | null;
	balanceOverride: number | null;
	cancelSoon: boolean;
	cuota: number;
	saldo: { value: number; calculated: number; diff: number | null };
	status: InstallmentStatus;
}

export interface CardTotals {
	cuotaMensual: number;
	saldoFaltante: number;
	porQuincena: number[];
	porPeriodo?: number[];
}

export interface CardView {
	id: number;
	name: string;
	installments: Installment[];
	totals: CardTotals;
}

/** Request body: amounts are raw strings as typed; the API parses them. */
export interface InstallmentBody {
	cardId: number;
	merchant: string;
	item: string;
	type: InstallmentType;
	totalInstallments: number;
	paidInstallments: number;
	initialBalance: string;
	installmentAmount: string | null;
	balanceOverride: string | null;
}
