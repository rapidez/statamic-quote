<quote-data v-slot="quoteData" {{ $attributes }}>
    <x-rapidez::button.primary :href="route('quote.form')" v-on:click.prevent="quoteData.newQuote(quoteData.addProducts).then(() => window.Turbo.visit('{{ route('quote.form') }}'))">
        {{ $slot }}
    </x-rapidez::button.primary>
</quote-data>
