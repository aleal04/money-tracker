import type { PeriodDetail } from "@/Types/Pages/Period/Period";
import type { CardView } from "@/Types/Pages/Installment/Installment";

export interface DashboardData {
	current: PeriodDetail | null;
	cards: CardView[];
	templateCount: number;
}
