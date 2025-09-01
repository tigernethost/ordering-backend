import './bootstrap';
import { createApp } from 'vue/dist/vue.esm-bundler.js';
import SalesReport from './components/SalesReport.vue';

const app = createApp({});

app.component('sales-report', SalesReport);

app.mount('#app');