// assets/js/api-client.js
class API {
    static async request(endpoint, method = 'GET', body = null) {
        // Normalize base URL to avoid double slashes
        const baseUrl = CONFIG.API_BASE_URL.replace(/\/$/, "");
        const cleanEndpoint = endpoint.startsWith("/") ? endpoint : `/${endpoint}`;
        const url = `${baseUrl}${cleanEndpoint}`;

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        const token = localStorage.getItem(CONFIG.TOKEN_KEY);
        if (token && !endpoint.includes('login')) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const config = { method, headers };
        if (body) config.body = JSON.stringify(body);

        try {
            const response = await fetch(url, config);

            if (response.status === 401 && !endpoint.includes('login')) {
                this.handleSessionExpired();
                return;
            }

            // Successful update with no response body (204 No Content)
            if (response.status === 204) return { success: true };

            const data = await response.json();
            if (!response.ok) throw new Error(data.message || `Error ${response.status}`);
            return data;
        } catch (error) {
            console.error("🌐 Network Error:", error.message);
            throw error;
        }
    }

    static get(endpoint) { return this.request(endpoint, 'GET'); }
    static post(endpoint, body) { return this.request(endpoint, 'POST', body); }
    static put(endpoint, body) { return this.request(endpoint, 'PUT', body); }
    static patch(endpoint, body) { return this.request(endpoint, 'PATCH', body); } // Added helper for PATCH
    static delete(endpoint) { return this.request(endpoint, 'DELETE'); }

    static handleSessionExpired() {
        localStorage.clear();
        window.location.href = 'index.html';
    }
}   