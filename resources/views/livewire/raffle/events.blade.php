
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">
                Raffle Events
            </flux:heading>

            <flux:text class="mt-2">
                Create and manage MAMSAR raffle events.
            </flux:text>
        </div>

        <flux:button
            variant="primary"
            icon="plus"
            wire:click="openCreateModal"
        >
            Create Event
        </flux:button>
    </div>

    {{-- Success Message --}}
    @if (session()->has('success'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 4000)"
            x-show="show"
            x-transition
            class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
        >
            {{ session('success') }}
        </div>
    @endif

    @error('eventStatus')
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
        {{ $message }}
    </div>
@enderror

    {{-- Events Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-4">Event</th>
                        <th class="px-5 py-4">Date</th>
                        <th class="px-5 py-4">Attendees</th>
                        <th class="px-5 py-4">Sequences</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($events as $event)
                        <tr wire:key="event-{{ $event->id }}"
                            class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">

                            <td class="px-5 py-4 font-medium text-zinc-900 dark:text-white">
                                {{ $event->name }}
                            </td>

                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">
                                {{ $event->event_date?->format('M d, Y') ?? 'Not set' }}
                            </td>

                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">
                                {{ number_format($event->attendees_count) }}
                            </td>

                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">
                                {{ $event->sequences_count }} / 9
                            </td>

                            <td class="px-5 py-4">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
                                        'registration' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                        'ready' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                        'ongoing' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                                        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                    ];
                                @endphp

                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusColors[$event->status] ?? $statusColors['draft'] }}">
                                    {{ ucfirst($event->status) }}
                                </span>
                            </td>
                            
{{-- Event Actions --}}
<td class="px-5 py-4">
    <div class="flex items-center justify-end gap-2">

        @if ($event->status === 'draft')
            <flux:button
                size="sm"
                variant="primary"
                icon="play"
                wire:click="openRegistration({{ $event->id }})"
                wire:confirm="Open registration for this event?"
            >
                Open Registration
            </flux:button>
        @endif


@if ($event->status === 'registration')
    <flux:button
        size="sm"
        variant="danger"
        icon="lock-closed"
        wire:click="closeRegistration({{ $event->id }})"
        wire:confirm="Are you sure you want to close registration? You will no longer be able to register attendees."
        wire:loading.attr="disabled"
        wire:target="closeRegistration"
    >
        Close Registration
    </flux:button>
@endif


        <flux:button
            size="sm"
            variant="outline"
            icon="users"
            :href="route('raffle.registration', $event->id)"
            wire:navigate
        >
            View Registration
        </flux:button>

    </div>
</td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="space-y-2">
                                    <p class="font-medium text-zinc-900 dark:text-white">
                                        No raffle events yet
                                    </p>
                                    <p class="text-sm text-zinc-500">
                                        Create your first raffle event to get started.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create Event Modal --}}
    @if ($showCreateModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            wire:key="create-event-modal"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900">

                <div class="mb-6 flex items-center justify-between">
                    <flux:heading size="lg">
                        Create Raffle Event
                    </flux:heading>

                    <flux:button
                        variant="ghost"
                        icon="x-mark"
                        wire:click="closeCreateModal"
                    />
                </div>

                <form wire:submit="createEvent" class="space-y-5">

                    <flux:input
                        wire:model="name"
                        label="Event Name"
                        placeholder="e.g. MAMSAR Christmas Party 2026"
                        required
                    />

                    <flux:input
                        wire:model="eventDate"
                        type="date"
                        label="Event Date"
                    />

                    <div class="rounded-lg bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        This event will automatically include
                        <strong>9 raffle sequences</strong> and
                        <strong>55 prize slots</strong>.
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <flux:button
                            type="button"
                            variant="ghost"
                            wire:click="closeCreateModal"
                        >
                            Cancel
                        </flux:button>

                        <flux:button
                            type="submit"
                            variant="primary"
                            wire:loading.attr="disabled"
                            wire:target="createEvent"
                        >
                            Create Event
                        </flux:button>
                    </div>

                </form>
            </div>
        </div>
    @endif

</div>
