<?php

namespace Rapidez\Quote\Fieldtypes;

use Illuminate\Support\Arr;
use Rapidez\Core\Facades\Rapidez;
use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;

class Products extends Fieldtype
{
    protected $selectable = false;
    protected $selectableInForms = true;
    
    public function preProcess($products)
    {
        return $this->augment($products);
    }

    public function augment($products)
    {
        // Make sure to set the correct Rapidez store before trying to retrieve the products
        Rapidez::setStore(Site::current()->handle);

        $products = collect(json_decode($products, true));
        $productModel = config('rapidez.models.product');
        /** @var \Rapidez\Core\Models\Product $productInstance */
        $productInstance = new $productModel;
        $dbProducts = $productModel::withoutGlobalScopes()
            ->whereIn($productInstance->qualifyColumn('sku'), $products->map(fn($product) => $product['sku']))
            ->get()
            ->keyBy('sku');

        return $products->map(function($product) use ($dbProducts) {
            $dbProduct = $dbProducts[$product['sku']] ?? null;
            $productOptions = collect($product['options'] ?? [])->mapWithKeys(function (string $optionValue, string $option) use ($dbProduct): array {
                $option = Arr::firstOrFail($dbProduct->options, fn ($productOption) => $productOption->option_id == $option);
                $value = Arr::firstOrFail($option->values, fn ($value) => $value->option_type_id == $optionValue);

                return [
                    'title' => $option->title,
                    'price' => ($value?->price ?? 0) + ($option->price ?? 0),
                    'value' => $value,
                ];
            });

            $totalPrice = ($productOptions->sum('price') + $dbProduct->price) * $product['qty'];

            return [
                ...$product,
                'store' => config('rapidez.store'),
                'product' => $dbProduct,
                'options' => $productOptions,
                'totalPrice' => $totalPrice,
            ];
        });
    }
}