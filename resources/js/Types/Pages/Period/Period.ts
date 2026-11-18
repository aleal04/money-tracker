export type Category =
	| "ingreso"
	| "servicio"
	| "gasto"
	| "ahorro"
	| "deuda_fija";
export type LineSource =
	| "manual"
	| "template"
	| "installments"
	| "carried"
	| "transfer";

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
	targetSpaceId: number | null;
	transferFromLineId: number | null;
	transferFromSpaceName: string | null;
}

/** year/month/half are only set for biweekly periods on calendar halves; dates are "YYYY-MM-DD". */
export interface Period {
	id: number;
	spaceId: number;
	startDate: string;
	endDate: string;
	year: number | null;
	month: number | null;
	half: 1 | 2 | null;
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
	saldoAnterior: number;
	restante: number;
	entro: number;
	gastado: number;
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
	targetSpaceId?: number | null;
}
