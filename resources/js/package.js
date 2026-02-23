import 'Vendor/rapidez/core/resources/js/vue'
import { defineAsyncComponent } from 'vue'

Vue.component('quote-data', defineAsyncComponent(() => import('./components/QuoteData.vue')))
