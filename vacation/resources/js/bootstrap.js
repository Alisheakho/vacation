import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,

    // 👇 لازم يطابق الـ prefix اللي حطيناه فوق (api)
    authEndpoint: '/api/broadcasting/auth', 
    
    auth: {
        headers: {
            // هذا التوكن اللي رح تجيبه من اللوجين وتخزنه
            Authorization: 'Bearer ' + localStorage.getItem('jwt_token'),
            Accept: 'application/json',
        },
    },
});