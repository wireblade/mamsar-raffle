<div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
<a
    href="{{ route('raffle.events') }}"
    wire:navigate
    class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-zinc-500 transition hover:text-orange-600 dark:text-zinc-400 dark:hover:text-orange-400"
>
    <flux:icon.arrow-left class="size-4" />
    Back to Events
</a>
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">
                Front Desk Registration
            </flux:heading>

            <flux:text class="mt-2">
                {{ $event->name }}
                @if ($event->event_date)
                    &bull; {{ $event->event_date->format('M d, Y') }}
                @endif
            </flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-3">

    {{-- Registration Status --}}
    @if ($event->status === 'registration')
        <flux:badge color="green" size="lg">
            Registration Open
        </flux:badge>
    @else
        <flux:badge color="zinc" size="lg">
            {{ ucfirst($event->status) }}
        </flux:badge>
    @endif

    {{-- Draw Control --}}
    @if (in_array($event->status, ['ready', 'ongoing', 'completed']))
        <flux:button
            size="sm"
            variant="primary"
            icon="play"
            :href="route('raffle.draw', $event->id)"
            wire:navigate
        >
            Draw Control
        </flux:button>
    @endif

    {{-- Audience Display --}}
    @if (in_array($event->status, ['ready', 'ongoing', 'completed']))
        <flux:button
            size="sm"
            variant="outline"
            icon="computer-desktop"
            :href="route('raffle.audience', $event->id)"
            target="_blank"
        >
            Audience Display
        </flux:button>
    @endif

</div>
    </div>

    {{-- Success Notification --}}
    @if (session()->has('registration_success'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 4000)"
            x-show="show"
            x-transition
            class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
        >
            {{ session('registration_success') }}
        </div>
    @endif

    {{-- Registration Overview --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Registered Attendees
            </p>

            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">
                {{ number_format($attendeeCount) }}
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Minimum Required for Raffle
            </p>

            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">
                474
            </p>

            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                Reserved winner slots for sequences 3–9
            </p>
        </div>
    </div>

    {{-- Registration Form --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">

        <flux:heading size="lg">
            Register Attendee
        </flux:heading>

        <flux:text class="mt-1">
            Enter the raffle number of an attendee who is physically present.
        </flux:text>

        <form wire:submit="registerAttendee" class="mt-6 space-y-4">

            <flux:input
                wire:model="raffleNumber"
                label="Raffle Number"
                placeholder="Enter raffle number"
                autocomplete="off"
                autofocus
                :disabled="$event->status !== 'registration'"
            />

            <div class="flex justify-end">
                <flux:button
                    type="submit"
                    variant="primary"
                    icon="plus"
                    wire:loading.attr="disabled"
                    wire:target="registerAttendee"
                    :disabled="$event->status !== 'registration'"
                >
                    Register Attendee
                </flux:button>
            </div>

        </form>

        @if ($event->status !== 'registration')
            <p class="mt-4 text-sm text-amber-600 dark:text-amber-400">
                Registration is currently closed for this event.
            </p>
        @endif
    </div>

    {{-- Registered Attendees --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">

        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <flux:heading size="lg">
                Registered Attendees
            </flux:heading>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3">Raffle Number</th>
                        <th class="px-5 py-3">Registered At</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($attendees as $attendee)
                        <tr wire:key="attendee-{{ $attendee->id }}">
                            <td class="px-5 py-4 font-semibold text-zinc-900 dark:text-white">
                                {{ $attendee->raffle_number }}
                            </td>

                            <td class="px-5 py-4 text-zinc-500 dark:text-zinc-400">
                                {{ $attendee->created_at->format('M d, Y h:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-5 py-10 text-center text-zinc-500">
                                No attendees registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($attendees->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-700">
                {{ $attendees->links() }}
            </div>
        @endif
    </div>

</div>
