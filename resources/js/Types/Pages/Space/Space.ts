export type SpaceFrequency = "weekly" | "biweekly" | "monthly" | "custom";

/** Money is integer cents. */
export interface Space {
	id: number;
	name: string;
	frequency: SpaceFrequency;
	customDays: number | null;
	initialBalance: number;
	isPrimary: boolean;
	/** Its frequency and days are locked once it has periods. */
	hasPeriods: boolean;
}

/** Request body: the amount is a raw string as typed. */
export interface SpaceBody {
	name: string;
	frequency: SpaceFrequency;
	customDays: number | null;
	initialBalance: string;
}

/** Dates and label the space's frequency proposes for its next period. */
export interface PeriodProposal {
	startDate: string;
	endDate: string;
	label: string;
}
