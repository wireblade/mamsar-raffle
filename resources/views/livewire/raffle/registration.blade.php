<div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
    {{-- Navigation --}}
    <a href="{{ route('raffle.events') }}" wire:navigate
        class="inline-flex items-center gap-2 text-sm font-medium text-zinc-500 transition hover:text-orange-600 dark:text-zinc-400 dark:hover:text-orange-400">
        <flux:icon.arrow-left class="size-4" />
        Back to Events
    </a>

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="xl">Front Desk Registration</flux:heading>
                @if ($event->status === 'registration')
                    <flux:badge color="green" size="lg">Registration Open</flux:badge>
                @else
                    <flux:badge color="zinc" size="lg">{{ ucfirst($event->status) }}</flux:badge>
                @endif
            </div>
            <flux:text>
                {{ $event->name }}
                @if ($event->event_date)
                    &bull; {{ $event->event_date->format('M d, Y') }}
                @endif
            </flux:text>
        </div>
        @if (in_array($event->status, ['ready', 'ongoing', 'completed'], true))
            <div class="flex flex-wrap items-center gap-2">
                <flux:button size="sm" variant="primary" icon="play"
                    :href="route('raffle.draw', $event->id)" wire:navigate>
                    Draw Control
                </flux:button>
                <flux:button size="sm" variant="outline" icon="computer-desktop"
                    :href="route('raffle.audience', $event->id)" target="_blank">
                    Audience Display
                </flux:button>
            </div>
        @endif
    </div>

    {{-- Success message: preserves the existing session flash mechanism. --}}
    @if (session()->has('registration_success'))
        <div x-data="{ show: true, timer: null }"
            x-init="timer = setTimeout(() => show = false, 4000)"
            x-show="show" x-transition.opacity x-cloak
            class="flex items-start justify-between gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
            <div class="flex items-center gap-2">
                <flux:icon.check-circle class="size-5 shrink-0" />
                <span>{{ session('registration_success') }}</span>
            </div>
            <button type="button" x-on:click="show = false" aria-label="Dismiss notification"
                class="shrink-0 text-lg leading-none">&times;</button>
        </div>
    @endif

    {{-- Registration comes first, so staff can enter numbers immediately. --}}
    <section class="overflow-hidden rounded-2xl border border-orange-200 bg-white shadow-sm dark:border-orange-900/60 dark:bg-zinc-900">
        <div class="border-b border-orange-100 bg-orange-50/70 px-5 py-4 dark:border-orange-900/40 dark:bg-orange-950/20 sm:px-6">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">
                    <flux:icon.ticket class="size-5" />
                </div>
                <div>
                    <flux:heading size="lg">Register Attendee</flux:heading>
                    <flux:text class="mt-1 text-sm">Enter the wristband's raffle number for an attendee who is physically present.</flux:text>

                    
                </div>
            </div>
        </div>

        <div class="p-5 sm:p-6">
            @if ($event->status === 'registration')
                <form wire:submit="registerAttendee" class="space-y-4"
                    x-data="{ focusInput() { this.$nextTick(() => this.$refs.raffleInput?.focus()); } }"
                    x-init="focusInput()">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                        <div>
                            <label for="front-desk-raffle-number"
                                class="mb-2 block text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                Raffle Number
                            </label>
                            <input
    id="front-desk-raffle-number"
    x-ref="raffleInput"
    x-on:registration-focus.window="$nextTick(() => $refs.raffleInput.focus())"
    type="text"
    inputmode="text"
    autocomplete="off"
    autocapitalize="off"
    spellcheck="false"
    autofocus
    wire:model="raffleNumber"
    placeholder="Enter or scan raffle number"
    class="block w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-lg font-semibold tracking-wide text-zinc-900 shadow-sm outline-none transition placeholder:text-base placeholder:font-normal placeholder:tracking-normal placeholder:text-zinc-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500"
/>
                            @error('raffleNumber')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <flux:button type="submit" variant="primary" icon="plus"
                            wire:loading.attr="disabled" wire:target="registerAttendee"
                            class="w-full lg:w-auto">
                            Register Attendee
                        </flux:button>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>Press Enter to register. Keep leading zeros in raffle numbers.</span>
                        <span wire:loading wire:target="registerAttendee" class="font-medium text-orange-600 dark:text-orange-400">
                            Registering attendee…
                        </span>
                    </div>
                </form>
            @else
                <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-300">
                    <flux:icon.lock-closed class="mt-0.5 size-5 shrink-0" />
                    <div>
                        <p class="font-semibold">Registration is currently closed</p>
                        <p class="mt-1">Attendees can only be registered while this event is in registration status.</p>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Event statistics --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Registered Attendees</p>
                <flux:icon.users class="size-5 text-orange-500" />

                
            </div>
            <p class="mt-3 text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($attendeeCount) }}</p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Total registered for this event</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Reserved Winner Slots</p>
                <flux:icon.lock-closed class="size-5 text-zinc-400" />
            </div>
            <p class="mt-3 text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($fixedWinnerSlots) }}</p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Fixed allocations for Sequences 3–9</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Allocated Winners</p>
                <flux:icon.trophy class="size-5 text-zinc-400" />
            </div>
            <p class="mt-3 text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($totalAllocatedWinners) }}</p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                @if (in_array($event->status, ['ready', 'ongoing', 'completed'], true))
                    Final allocation across all 9 sequences
                @else
                    Final allocation is calculated when registration closes
                @endif
            </p>
        </div>
    </div>

    {{-- Attendee list: retains the current collection and pagination. --}}
    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 border-b border-zinc-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-zinc-700">

    <div>
        <flux:heading size="lg">
            Registered Attendees
        </flux:heading>

        <flux:text class="mt-1 text-sm">
            Review raffle numbers recorded for this event.
        </flux:text>
    </div>

    <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">

        <div class="w-full sm:w-72">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search raffle number..."
                clearable
            />
        </div>

        <flux:badge color="zinc">
            {{ number_format($attendeeCount) }} Registered
        </flux:badge>

    </div>

</div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800/70 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="px-5 py-3 sm:px-6">Raffle Number</th>
                        <th scope="col" class="px-5 py-3">Registered At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($attendees as $attendee)
                        <tr wire:key="attendee-{{ $attendee->id }}" class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-5 py-4 font-semibold tracking-wide text-zinc-900 dark:text-white sm:px-6">
                                {{ $attendee->raffle_number }}
                            </td>
                            <td class="px-5 py-4 text-zinc-500 dark:text-zinc-400">
                                {{ $attendee->created_at->format('M d, Y h:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-5 py-12 text-center sm:px-6">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-2">
                                    <flux:icon.users class="size-8 text-zinc-400" />
                                    <p class="font-medium text-zinc-800 dark:text-zinc-200">No attendees registered yet</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Registered raffle numbers will appear here.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($attendees->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-700 sm:px-6">
                {{ $attendees->links() }}
            </div>
        @endif
    </section>
</div>
