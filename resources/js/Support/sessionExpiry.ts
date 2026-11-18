import axios from "axios";

// Expired session: reload for a fresh CSRF token / login instead of 419 on every tap.
export function reloadOnSessionExpiry() {
	axios.interceptors.response.use(undefined, (e) => {
		if (e?.response?.status === 419) window.location.reload();
		return Promise.reject(e);
	});
}
