<?php

namespace Rapidez\StatamicQuote\Fieldtypes;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rapidez\Core\Facades\Rapidez;
use Statamic\Exceptions\AssetContainerNotFoundException;
use Statamic\Facades\AssetContainer;
use Statamic\Fieldtypes\Assets\UndefinedContainerException;
use Statamic\Fields\Fieldtype;

class Products extends Fieldtype
{
    protected $selectable = false;
    protected $selectableInForms = true;

    protected function configFieldItems(): array
    {
        return [
            [
                'display' => __('Uploaded files'),
                'fields' => [
                    'allow_uploads' => [
                        'display' => __('Allow Uploads'),
                        'instructions' => __('statamic::fieldtypes.assets.config.allow_uploads'),
                        'type' => 'toggle',
                        'default' => true,
                    ],
                    'container' => [
                        'display' => __('Container'),
                        'instructions' => __('statamic::fieldtypes.assets.config.container'),
                        'type' => 'asset_container',
                        'max_items' => 1,
                        'mode' => 'select',
                        'required' => false,
                        'default' => AssetContainer::all()->count() == 1 ? AssetContainer::all()->first()->handle() : null,
                        'force_in_config' => true,
                    ],
                ],
            ],
        ];
    }

    public function process($value)
    {
        $products = collect(json_decode($value, true));

        $customOptions = $products->pluck('customOptions', 'id');
        $savedProducts = $products->map(fn($product) => Arr::except($product, 'customOptions'));

        //if ($this->config('allow_uploads') && $this->config('container')) {
            $uploaded = $customOptions->map(function ($options, $id) {
                collect($options)->map(function ($value, $key) {
                    $data = json_decode($value)->base64_encoded_data ?? null;
                    if (!$data) {
                        return null;
                    }

                    return $this->valueToId($data);
                });
            });
        //}
        //
        dd($uploaded);

        return [
            'store' => config('rapidez.store'),
            'products' => $savedProducts->toJson(),
        ];
    }

    public function preProcess($products)
    {
        dd($products);
        return $this->augment($products);
    }

    protected function valueToId($value)
    {
        if (Str::contains($value, '::')) {
            return $value;
        }

        return optional($this->container()->asset($value))->id();
    }

    protected function container()
    {
        if ($configured = $this->config('container')) {
            if ($container = AssetContainer::find($configured)) {
                return $container;
            }

            throw new AssetContainerNotFoundException($configured);
        }

        if (($containers = AssetContainer::all())->count() === 1) {
            return $containers->first();
        }

        throw new UndefinedContainerException;
    }

    public function augment($data)
    {
        $store = $data['store'] ?? 1;

        return Rapidez::withStore($store, function() use ($data, $store) {
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
