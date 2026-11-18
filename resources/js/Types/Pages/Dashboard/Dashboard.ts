import type { SpaceFrequency } from "@/Types/Pages/Space/Space";
import type { PeriodDetail } from "@/Types/Pages/Period/Period";
import type { CardView } from "@/Types/Pages/Installment/Installment";

export interface DashboardSpace {
	id: number;
	name: string;
	frequency: SpaceFrequency;
	customDays: number | null;
	isPrimary: boolean;
	current: { periodId: number; label: string; restante: number } | null;
}

export interface DashboardData {
	spaces: DashboardSpace[];
	current: PeriodDetail | null;
	cards: CardView[];
	templateCount: number;
}
