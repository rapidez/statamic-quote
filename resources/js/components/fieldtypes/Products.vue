<template>
    <div class="flex flex-col *:py-1 divide-y">
        <div v-for="item in value" class="flex gap-5 items-center">
            <div>
                <img
                    v-if="item.product.thumbnail"
                    class="object-contain h-16 w-20 shrink-0"
                    :alt="item.product.name"
                    :src="`/storage/${item.store}/resizes/200/magento/catalog/product${item.product.thumbnail}.webp`"
                />
                <div class="object-contain h-16 w-20 shrink-0 flex items-center justify-center opacity-50" v-else>
                    No image
                </div>
            </div>
            <div class="flex flex-col">
                <span>{{ item.qty }} x {{ item.product.name }}</span>
                <template v-for="option in item.options ?? {}">
                    <div class="flex">
                        <span class="opacity-50">{{ option.title }}: {{ option.value.title }}</span>
                        <span class="ml-auto opacity-50">+{{ price(option.price) }}</span>
                    </div>
                </template>
            </div>
            <div class="ml-auto">
                {{ price(item.totalPrice) }}
            </div>
        </div>
    </div>
</template>

<script>
export default {
    mixins: [Fieldtype],

    methods: {
        price(value, extra = {}) {
            return new Intl.NumberFormat(StatamicConfig.locale.replace('_', '-'), {
                style: 'currency',
                currency: 'EUR',
                ...extra,
            }).format(value)
        }
    },  
}
</script>