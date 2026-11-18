import type { Category, Line } from "@/Types/Pages/Period/Period";
import type { Space } from "@/Types/Pages/Space/Space";

export type LineKind =
	| "normal"
	| "origin"
	| "destination"
	| "arrastre"
	| "transferCarried";

/** What a row is: destination lines are read-only; '↩' lines are carried but carry a target, so they are not the general arrastre. */
export function lineKind(l: Line): LineKind {
	if (l.source === "transfer") return "destination";
	if (l.source === "carried")
		return l.targetSpaceId === null ? "arrastre" : "transferCarried";
	return l.targetSpaceId === null ? "normal" : "origin";
}

/** Name of a space by id, or "otro espacio" when it is not in the list. */
export const spaceName = (spaces: Space[], id: number | null): string =>
	spaces.find((s) => s.id === id)?.name ?? "otro espacio";

/** The badge text of a row, or null when it has none. */
export function transferBadge(l: Line, spaces: Space[]): string | null {
	switch (lineKind(l)) {
		case "origin":
			return `→ ${spaceName(spaces, l.targetSpaceId)}`;
		case "transferCarried":
			return `↩ → ${spaceName(spaces, l.targetSpaceId)}`;
		case "destination":
			return `↘ desde ${l.transferFromSpaceName ?? "otro espacio"}`;
		default:
			return null;
	}
}

/** The "Enviar a espacio" select only makes sense for manual/template expenses (not carried, cuotas or transfer lines). */
export const canSendToSpace = (category: Category, line?: Line): boolean =>
	category !== "ingreso" &&
	(!line || line.source === "manual" || line.source === "template");

/** Spaces a line can be sent to: every space but its own. */
export const targetOptions = (spaces: Space[], ownId: number | undefined) =>
	spaces.filter((s) => s.id !== ownId);

/** Close preview split: lines going into the arrastre vs lines that pass on as transfers. */
export function splitPreview(lines: Line[]) {
	const amount = (l: Line) => l.actual ?? l.estimated;
	const transfers = lines.filter((l) => l.targetSpaceId !== null);
	const arrastre = lines.filter((l) => l.targetSpaceId === null);
	const sum = (xs: Line[]) => xs.reduce((n, l) => n + amount(l), 0);
	return {
		arrastre,
		transfers,
		arrastreTotal: sum(arrastre),
		transfersTotal: sum(transfers),
	};
}
