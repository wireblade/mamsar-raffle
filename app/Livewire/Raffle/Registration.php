<?php

namespace App\Livewire\Raffle;

use App\Models\RaffleAttendee;
use App\Models\RaffleEvent;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class Registration extends Component
{
    use WithPagination;
    public RaffleEvent $event;

    public string $raffleNumber = '';

    public function mount(RaffleEvent $event): void
    {
        $this->event = $event;
    }

    
public function registerAttendee(): void
{
    $this->raffleNumber = trim($this->raffleNumber);

    $this->validate([
        'raffleNumber' => [
            'required',
            'string',
            'max:50',
            Rule::unique('raffle_attendees', 'raffle_number')
                ->where('raffle_event_id', $this->event->id),
        ],
    ], [
        'raffleNumber.required' => 'Please enter a raffle number.',
        'raffleNumber.unique' => 'This raffle number is already registered.',
    ]);

    try {
        DB::transaction(function () {

            $event = RaffleEvent::query()
                ->lockForUpdate()
                ->findOrFail($this->event->id);

            if ($event->status !== 'registration') {
                $this->addError(
                    'raffleNumber',
                    'Registration is closed for this event.'
                );

                return;
            }

            RaffleAttendee::create([
                'raffle_event_id' => $event->id,
                'raffle_number' => $this->raffleNumber,
            ]);
        });
    } catch (QueryException $exception) {

        if ($exception->getCode() === '23505') {
            $this->addError(
                'raffleNumber',
                'This raffle number is already registered.'
            );

            return;
        }

        throw $exception;
    }

    if ($this->getErrorBag()->has('raffleNumber')) {
        return;
    }

    $registeredNumber = $this->raffleNumber;

    $this->reset('raffleNumber');
    $this->resetValidation();

    $this->event->refresh();

    session()->flash(
        'registration_success',
        "Raffle number {$registeredNumber} registered successfully."
    );
}


    public function render()
    {
        return view('livewire.raffle.registration', [
            'attendees' => $this->event
                ->attendees()
                ->latest()
                ->paginate(20),

            'attendeeCount' => $this->event
                ->attendees()
                ->count(),
        ])->layout('layouts.app.guest');
    }
}
