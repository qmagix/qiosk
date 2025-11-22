import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import axios from 'axios'
import App from './App.vue'
import router from './router'

// Configure Axios
axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')

