import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router'

// createApp(App).mount('#app');

// Create Vue application
const app = createApp(App)
// (Lucide icons are used per-component where needed)

// Use router
app.use(router)

// Mount the app
app.mount('#app')