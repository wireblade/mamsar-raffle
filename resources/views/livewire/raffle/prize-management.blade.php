
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a
                href="{{ route('raffle.events') }}"
                wire:navigate
                class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-zinc-500 transition hover:text-orange-600 dark:text-zinc-400 dark:hover:text-orange-400"
            >
                <flux:icon.arrow-left class="size-4" />
                Back to Events
            </a>

            <flux:heading size="xl">
                Prize Management
            </flux:heading>

            <flux:text class="mt-2">
                {{ $event->name }}
            </flux:text>
        </div>

        <flux:badge color="blue" size="lg">
            {{ $sequences->sum(fn ($sequence) => $sequence->prizes->count()) }} Prize Slots
        </flux:badge>
    </div>


{{-- Floating Success Notification --}}
<div
    x-data="{
        show: false,
        message: '',
        timeout: null,

        notify(text) {
            this.message = text;
            this.show = true;

            clearTimeout(this.timeout);

            this.timeout = setTimeout(() => {
                this.show = false;
            }, 4000);
        }
    }"
    x-on:prize-saved.window="notify($event.detail.message)"
>
    <template x-teleport="body">
        <div
            x-show="show"
            x-transition.opacity
            x-cloak
            class="fixed top-5 left-1/2 z-[9999] w-[calc(100%-2rem)] max-w-md -translate-x-1/2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-xl dark:border-green-800 dark:bg-green-900 dark:text-green-300"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <flux:icon.check-circle class="size-5" />

                    <span x-text="message"></span>
                </div>

                <button
                    type="button"
                    x-on:click="show = false"
                    class="text-green-700 hover:text-green-900 dark:text-green-300"
                    aria-label="Dismiss notification"
                >
                    &times;
                </button>
            </div>
        </div>
    </template>
</div>


    {{-- Raffle Sequences --}}
    @forelse ($sequences as $sequence)
        <section
            wire:key="prize-sequence-{{ $sequence->id }}"
            class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
            {{-- Sequence Header --}}
<div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div>
                    <flux:heading size="lg">
                        Sequence {{ $sequence->sequence_number }}
                    </flux:heading>

                    <flux:text class="mt-1 text-sm">
                        {{ $sequence->prizes->count() }} Prize Items
                    </flux:text>
                </div>

                <flux:badge>
                    {{ ucfirst($sequence->status) }}
                </flux:badge>
            </div>

            {{-- Prize Items --}}
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($sequence->prizes as $prize)
                    <div
                        wire:key="manage-prize-{{ $prize->id }}"
                        class="space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-zinc-900 dark:text-white">
                                Item {{ $prize->item_number }}
                            </span>

                            @if ($prize->status === 'pending')
                                <flux:badge color="amber">
                                    Pending
                                </flux:badge>
                            @elseif ($prize->status === 'drawing')
                                <flux:badge color="blue">
                                    Drawing
                                </flux:badge>
                            @else
                                <flux:badge color="green">
                                    Completed
                                </flux:badge>
                            @endif
                        </div>

                        <flux:input
                            label="Prize Name"
                            placeholder="e.g. Rice Cooker"
                            wire:model="prizes.{{ $prize->id }}.name"
                            :disabled="$prize->status !== 'pending'"
                        />

                        <flux:input
                            type="number"
                            min="1"
                            label="Target Winners"
                            wire:model="prizes.{{ $prize->id }}.target_winners"
                            :disabled="$prize->status !== 'pending'"
                        />

                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                Allocated: {{ $prize->allocated_winners }}
                            </span>

                            <flux:button
                                size="sm"
                                variant="primary"
                                icon="check"
                                wire:click="savePrize({{ $prize->id }})"
                                wire:loading.attr="disabled"
                                wire:target="savePrize({{ $prize->id }})"
                                :disabled="$prize->status !== 'pending'"
                            >
                                Save Prize
                            </flux:button>
                        </div>

                        @error('prizes.' . $prize->id)
                            <p class="text-sm text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-xl border border-zinc-200 p-8 text-center dark:border-zinc-700">
            <flux:text>
                No raffle sequences found for this event.
            </flux:text>
        </div>
    @endforelse

</div>
