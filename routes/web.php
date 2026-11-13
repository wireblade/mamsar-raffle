<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Raffle\Events;
use App\Livewire\Raffle\Registration;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::get('/raffle/events', Events::class)
    ->name('raffle.events');
    Route::get('/raffle/events/{event}/registration', Registration::class)
    ->name('raffle.registration');

require __DIR__.'/settings.php';
