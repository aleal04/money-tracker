import { ref } from "vue";

type Theme = "light" | "dark";

const KEY = "theme";
const THEME_COLOR: Record<Theme, string> = {
	light: "#ffffff",
	dark: "#0f1012",
};

function stored(): Theme | null {
	try {
		const value = localStorage.getItem(KEY);
		return value === "light" || value === "dark" ? value : null;
	} catch {
		return null;
	}
}

const systemQuery = window.matchMedia?.("(prefers-color-scheme: dark)");
const system = (): Theme => (systemQuery?.matches ? "dark" : "light");

const theme = ref<Theme>(stored() ?? system());

function apply(value: Theme): void {
	document.documentElement.classList.toggle("dark", value === "dark");
	document
		.querySelector('meta[name="theme-color"]')
		?.setAttribute("content", THEME_COLOR[value]);
}

apply(theme.value);

// Without an explicit choice, keep tracking the system setting.
systemQuery?.addEventListener?.("change", () => {
	if (!stored()) {
		theme.value = system();
		apply(theme.value);
	}
});

/** Theme state shared by the whole app: follows the system until the user picks one. */
export function useTheme() {
	function toggleTheme(): void {
		theme.value = theme.value === "dark" ? "light" : "dark";
		try {
			localStorage.setItem(KEY, theme.value);
		} catch {
			// Private mode: the choice is not remembered.
		}
		apply(theme.value);
	}

	return { theme, toggleTheme };
}
