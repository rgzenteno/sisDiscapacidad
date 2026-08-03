import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true; // 👈 importante para cookies/sesión

// Interceptor: si Laravel devuelve 419, refrescar el token y reintentar
window.axios.interceptors.response.use(
    response => response,
    async error => {
        if (error.response?.status === 419) {
            // Refrescar el token CSRF
            await axios.get('/sanctum/csrf-cookie');
            // Reintentar la petición original
            return axios(error.config);
        }
        return Promise.reject(error);
    }
);

import './echo';
