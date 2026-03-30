import Echo from 'laravel-echo'

import Pusher from 'pusher-js'
window.Pusher = Pusher

const realtimeDriver = (import.meta.env.VITE_BROADCAST_DRIVER ?? 'pusher').toLowerCase()

const toPort = (value, fallback) => {
    const parsed = Number(value)

    return Number.isFinite(parsed) ? parsed : fallback
}

const resolveEchoConfig = () => {
    if (realtimeDriver === 'reverb') {
        const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https'

        return {
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: toPort(import.meta.env.VITE_REVERB_PORT, 80),
            wssPort: toPort(import.meta.env.VITE_REVERB_PORT, 443),
            forceTLS: scheme === 'https',
            enabledTransports: ['ws', 'wss'],
        }
    }

    const scheme = import.meta.env.VITE_PUSHER_SCHEME ?? 'https'

    return {
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        wsHost: import.meta.env.VITE_PUSHER_HOST || undefined,
        wsPort: toPort(import.meta.env.VITE_PUSHER_PORT, 80),
        wssPort: toPort(import.meta.env.VITE_PUSHER_PORT, 443),
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    }
}

window.Echo = new Echo(resolveEchoConfig())
