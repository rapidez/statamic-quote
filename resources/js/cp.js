import ProductsFieldtype from "./components/fieldtypes/Products.vue"

Statamic.booting(() => {
    Statamic.$components.register('products-fieldtype', ProductsFieldtype)
})