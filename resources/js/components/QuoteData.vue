<script>
import { useSessionStorage } from '@vueuse/core';
import { useIDBKeyval } from '@vueuse/integrations/useIDBKeyval'

export default {
    props: {
        addProducts: Array|Object,
    },

    data() {
        return {
            products: [],
            customOptions: useIDBKeyval('quote_custom_options', {}),
        }
    },

    mounted() {
        this.products = useSessionStorage('quote_products', [])
    },

    render() {
        return this.$scopedSlots.default(this)
    },

    methods: {
        async pushProducts(products = null) {
            if (products === null) {
                products = this.addProducts
            }

            if (!Array.isArray(products)) {
                products = [products]
            }

            products = products.map(product => {
                if (!('customizable_options' in product)) {
                    return product
                }

                let options = Object.fromEntries(product.customizable_options
                    .filter((option) => option.type != 'file')
                    .map((option) => ([
                        window.atob(option.customizable_option_uid).split('/')[1],
                        option.values[0].value,
                    ]))
                )

                let customOptions = product.customizable_options.filter((option) => option.type == 'file')

                return {
                    sku: product.sku,
                    qty: product.qty,
                    options: options,
                    customOptions: customOptions,
                }
            })

            products = products.map(product => ({...product, id: Math.random().toString(16)}))
            let customOptions = Object.fromEntries(products.map(product => [product.id, product.customOptions ?? {}]))

            await this.customOptions.set(customOptions)
            this.products.push(...products.map(product => ({
                id: product.id,
                sku: product.sku,
                qty: product.qty ?? 1,
                options: product.options ?? {},
            })))
        },

        async clearProducts() {
            await this.customOptions.set({})
            this.products = []
        },

        async newQuote(products = null) {
            await this.clearProducts()
            await this.pushProducts(products)
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

            return JSON.stringify(this.products.map(product => ({
                ...product,
                customOptions: this.customOptions.data[product.id] ?? {}
            })))
        },
    },
}
</script>
