import { spaceFreqLabel } from "@/Composables/Space/useSpaceIndex";
import type { DashboardSpace } from "@/Types/Pages/Dashboard/Dashboard";

export interface SpaceRow {
	id: number;
	name: string;
	frequency: string;
	/** Remaining of the latest period (cents); null when the space has no periods. */
	restante: number | null;
}

/** One row per space with the remaining of its latest period (from the dashboard payload). */
export const spaceRows = (spaces: DashboardSpace[]): SpaceRow[] =>
	spaces.map((s) => ({
		id: s.id,
		name: s.name,
		frequency: spaceFreqLabel(s),
		restante: s.current?.restante ?? null,
	}));
