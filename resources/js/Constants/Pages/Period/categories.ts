import type { Category } from "@/Types/Pages/Period/Period";

export const CATEGORIES: { key: Category; label: string }[] = [
	{ key: "ingreso", label: "Ingresos" },
	{ key: "servicio", label: "Servicios" },
	{ key: "gasto", label: "Gastos" },
	{ key: "ahorro", label: "Ahorros" },
	{ key: "deuda_fija", label: "Deudas fijas" },
];

export const MONTHS = [
	"Enero",
	"Febrero",
	"Marzo",
	"Abril",
	"Mayo",
	"Junio",
	"Julio",
	"Agosto",
	"Septiembre",
	"Octubre",
	"Noviembre",
	"Diciembre",
];
