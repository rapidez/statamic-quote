<?php

namespace Rapidez\StatamicQuote\Fieldtypes;

use Illuminate\Support\Arr;
use Rapidez\Core\Facades\Rapidez;
use Rapidez\Core\Models\QuoteItemOption;
use Statamic\Exceptions\AssetContainerNotFoundException;
use Statamic\Facades\Asset;
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
                    'max_upload_size' => [
                        'display' => __('Maximum File Size (KB)'),
                        'type' => 'integer',
                        'default' => '10240',
                    ],
                    'allowed_filetypes' => [
                        'display' => __('Allowed Filetypes'),
                        'instructions' => __('The file extensions that are allowed to be uploaded, separated by a comma'),
                        'type' => 'text',
                        'required' => true,
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

        if ($this->config('allow_uploads') && $this->config('container')) {
            $uploaded = $customOptions->map(function ($options, $id) {
                return collect($options)->mapWithKeys(function ($value, $key) {
                    $file = collect(is_string($value) ? json_decode($value) : $value);

                    $isCartData = $file->has('customizable_option_uid');
                    return $isCartData ? $this->handleCartDataUpload($file) : $this->handleFileUpload($file, $key);
                });
            });
        }

        return [
            'store' => config('rapidez.store'),
            'products' => $savedProducts->toJson(),
            'uploaded' => $uploaded,
        ];
    }

    protected function handleFileUpload($file, $optionId)
    {
        $name = basename($file['name'] ?? '');
        if (! $name || ! $this->isAllowedFileType($name)) {
            return null;
        }

        $data = $file['base64_encoded_data'] ?? null;
        if (! $data) {
            return null;
        }

        // 1365 here is 1024 / 6 * 8. This is to account for base64 being larger than the actual file size.
        if (strlen($data) / 1365 > $this->config('max_upload_size')) {
            throw new \Exception('File exceeds the maximum upload size');
        }

        return [$optionId => $this->valueToId($name, $data)];
    }

    protected function handleCartDataUpload($file)
    {
        $optionUid = base64_decode($file['customizable_option_uid']);
        $optionId = explode('/', $optionUid)[1] ?? null;
        $optionId = filter_var($optionId, FILTER_VALIDATE_INT);
        if ($optionId === false) {
            return null;
        }

        // Get first file data from the option and check if it's really there
        $fileData = $file['values'][0] ?? null;
        if (! $fileData) {
            return null;
        }

        // Check name and valueId from said data
        $name = $fileData['value'] ?? null;
        $valueId = $fileData['id'] ?? null;
        if (! $name || ! $valueId || ! $this->isAllowedFileType($name)) {
            return null;
        }

        // Check if the quote item exists with the correct option id
        $itemOption = QuoteItemOption::find($valueId);
        if (! $itemOption || ($itemOption->code !== 'option_' . $optionId)) {
            return null;
        }

        // Also double check whether the given name checks out
        $optionData = $itemOption->value;
        if (! $optionData->fullpath || $optionData->title !== $name) {
            return null;
        };

        if ($optionData->size / 1024 > $this->config('max_upload_size')) {
            throw new \Exception('File exceeds the maximum upload size');
        }

        // Once we get here we can be certain the data is legitimate
        // TODO: This assumes that your Magento installation is on the same server as your Rapidez installation
        // Might be worthwhile to just get the file from its url instead (but this would require allow_url_fopen to be enabled)
        $data = fopen($optionData->fullpath, 'r');

        return [$optionId => $this->valueToId($name, $data)];
    }

    protected function isAllowedFileType($name): bool
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = explode(',', $this->config('allowed_filetypes'));
        return in_array($extension, $allowed);
    }

    public function preProcess($products)
    {
        return $this->augment($products);
    }

    protected function valueToId($name, $value)
    {
        $folder = now()->format('YmdHis');
        $path = "$folder/$name";

        $asset = $this->container()->makeAsset($path);
        if ($asset->exists()) {
            return $asset->id();
        }

        $fileData = is_resource($value) ? stream_get_contents($value) : base64_decode($value, true);

        $this->container()->disk()->put($path, $fileData);
        $asset = $this->container()->makeAsset($path);
        $asset->save();

        return $asset->id();
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
        $uploaded = $data['uploaded'] ?? [];

        return Rapidez::withStore($store, function() use ($data, $store, $uploaded) {
            $products = collect(json_decode($data['products'] ?? $data, true));
            $productModel = config('rapidez.models.product');
            /** @var \Rapidez\Core\Models\Product $productInstance */
            $productInstance = new $productModel;
            $dbProducts = $productModel::withoutGlobalScopes()
                ->whereIn($productInstance->qualifyColumn('sku'), $products->map(fn($product) => $product['sku']))
                ->get()
                ->keyBy('sku');

            return $products->map(function($product) use ($dbProducts, $store, $uploaded) {
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

                $currentUploaded = collect($uploaded[$product['id'] ?? ''] ?? [])
                    ->map(function ($id, $optionId) use ($dbProduct) {
                        $asset = Asset::findById($id);
                        if (!$asset) {
                            return null;
                        }

                        $option = collect($dbProduct->options)->first(fn ($productOption) => $productOption->option_id == $optionId);

                        return [
                            'path' => $asset->url(),
                            'filename' => basename($asset->url()),
                            'option' => $option->title ?? $optionId,
                        ];
                    })
                    ->whereNotNull();

                return [
                    ...$product,
                    'uploaded' => $currentUploaded,
                    'store' => $store,
                    'product' => $dbProduct,
                    'options' => $productOptions,
                    'totalPrice' => $totalPrice,
                ];
            });
        });
    }
}
