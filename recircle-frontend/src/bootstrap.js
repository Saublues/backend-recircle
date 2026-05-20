import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: false,
    enabledTransports: ['ws'],
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                // Dynamically leverage our project APP URL or fallback to localhost
                const baseUrl = import.meta.env.VITE_APP_URL || 'http://localhost:8000';
                // Normalize to clean broadcasting auth endpoint
                axios.post(`${baseUrl.trim().replace(/\/$/, '')}/broadcasting/auth`, {
                    socket_id: socketId,
                    channel_name: channel.name
                }, {
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                        'Accept': 'application/json',
                    }
                })
                .then(response => {
                    callback(false, response.data);
                })
                .catch(error => {
                    callback(true, error);
                });
            }
        };
    },
});
