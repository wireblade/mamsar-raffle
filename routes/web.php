<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Raffle\AudienceDisplay;
use App\Livewire\Raffle\Events;
use App\Livewire\Raffle\Registration;
use App\Livewire\Raffle\DrawControl;
use App\Livewire\Raffle\PrizeManagement;
use App\Livewire\Raffle\TestDataGenerator;
use App\Livewire\Raffle\ResultsSummary;

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
    Route::get('/raffle/events/{event}/prizes', PrizeManagement::class)
    ->name('raffle.prizes');
    Route::get('/raffle/test-data-generator', TestDataGenerator::class)
    ->name('raffle.test-data-generator');

    Route::get('/raffle/events/{event}/results', ResultsSummary::class)
    ->name('raffle.results');

require __DIR__.'/settings.php';
