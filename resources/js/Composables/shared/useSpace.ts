import axios from "axios";
import { router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import type { Space } from "@/Types/Pages/Space/Space";

const KEY = "space";

function stored(): number | null {
	try {
		const n = Number(localStorage.getItem(KEY));
		return Number.isInteger(n) && n > 0 ? n : null;
	} catch {
		return null;
	}
}

// Module singleton: every page and the topbar share the same selection.
const spaces = ref<Space[]>([]);
/** The chosen space id; null means "the primary" (the API default when no `space` is sent). */
const selectedId = ref<number | null>(stored());

/** The chosen space, falling back to the primary when nothing (valid) is chosen. */
const current = computed<Space | null>(
	() =>
		spaces.value.find((s) => s.id === selectedId.value) ??
		spaces.value.find((s) => s.isPrimary) ??
		null,
);

function persist(id: number): void {
	selectedId.value = id;
	try {
		localStorage.setItem(KEY, String(id));
	} catch {
		// Private mode or blocked storage: the choice lasts for this visit only.
	}
}

/** Query params for GET calls: empty (primary) until a space is chosen. */
const spaceParams = (): { space?: number } =>
	selectedId.value === null ? {} : { space: selectedId.value };

async function loadSpaces(): Promise<void> {
	spaces.value = (await axios.get<Space[]>("/spaces/get-space-list")).data;
	// A stored space that no longer exists goes back to the primary.
	if (
		selectedId.value !== null &&
		!spaces.value.some((s) => s.id === selectedId.value)
	) {
		selectedId.value = null;
		try {
			localStorage.removeItem(KEY);
		} catch {
			// Blocked storage: nothing persisted to clean.
		}
	}
}

let loaded: Promise<void> | null = null;
/** Loads the spaces once so the first data request never sends a stale stored id (404). */
function ensureSpaces(): Promise<void> {
	loaded ??= loadSpaces().catch(() => undefined);
	return loaded;
}

/** Follow the space of the period on screen without reloading anything. */
function adoptSpace(id: number): void {
	if (selectedId.value !== id) persist(id);
}

/** Choose a space; pages watching `selectedId` reload, the period pages go to its latest period. */
function setSpace(id: number): void {
	persist(id);
	if (window.location.pathname.startsWith("/periods")) {
		router.visit(`/periods?space=${id}`);
	}
}

export function useSpace() {
	return {
		spaces,
		selectedId,
		current,
		spaceParams,
		loadSpaces,
		ensureSpaces,
		setSpace,
		adoptSpace,
	};
}
