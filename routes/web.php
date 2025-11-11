<?php

Route::middleware('web')->group(function () {
    Route::view('request-quote', 'rapidez-quote::quote-form')->name('quote.form');
});
