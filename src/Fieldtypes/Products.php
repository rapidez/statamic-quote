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

        return Rapidez::withStore($store, function() use ($data, $store) {
            $products = collect(json_decode($data['products'] ?? $data, true));
            $productModel = config('rapidez.models.product');
            /** @var \Rapidez\Core\Models\Product $productInstance */
            $productInstance = new $productModel;
            $dbProducts = $productModel::with('options')
                ->whereIn($productInstance->qualifyColumn('sku'), $products->map(fn($product) => $product['sku']))
                ->get()
                ->keyBy('sku');

            return $products->map(function($product) use ($dbProducts, $store) {
                $dbProduct = $dbProducts[$product['sku']] ?? null;

                $productOptions = $dbProduct
                    ? collect($product['options'] ?? [])->map(function (string $optionValue, string $option) use ($dbProduct): array {
                        $optionData = collect($dbProduct->options)->first(fn ($productOption) => $productOption->option_id == $option) ?? null;
                        $value = collect($optionData?->values ?? [])->first(fn ($value) => $value->option_type_id == $optionValue) ?? null;

                        return [
                            'title' => $optionData?->title ?? $option,
                            'price' => ($value?->price->price ?? 0) + ($optionData?->price->price ?? 0),
                            'value' => $value ?? ['title' => $optionValue],
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
        });
    }
}
