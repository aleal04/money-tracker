import type { Category } from "@/Types/Pages/Period/Period";

export type Frequency =
	| "quincenal"
	| "mensual_dividido"
	| "mensual_en_quincena";

/** Money is integer cents. */
export interface Template {
	id: number;
	name: string;
	category: Category;
	estimated: number;
	frequency: Frequency;
	half: 1 | 2 | null;
	active: boolean;
}

/** Request body: the amount is a raw string as typed. */
export interface TemplateBody {
	name: string;
	category: Category;
	estimated: string;
	frequency: Frequency;
	half: 1 | 2 | null;
}
