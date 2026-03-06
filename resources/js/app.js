import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router'

// createApp(App).mount('#app');

/* import font awesome core */
import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEye, faEyeSlash } from '@fortawesome/free-regular-svg-icons'

/* add icons to the library */
library.add(faEye, faEyeSlash)

// Create Vue application
const app = createApp(App)
app.component('font-awesome-icon', FontAwesomeIcon)

// Use router
app.use(router)

// Mount the app
app.mount('#app')