<?php

namespace Rapidez\StatamicQuote\Fieldtypes;

use Rapidez\Core\Facades\Rapidez;
use Statamic\Fields\Fieldtype;

class Products extends Fieldtype
{
    protected $selectable = false;
    protected $selectableInForms = true;

    public function process($value)
    {
        return [
            'store' => config('rapidez.store'),
            'products' => $value,
        ];
    }

    public function preProcess($products)
    {
        return $this->augment($products);
    }

    public function augment($data)
    {
        $store = $data['store'] ?? 1;

        Rapidez::setStore($store);

        $products = collect(json_decode($data['products'] ?? $data, true));
        $productModel = config('rapidez.models.product');
        /** @var \Rapidez\Core\Models\Product $productInstance */
        $productInstance = new $productModel;
        $dbProducts = $productModel::withoutGlobalScopes()
            ->whereIn($productInstance->qualifyColumn('sku'), $products->map(fn($product) => $product['sku']))
            ->get()
            ->keyBy('sku');

        return $products->map(function($product) use ($dbProducts, $store) {
            $dbProduct = $dbProducts[$product['sku']] ?? null;

            $productOptions = $dbProduct
                ? collect($product['options'] ?? [])->map(function (string $optionValue, string $option) use ($dbProduct): array {
                    $option = collect($dbProduct->options)->firstOrFail(fn ($productOption) => $productOption->option_id == $option);
                    $value = collect($option->values)->firstOrFail(fn ($value) => $value->option_type_id == $optionValue);

                    return [
                        'title' => $option->title,
                        'price' => ($value?->price->price ?? 0) + ($option->price->price ?? 0),
                        'value' => $value,
                    ];
                })
                : null;

            $totalPrice = $dbProduct
                ? ($productOptions->sum('price.price') + $dbProduct->price) * $product['qty']
                : null;

            return [
                ...$product,
                'store' => $store,
                'product' => $dbProduct,
                'options' => $productOptions,
                'totalPrice' => $totalPrice,
            ];
        });
    }
}
