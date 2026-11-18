import axios from "axios";

/** Message for a failed JSON call: 409/404 {message}, otherwise a generic one. */
export function errorMessage(e: unknown): string {
	if (axios.isAxiosError(e) && typeof e.response?.data?.message === "string") {
		return e.response.data.message;
	}
	return axios.isAxiosError(e) && e.response
		? "Error inesperado"
		: "Error de conexión";
}

/** 422 field errors as {field: firstMessage}; any other failure goes under `form`. */
export function fieldErrors(e: unknown): Record<string, string> {
	if (axios.isAxiosError(e) && e.response?.status === 422) {
		const errors = e.response.data?.errors ?? {};
		return Object.fromEntries(
			Object.entries(errors).map(([k, v]) => [k, (v as string[])[0]]),
		);
	}
	return { form: errorMessage(e) };
}
