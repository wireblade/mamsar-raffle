<?php

namespace App\Livewire\Raffle;

use App\Services\RaffleTestDataService;
use Livewire\Component;

class TestDataGenerator extends Component
{
    public int $attendeeCount = 1130;

    private function authorizeTestAccess(): void
    {
        abort_unless(
            app()->environment(['local', 'testing']),
            403
        );
    }

    public function mount(): void
    {
        $this->authorizeTestAccess();
    }

    public function generateTestData(RaffleTestDataService $service): void
    {
        $this->authorizeTestAccess();

        $this->validate([
            'attendeeCount' => [
                'required',
                'integer',
                'min:474',
                'max:10000',
            ],
        ]);

        $service->generate($this->attendeeCount);

        session()->flash(
            'generator_success',
            "Test raffle created successfully with {$this->attendeeCount} attendees."
        );

        $this->redirectRoute(
            'raffle.events',
            navigate: true
        );
    }

    public function resetTestData(RaffleTestDataService $service): void
{
    $this->authorizeTestAccess();

    // Permanently delete all raffle records.
    $service->reset();

    session()->flash(
        'generator_success',
        'All raffle events, attendees, sequences, prizes, and winners have been deleted successfully.'
    );

    $this->redirectRoute('raffle.events', navigate: true);
}

    public function render()
    {
        return view('livewire.raffle.test-data-generator')
            ->layout('layouts.app.guest');
    }
}
