// assets/js/api-client.js - النسخة الحقيقية (Real API)

class API {
    static async request(endpoint, method = 'GET', body = null) {
        // بناء الرابط: يدمج رابط السيرفر مع المسار المطلوب
        const url = `${CONFIG.API_BASE_URL}${endpoint}`;
        
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        // إرفاق التوكن (إثبات الشخصية) من اللوكال، وهذا الشيء الوحيد الذي نحفظه
        const token = localStorage.getItem(CONFIG.TOKEN_KEY);
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const config = {
            method,
            headers,
        };

        if (body) {
            config.body = JSON.stringify(body);
        }

        try {
            console.log(`📡 Sending ${method} to ${url}`);
            const response = await fetch(url, config);

            // إذا انتهت الجلسة، طرد المستخدم للدخول مرة أخرى
            if (response.status === 401) {
                localStorage.removeItem(CONFIG.TOKEN_KEY);
                localStorage.removeItem(CONFIG.USER_DATA_KEY);
                window.location.href = 'index.html';
                throw new Error('Session expired');
            }

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `Server Error (${response.status})`);
            }

            return data;

        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // دوال مختصرة للاستخدام السريع
    static get(endpoint) { return this.request(endpoint, 'GET'); }
    static post(endpoint, body) { return this.request(endpoint, 'POST', body); }
    static put(endpoint, body) { return this.request(endpoint, 'PUT', body); }
    static delete(endpoint) { return this.request(endpoint, 'DELETE'); }
}