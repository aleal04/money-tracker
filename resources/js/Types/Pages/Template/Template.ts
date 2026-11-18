import type { Category } from "@/Types/Pages/Period/Period";

export type Frequency = "cada_periodo" | "mensual_repartido" | "mensual_dia";

/** Money is integer cents. */
export interface Template {
	id: number;
	spaceId: number;
	name: string;
	category: Category;
	estimated: number;
	frequency: Frequency;
	dayOfMonth: number | null;
	active: boolean;
}

/** Request body: the amount is a raw string as typed. */
export interface TemplateBody {
	name: string;
	category: Category;
	estimated: string;
	frequency: Frequency;
	dayOfMonth: number | null;
}
