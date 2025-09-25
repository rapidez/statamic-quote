<quote-data v-slot="quoteData">
    <div>
        <template v-if="quoteData.products.length">
            <x-rapidez::productlist
                value="quoteData.products.map(product => product.sku)"
                field="sku"
            >
                <x-slot:items>
                    <div v-for="item in items" class="flex gap-5 items-center">
                        <button type="button" v-on:click="confirm('@lang('Are you sure you want to remove this product?')') && quoteData.removeProduct(item.sku)">
                            <x-heroicon-o-x-circle class="size-6"/>
                        </button>
                        <div>
                            <img
                                v-if="item.thumbnail"
                                class="object-contain h-16 w-20 shrink-0"
                                :alt="item.name"
                                :src="`/storage/{{ config('rapidez.store') }}/resizes/200/magento/catalog/product${item.thumbnail}.webp`"
                            />
                            <x-rapidez::no-image v-else />
                        </div>
                        <div class="flex flex-col">
                            <span>@{{ quoteData.from(item.sku).qty }} x @{{ item.name }}</span>
                            <template v-for="option in quoteData.from(item.sku).options ?? {}">
                                <span class="text-muted">@{{ option }}</span>
                            </template>
                        </div>
                    </div>
                </x-slot:items>
            </x-rapidez::productlist>
        </template>
        </x-rapidez::productlist>
        <div v-else class="text-muted">
            @lang('No products selected.')
        </div>
        <input
            type="hidden"
            v-bind:value="quoteData.productsString"
            name="{{ $field['handle'] }}"
            @required(in_array('required', $field['validate'] ?? []))
        />
    </div>
</quote-data>
