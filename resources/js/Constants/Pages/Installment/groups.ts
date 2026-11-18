import type { InstallmentStatus } from "@/Types/Pages/Installment/Installment";

/** Blue / yellow / green, shown as a left border and a tinted pill. */
export const GROUPS: { status: InstallmentStatus; label: string }[] = [
	{ status: "activa", label: "Activas" },
	{ status: "por_terminar", label: "Por terminar" },
	{ status: "terminada", label: "Terminadas" },
];
