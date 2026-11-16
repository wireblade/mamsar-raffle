<div
    x-data="{
        selectedSequence: Number(new URLSearchParams(window.location.search).get('prize_sequence')) || {{ (int) ($sequences->first()?->sequence_number ?? 1) }},
        selectSequence(number) {
            this.selectedSequence = Number(number);
            const url = new URL(window.location.href);
            url.searchParams.set('prize_sequence', String(number));
            window.history.replaceState(window.history.state, '', url.toString());
        }
    }"
    class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8"
>
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('raffle.events') }}" wire:navigate
                class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-zinc-500 transition hover:text-orange-600 dark:text-zinc-400 dark:hover:text-orange-400">
                <flux:icon.arrow-left class="size-4" />
                Back to Events
            </a>
            <p class="mb-1 text-xs font-bold uppercase tracking-[0.2em] text-orange-600 dark:text-orange-400">Raffle administration</p>
            <flux:heading size="xl">Prize Management</flux:heading>
            <flux:text class="mt-2">{{ $event->name }}</flux:text>
        </div>
        <flux:badge color="blue" size="lg">
            {{ $sequences->sum(fn ($sequence) => $sequence->prizes->count()) }} Prize Slots
        </flux:badge>
    </div>

    {{-- Overall statistics: computed from the supplied sequences, no additional PHP properties required --}}
    @php
        $allPrizes = $sequences->flatMap(fn ($sequence) => $sequence->prizes);
        $totalPrizes = $allPrizes->count();
        $pendingPrizes = $allPrizes->where('status', 'pending')->count();
        $completedPrizes = $allPrizes->where('status', 'completed')->count();
    @endphp
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Prize Slots</span>
                <flux:icon.gift class="size-5 text-orange-500" />
            </div>
            <div class="mt-3 text-3xl font-black text-zinc-900 dark:text-white">{{ number_format($totalPrizes) }}</div>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Across {{ $sequences->count() }} sequences</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Pending Prizes</span>
                <flux:icon.clock class="size-5 text-amber-500" />
            </div>
            <div class="mt-3 text-3xl font-black text-zinc-900 dark:text-white">{{ number_format($pendingPrizes) }}</div>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Awaiting draw</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Completed Prizes</span>
                <flux:icon.check-circle class="size-5 text-emerald-500" />
            </div>
            <div class="mt-3 text-3xl font-black text-zinc-900 dark:text-white">{{ number_format($completedPrizes) }}</div>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Draws finished</p>
        </div>
    </div>

    {{-- Existing floating success notification --}}
    <div x-data="{
        show: false,
        message: '',
        timeout: null,
        notify(text) {
            this.message = text;
            this.show = true;
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => { this.show = false; }, 4000);
        }
    }" x-on:prize-saved.window="notify($event.detail.message)">
        <template x-teleport="body">
            <div x-show="show" x-transition.opacity x-cloak
                class="fixed left-1/2 top-5 z-[9999] w-[calc(100%-2rem)] max-w-md -translate-x-1/2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-xl dark:border-green-800 dark:bg-green-900 dark:text-green-300">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <flux:icon.check-circle class="size-5 shrink-0" />
                        <span x-text="message"></span>
                    </div>
                    <button type="button" x-on:click="show = false" class="text-lg leading-none text-green-700 hover:text-green-900 dark:text-green-300" aria-label="Dismiss notification">&times;</button>
                </div>
            </div>
        </template>
    </div>

    {{-- Sequence navigation --}}
    <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="lg">Raffle Sequences</flux:heading>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Select a sequence to manage its prize items.</p>
            </div>
            <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-bold text-orange-700 dark:bg-orange-500/10 dark:text-orange-400">{{ $sequences->count() }} Sequences</span>
        </div>
        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-9">
            @foreach ($sequences as $sequence)
                @php
                    $sequenceCompleted = $sequence->prizes->isNotEmpty() && $sequence->prizes->every(fn ($prize) => $prize->status === 'completed');
                @endphp
                <button type="button" wire:key="prize-tab-{{ $sequence->id }}"
                    x-on:click="selectSequence({{ (int) $sequence->sequence_number }})"
                    x-bind:class="selectedSequence === {{ (int) $sequence->sequence_number }}
                        ? 'border-orange-500 bg-orange-500 text-white shadow-md shadow-orange-500/15'
                        : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-orange-300 hover:bg-orange-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:border-orange-500/50'"
                    class="min-h-20 rounded-xl border px-2 py-3 text-center transition">
                    <span class="block text-sm font-bold">Seq {{ $sequence->sequence_number }}</span>
                    <span class="mt-1 block text-[11px]" x-bind:class="selectedSequence === {{ (int) $sequence->sequence_number }} ? 'text-orange-100' : 'text-zinc-500 dark:text-zinc-400'">
                        {{ $sequenceCompleted ? 'Completed' : $sequence->prizes->count() . ' Items' }}
                    </span>
                </button>
            @endforeach
        </div>
    </section>

    {{-- Only the selected sequence is visible; all existing Livewire bindings remain mounted --}}
    @forelse ($sequences as $sequence)
        <section wire:key="prize-sequence-{{ $sequence->id }}"
            x-show="selectedSequence === {{ (int) $sequence->sequence_number }}" x-cloak
            class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-5 dark:border-zinc-700 dark:bg-zinc-800/70">
                <div>
                    <p class="mb-1 text-xs font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400">Selected Sequence</p>
                    <flux:heading size="lg">Sequence {{ $sequence->sequence_number }}</flux:heading>
                    <flux:text class="mt-1 text-sm">{{ $sequence->prizes->count() }} Prize Items</flux:text>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:badge>{{ ucfirst($sequence->status) }}</flux:badge>
                    @if (in_array($event->status, ['draft', 'registration'], true) && $sequence->status === 'pending')
                        <flux:button size="sm" variant="primary" icon="plus"
                            wire:click="addPrize({{ $sequence->id }})"
                            wire:loading.attr="disabled" wire:target="addPrize({{ $sequence->id }})">
                            Add Prize Item
                        </flux:button>
                    @endif
                </div>
            </div>

            <div class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-3 sm:p-5">
                @foreach ($sequence->prizes as $prize)
                    <div wire:key="manage-prize-{{ $prize->id }}"
                        class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm transition dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Prize Item {{ $prize->item_number }}</span>
                                <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">{{ $prize->name ?: 'Unnamed Prize' }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if ($prize->status === 'pending')
                                    <flux:badge color="amber">Pending</flux:badge>
                                @elseif ($prize->status === 'drawing')
                                    <flux:badge color="blue">Drawing</flux:badge>
                                @else
                                    <flux:badge color="green">Completed</flux:badge>
                                @endif
                                @if (in_array($event->status, ['draft', 'registration'], true) && $prize->status === 'pending')
                                    <flux:button size="xs" variant="danger" icon="trash"
                                        wire:click="deletePrize({{ $prize->id }})"
                                        wire:confirm="Are you sure you want to delete Item {{ $prize->item_number }}? This action cannot be undone."
                                        wire:loading.attr="disabled" wire:target="deletePrize({{ $prize->id }})" />
                                @endif
                            </div>
                        </div>

                        <flux:input label="Prize Name" placeholder="e.g. Rice Cooker"
                            wire:model="prizes.{{ $prize->id }}.name"
                            :disabled="$prize->status !== 'pending'" />
                        @error('prizes.' . $prize->id . '.name')
                            <p class="-mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <flux:input type="number" min="1" label="Target Winners"
                                    wire:model="prizes.{{ $prize->id }}.target_winners"
                                    :disabled="$prize->status !== 'pending' || ! in_array($event->status, ['draft', 'registration'], true)" />
                                @error('prizes.' . $prize->id . '.target_winners')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-800">
                                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Allocated</p>
                                <p class="mt-1 text-2xl font-black text-orange-600 dark:text-orange-400">{{ $prize->allocated_winners }}</p>
                            </div>
                        </div>

                        @error('prizes.' . $prize->id)
                            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div class="mt-auto flex justify-end border-t border-zinc-100 pt-3 dark:border-zinc-800">
                            <flux:button size="sm" variant="primary" icon="check"
                                wire:click="savePrize({{ $prize->id }})"
                                wire:loading.attr="disabled" wire:target="savePrize({{ $prize->id }})"
                                :disabled="$prize->status !== 'pending'">
                                Save Prize
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-xl border border-zinc-200 p-8 text-center dark:border-zinc-700">
            <flux:text>No raffle sequences found for this event.</flux:text>
        </div>
    @endforelse
</div>
