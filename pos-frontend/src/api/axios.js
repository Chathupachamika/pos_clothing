import axios from 'axios';

export const connection = axios.create({
    baseURL: 'http://localhost:8000/api/v1/',
});

// Add request interceptor to include token in headers
connection.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Modified response interceptor to only clear storage on 401
connection.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        }
        return Promise.reject(error);
    }
);

// Password Reset endpoints
export const passwordResetApi = {
    sendResetEmail: (email) => connection.post('/forgot-password', { email }),
    resetPassword: (data) => connection.post('/reset-password', data),
};

// Return Item endpoints
export const returnItemApi = {
    getAll: () => connection.get('/return'),
    getById: (id) => connection.get(`/return/${id}`),
    export: (format, ids) => connection.post('/return/export', { format, ids }),
};