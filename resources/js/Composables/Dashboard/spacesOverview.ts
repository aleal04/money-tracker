import { spaceFreqLabel } from "@/Composables/Space/useSpaceIndex";
import type { DashboardData } from "@/Types/Pages/Dashboard/Dashboard";
import type { Space } from "@/Types/Pages/Space/Space";

export interface SpaceRow {
	id: number;
	name: string;
	frequency: string;
	/** Remaining of the latest period (cents); null when the space has no periods. */
	restante: number | null;
}

/** One row per space with the remaining of its latest period (from its dashboard data). */
export const spaceRows = (
	spaces: Space[],
	data: Record<number, DashboardData | undefined>,
): SpaceRow[] =>
	spaces.map((s) => ({
		id: s.id,
		name: s.name,
		frequency: spaceFreqLabel(s),
		restante: data[s.id]?.current?.summary.restante ?? null,
	}));
