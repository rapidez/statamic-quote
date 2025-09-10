<script>
import { useSessionStorage } from '@vueuse/core';

export default {
    props: {
        addProducts: Array|Object,
    },

    data() {
        return {
            products: [],
        }
    },

    mounted() {
        this.products = useSessionStorage('quote_products', [])
    },
    
    render() {
        return this.$scopedSlots.default(this)
    },

    methods: {
        pushProducts(products = null) {
            if (products === null) {
                products = this.addProducts
            }
            
            if (Array.isArray(products)) {
                this.products.push(...products)
            } else {
                this.products.push(products)
            }
        },

        clearProducts() {
            this.products = []
        },

        newQuote(products = null) {
            this.clearProducts()
            this.pushProducts(products)
        },

        removeProduct(sku) {
            let index = this.products.findIndex(product => product.sku === sku)
            if (index >= 0) {
                this.products.splice(index, 1)
            }
        },

        from(sku) {
            return this.products.find(product => product.sku === sku)
        }
    },

    computed: {
        productsString() {
            if (this.products.length === 0) {
                return ''
            }
            
            return JSON.stringify(this.products)
        },
    },
}
</script>