import { defineAsyncComponent } from 'vue'

document.addEventListener('vue:loaded', function (event) {
    const vue = event.detail.vue
    vue.component('quote-data', defineAsyncComponent(() => import('./components/QuoteData.vue')))
})
