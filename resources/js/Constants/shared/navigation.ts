export type IconName =
	| "home"
	| "periods"
	| "installments"
	| "templates"
	| "user"
	| "moon"
	| "theme";

export interface NavLink {
	href: string;
	label: string;
	icon: IconName;
	/** URL prefixes (besides href) that mark the entry as active. */
	prefixes?: string[];
}

export const NAV_LINKS: NavLink[] = [
	{ href: "/", label: "Inicio", icon: "home" },
	{
		href: "/periods",
		label: "Quincenas",
		icon: "periods",
	},
	{
		href: "/installments",
		label: "Cuotas",
		icon: "installments",
	},
	{
		href: "/templates",
		label: "Plantillas",
		icon: "templates",
	},
];

/** Whether the current Inertia URL (may carry a query string) belongs to the link. */
export function isActive(link: NavLink, url: string): boolean {
	const path = url.split(/[?#]/)[0];
	if (link.href === "/") return path === "/";
	return (link.prefixes ?? [link.href]).some(
		(p) => path === p || path.startsWith(`${p}/`),
	);
}
