@extends('rapidez::layouts.app')

@section('content')
    <div class="container">
        <x-rapidez-statamic::form
            form-handle="quote_form"
            :success-text="__('The form was submitted successfully')"
            :button-text="__('Submit')"
        />
    </div>
@endsection