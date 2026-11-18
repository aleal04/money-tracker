export type Category =
	| "ingreso"
	| "servicio"
	| "gasto"
	| "ahorro"
	| "deuda_fija";
export type LineSource = "manual" | "template" | "installments" | "carried";

/** Money is integer cents. */
export interface Line {
	id: number;
	periodId: number;
	category: Category;
	name: string;
	estimated: number;
	actual: number | null;
	paid: boolean;
	paidAt: string | null;
	source: LineSource;
	templateId: number | null;
	cardId: number | null;
	carriedToLineId: number | null;
	sort: number;
	carried: boolean;
}

export interface Period {
	id: number;
	year: number;
	month: number;
	half: 1 | 2;
	closed: boolean;
	label: string;
}

export interface CategoryTotals {
	estimated: number;
	actual: number;
}

export interface Summary {
	ingreso: number;
	egreso: number;
	disponible: number;
	faltaPorPagar: number;
	byCategory: Record<Category, CategoryTotals>;
}

export interface PeriodDetail {
	period: Period;
	prevId: number | null;
	nextId: number | null;
	lines: Line[];
	summary: Summary;
}

export interface ClosePreview {
	lines: Line[];
	total: number;
}

/** Request body: amounts are raw strings as typed; the API parses them. */
export interface LineBody {
	category?: Category;
	name: string;
	estimated: string;
	actual: string | null;
	paid?: boolean;
}
