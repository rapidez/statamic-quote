# Rapidez Statamic Quote

> [!NOTE]
> This package requires the Rapidez+Statamic combination to be installed and set up.

Allows you to add a "Request quote" button anywhere with products present.

## Installation

You can install this package using Composer:

```sh
composer install rapidez/statamic-quote
```

Then, you'll need to publish the blueprint and form field. You're probably also going to want to publish the config file:

```sh
php artisan vendor:publish --tag=quote-content
php artisan vendor:publish --tag=quote-config
```

After publishing these, we would also recommend adding a default country to the country dictionary field in the form blueprint.

## Usage

This package does not create any "request invoice" buttons on the frontend by default. These will need to be added by the developer. The simplest way to do this is by adding this button to your product page:

```blade
<x-rapidez-quote::button v-bind:add-products="{
    sku: addToCart.simpleProduct.sku,
    qty: addToCart.qty,
    options: addToCart.customSelectedOptions,
}">
    @lang('Request quote')
</x-rapidez-quote::button>
```

You can also pass an array to `add-products`, which allows you to push an array of products to the quote. This can be useful for turning a cart into a quote request:

```blade
<x-rapidez-quote::button v-bind:add-products="cart.items.map(item => ({
    sku: item.product.sku,
    qty: item.quantity,
    options: item.options,
}))">
    @lang('Request quote')
</x-rapidez-quote::button>
```

### Hooking into the quote data

You can use the `quote.data` Eventy filter to hook into the data that's being sent to the automatic quote:

```php
Eventy::addFilter('quote.data', function ($quoteData) {
    return [
        ...$quoteData,
        'products' => $quoteData['products']->map([...])
    ];
});
```

You can also return `null` to not send the quote under certain conditions, for example:

```php
Eventy::addFilter('quote.data', function ($quoteData) {
    if ($quoteData['products']->contains(fn($item) => $item['product']->no_quote)) {
        // Don't send
        return null;
    }
    
    return $quoteData;
});
```

## Automatic PDF

By default, this package will automatically generate a quote for you based on the products. However, you may not want this to happen for various reasons. To this end, you can disable this functionality by setting the `auto_send_quote` config setting to `false`.

This can be done store-specific as well, see the [multistore configuration](https://docs.rapidez.io/4.x/configuration.html#multistore) section in the Rapidez docs.

## Styling

### Modifying the PDF

You can overwrite the `rapidez-quote.exports.quote` view to do most of the basic modifications you might need. However, if you want to completely upend the styling of the pdf (or change the logo to not be an SVG), you can do that by overwriting `rapidez-quote.exports.base`.

Note that the "primary" color and the path to the logo svg are defined by the quote configuration file.

### Modifying the Email

Similarly, the email that gets sent to the customer requesting the quote can be modified by overwriting the `rapidez-quote.mail.quote` view. This is defined as markdown content by default, but that can be changed by modifying the `email_markdown` config setting.
