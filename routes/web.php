<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Raffle\AudienceDisplay;
use App\Livewire\Raffle\Events;
use App\Livewire\Raffle\Registration;
use App\Livewire\Raffle\DrawControl;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::get('/raffle/events', Events::class)
    ->name('raffle.events');
    Route::get('/raffle/events/{event}/registration', Registration::class)
    ->name('raffle.registration');
    Route::get('/raffle/events/{event}/draw', DrawControl::class)
    ->name('raffle.draw');
    Route::get('/raffle/events/{event}/audience', AudienceDisplay::class)
    ->name('raffle.audience');

require __DIR__.'/settings.php';
