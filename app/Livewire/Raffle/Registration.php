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
    public string $search = '';

    public function updatedSearch(): void
{
    $this->resetPage();
}

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

    $this->dispatch('registration-focus');
}


    public function render()
{
    $fixedWinnerSlots = $this->event->sequences()
        ->whereBetween('sequence_number', [3, 9])
        ->with('prizes')
        ->get()
        ->sum(fn ($sequence) => $sequence->prizes->sum('target_winners'));

    $totalAllocatedWinners = $this->event->sequences()
        ->with('prizes')
        ->get()
        ->sum(fn ($sequence) => $sequence->prizes->sum('allocated_winners'));

    $attendees = $this->event
        ->attendees()
        ->when(trim($this->search) !== '', function ($query) {
            $query->where(
                'raffle_number',
                'ilike',
                '%' . trim($this->search) . '%'
            );
        })
        ->latest()
        ->paginate(20);

    return view('livewire.raffle.registration', [
        'attendees' => $attendees,
        'fixedWinnerSlots' => $fixedWinnerSlots,
        'totalAllocatedWinners' => $totalAllocatedWinners,
        'attendeeCount' => $this->event
            ->attendees()
            ->count(),
    ])->layout('layouts.app.guest');
}
}
