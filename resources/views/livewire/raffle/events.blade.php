<div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
    @php
        $statusColors = [
            'draft' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
            'registration' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
            'ready' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
            'ongoing' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
            'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        ];
        // These figures summarize the events currently supplied to this view.
        $eventCollection = collect($events);
    @endphp

    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Raffle Events</flux:heading>
            <flux:text class="mt-2">Create and manage MAMSAR raffle events.</flux:text>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if (app()->environment(['local', 'testing']))
                <flux:button variant="outline" icon="beaker" :href="route('raffle.test-data-generator')" wire:navigate>
                    Test Data Generator
                </flux:button>
            @endif
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                Create Event
            </flux:button>
        </div>
    </div>

    {{-- Notifications --}}
    @if (session()->has('success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-cloak
             class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif
    @error('eventStatus')
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror

    {{-- Overview of events currently loaded --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Events</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($eventCollection->count()) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Registered Attendees</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($eventCollection->sum('attendees_count')) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Completed Events</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($eventCollection->where('status', 'completed')->count()) }}</p>
        </div>
    </div>

    {{-- Event list --}}
    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">All Raffle Events</h2>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Use the primary action to advance an event, or open More Actions for other pages.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[780px] text-left text-sm">
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
                        <tr wire:key="event-{{ $event->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-5 py-4 font-semibold text-zinc-900 dark:text-white">{{ $event->name }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ $event->event_date?->format('M d, Y') ?? 'Not set' }}</td>
                            <td class="px-5 py-4 tabular-nums text-zinc-600 dark:text-zinc-300">{{ number_format($event->attendees_count) }}</td>
                            <td class="px-5 py-4 tabular-nums text-zinc-600 dark:text-zinc-300">{{ $event->sequences_count }} / 9</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusColors[$event->status] ?? $statusColors['draft'] }}">
                                    {{ ucfirst($event->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    {{-- Contextual primary action --}}
                                    @if ($event->status === 'draft')
                                        <flux:button size="sm" variant="primary" icon="play"
                                            wire:click="openRegistration({{ $event->id }})"
                                            wire:confirm="Open registration for this event?"
                                            wire:loading.attr="disabled" wire:target="openRegistration({{ $event->id }})">
                                            Open Registration
                                        </flux:button>
                                    @elseif ($event->status === 'registration')
                                        <flux:button size="sm" variant="danger" icon="lock-closed"
                                            wire:click="closeRegistration({{ $event->id }})"
                                            wire:confirm="Are you sure you want to close registration? You will no longer be able to register attendees."
                                            wire:loading.attr="disabled" wire:target="closeRegistration({{ $event->id }})">
                                            Close Registration
                                        </flux:button>
                                    @elseif (in_array($event->status, ['ready', 'ongoing']))
                                        <flux:button size="sm" variant="primary" icon="play" :href="route('raffle.draw', $event->id)" wire:navigate>
                                            Draw Control
                                        </flux:button>
                                    @elseif ($event->status === 'completed')
                                        <flux:button size="sm" variant="primary" icon="chart-bar"
                                            :href="route('raffle.results', ['event' => $event->id])" wire:navigate>
                                            View Results
                                        </flux:button>
                                    @endif

                                    {{-- Flux dropdown avoids a clipped inline menu inside the scrolling table. --}}
                                    

<div
    x-data="{
        open: false,
        position: {},

        updatePosition() {
            const rect = this.$refs.trigger.getBoundingClientRect();

            const menuWidth = 208;
            const menuHeight = 230;

            const spaceBelow = window.innerHeight - rect.bottom;
            const showAbove = spaceBelow < menuHeight
                && rect.top > spaceBelow;

            this.position = {
                position: 'fixed',
                left: `${Math.max(8, Math.min(
                    rect.right - menuWidth,
                    window.innerWidth - menuWidth - 8
                ))}px`,
                top: showAbove
                    ? `${Math.max(8, rect.top - menuHeight - 8)}px`
                    : `${rect.bottom + 8}px`,
                width: `${menuWidth}px`,
                zIndex: 9999
            };
        },

        toggle() {
            if (this.open) {
                this.open = false;
                return;
            }

            this.updatePosition();
            this.open = true;
        }
    }"
>
    <div x-ref="trigger">
        <flux:button
            size="sm"
            variant="outline"
            icon:trailing="chevron-down"
            x-on:click="toggle()"
        >
            More Actions
        </flux:button>
    </div>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-bind:style="position"
            x-on:click.outside="open = false"
            x-on:keydown.escape.window="open = false"
            x-on:resize.window="if (open) updatePosition()"
            x-on:scroll.window="open = false"
            class="rounded-xl border border-zinc-200 bg-white p-1 shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
        >
            <a
                href="{{ route('raffle.registration', $event->id) }}"
                class="block rounded-lg px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                View Registration
            </a>

            <a
                href="{{ route('raffle.prizes', $event->id) }}"
                class="block rounded-lg px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                Manage Prizes
            </a>

            @if (in_array($event->status, ['ready', 'ongoing', 'completed']))
                <a
                    href="{{ route('raffle.draw', $event->id) }}"
                    class="block rounded-lg px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Draw Control
                </a>

                <a
                    href="{{ route('raffle.audience', $event->id) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="block rounded-lg px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Audience Display
                </a>
            @endif

            <a
                href="{{ route('raffle.results', ['event' => $event->id]) }}"
                class="block rounded-lg px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                View Results
            </a>
        </div>
    </template>
</div>


                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="font-medium text-zinc-900 dark:text-white">No raffle events yet</p>
                                <p class="mt-2 text-sm text-zinc-500">Create your first raffle event to get started.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create Event Modal: original Livewire bindings preserved --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" wire:key="create-event-modal">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900">
                <div class="mb-6 flex items-center justify-between">
                    <flux:heading size="lg">Create Raffle Event</flux:heading>
                    <flux:button variant="ghost" icon="x-mark" wire:click="closeCreateModal" />
                </div>
                <form wire:submit="createEvent" class="space-y-5">
                    <flux:input wire:model="name" label="Event Name" placeholder="e.g. MAMSAR Christmas Party 2026" required />
                    <flux:input wire:model="eventDate" type="date" label="Event Date" />
                    <div class="rounded-lg bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        This event will automatically include <strong>9 raffle sequences</strong> and <strong>55 prize slots</strong>.
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <flux:button type="button" variant="ghost" wire:click="closeCreateModal">Cancel</flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="createEvent">Create Event</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
