<quote-data v-slot="quoteData" {{ $attributes }}>
    <x-rapidez::button.primary href="{{ route('quote.form') }}" v-on:click="quoteData.newQuote(quoteData.addProducts)">
        {{ $slot }}
    </x-rapidez::button.primary>
</quote-data>