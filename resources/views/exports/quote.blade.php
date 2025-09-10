@extends('rapidez-quote::exports.base')

@section('recipient')
    <b>
        <div style="padding-top:4rem">
            {{ ucfirst($formData['first_name'])[0] }}. {{ ucfirst($formData['last_name']) }}
        </div>
        @if ($formData['company'])
            <div>{{ $formData['company'] }}</div>
        @endif
        <div>{{ $formData['address'] }}</div>
        <div>{{ $formData['zipcode'] }} {{ $formData['city'] }}</div>
        <div>{{ __('statamic::dictionary-countries.names.' . $formData['country']) }}</div>
    </b>
    <div>{{ $formData['phone'] }}</div>
@endsection

@section('subject')
    @lang('Quote') | {{ config('app.name') }}
@endsection

@section('text')
    <div>
        <table>
            @foreach($products as $item)
                <tr>
                    <td>{{ $item['product']['name'] }}</td>
                    <td>x{{ $item['qty'] }}</td>
                    <td>{{ price($item['totalPrice']) }}</td>
                </tr>
            @endforeach
            <tr>
                <td><b>@lang('Total (excl. VAT)')</b></td>
                <td></td>
                <td><b>{{ price($products->sum('totalPrice')) }}</b></td>
            </tr>
        </table>
    </div>
@endsection
