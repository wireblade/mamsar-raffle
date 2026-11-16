<div
    x-data="{ drawAnimating: false }"
    x-on:raffle-animation-started.window="drawAnimating = true"
    x-on:raffle-animation-finished.window="drawAnimating = false"
    class="mx-auto w-full max-w-7xl space-y-5 p-4 sm:p-6 lg:p-8"
>
    {{-- Notifications --}}
    @if (session()->has('draw_success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4500)" x-show="show"
            class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
            {{ session('draw_success') }}
        </div>
    @endif
    @error('draw')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">{{ $message }}</div>
    @enderror

    <a href="{{ route('raffle.events') }}" wire:navigate
        class="inline-flex items-center gap-2 text-sm font-medium text-zinc-500 hover:text-orange-600 dark:text-zinc-400 dark:hover:text-orange-400">
        <flux:icon.arrow-left class="size-4" /> Back to Events
    </a>

    {{-- Header --}}
    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-widest text-orange-600 dark:text-orange-400">Live Event Operations</p>
                <flux:heading size="xl" class="mt-1">Raffle Control Panel</flux:heading>
                <p class="mt-1 break-words text-sm text-zinc-500 dark:text-zinc-400">{{ $event->name }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @php
                        $eventBadgeColor = match ($event->status) {
                            'registration' => 'blue',
                            'ready' => 'amber',
                            'ongoing' => 'purple',
                            'completed' => 'green',
                            default => 'zinc',
                        };
                    @endphp
                    <flux:badge :color="$eventBadgeColor">{{ ucfirst($event->status) }}</flux:badge>
                    <flux:badge>{{ number_format($event->total_attendees) }} Attendees</flux:badge>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button size="sm" variant="outline" icon="users" :href="route('raffle.registration', $event->id)" wire:navigate>Registration</flux:button>
                <flux:button size="sm" variant="primary" icon="computer-desktop" :href="route('raffle.audience', $event->id)" target="_blank">Audience Display</flux:button>
            </div>
        </div>
    </section>

    {{-- Overall progress, derived only from already-provided sequence/prize data --}}
    @php
        $allPrizes = $sequences->flatMap(fn ($sequence) => $sequence->prizes);
        $totalPrizes = $allPrizes->count();
        $completedPrizes = $allPrizes->where('status', 'completed')->count();
        $totalAllocated = $allPrizes->sum('allocated_winners');
        $completedAllocated = $allPrizes->where('status', 'completed')->sum('allocated_winners');
        $progressPercent = $totalPrizes > 0 ? round($completedPrizes / $totalPrizes * 100) : 0;
    @endphp
    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <flux:heading size="lg">Overall Raffle Progress</flux:heading>
            <span class="text-sm font-bold text-orange-600 dark:text-orange-400">{{ $progressPercent }}%</span>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/70">
                <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $completedPrizes }} <span class="text-sm font-medium text-zinc-400">/ {{ $totalPrizes }}</span></p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Prizes completed</p>
            </div>
            <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/70">
                <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ number_format($completedAllocated) }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Winners allocated to completed prizes</p>
            </div>
            <div class="col-span-2 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/70 sm:col-span-1">
                <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalAllocated) }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Total allocated winner slots</p>
            </div>
        </div>
        <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressPercent }}">
            <div class="h-full rounded-full bg-orange-500 transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
        </div>
    </section>

    {{-- Sequence selector --}}
    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <flux:heading size="lg">Raffle Sequences</flux:heading>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">Select a sequence to manage its prizes</span>
        </div>
        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-9">
            @foreach ($sequences as $sequence)
                @php
                    $sequenceCompleted = $sequence->status === 'completed';
                    $sequenceSelected = $selectedSequence === $sequence->sequence_number;
                @endphp
                <button type="button" wire:key="sequence-{{ $sequence->id }}"
                    wire:click="selectSequence({{ $sequence->sequence_number }})"
                    wire:loading.attr="disabled" wire:target="selectSequence,startDraw"
                    x-bind:disabled="drawAnimating"
                    @class([
                        'rounded-xl border px-2 py-3 text-center transition disabled:cursor-not-allowed disabled:opacity-60',
                        'border-orange-500 bg-orange-500 text-white shadow-sm' => $sequenceSelected,
                        'border-green-300 bg-green-50 text-green-800 hover:bg-green-100 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300' => !$sequenceSelected && $sequenceCompleted,
                        'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-orange-300 hover:bg-orange-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700' => !$sequenceSelected && !$sequenceCompleted,
                    ])>
                    <span class="block text-sm font-bold">Seq {{ $sequence->sequence_number }}</span>
                    <span class="mt-1 block text-[10px] font-medium uppercase tracking-wide opacity-80">{{ $sequenceCompleted ? 'Completed' : ucfirst($sequence->status) }}</span>
                </button>
            @endforeach
        </div>
    </section>

    {{-- Selected sequence --}}
    @if ($currentSequence)
        <section class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">Sequence {{ $currentSequence->sequence_number }}</flux:heading>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $currentSequence->prizes->count() }} Prize Items · {{ number_format($currentSequence->allocated_winners) }} Allocated Winners
                    </p>
                </div>
                <flux:badge>{{ ucfirst($currentSequence->status) }}</flux:badge>
            </div>

            <div class="grid items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($currentSequence->prizes as $prize)
                    <article wire:key="prize-{{ $prize->id }}"
                        x-data="{
                            prizeId: {{ $prize->id }},
                            animating: false,
                            expanded: false,
                            displayedWinners: @js($prize->status === 'completed' ? $prize->winners->pluck('attendee.raffle_number')->values()->all() : []),
                            sleep(ms) { return new Promise(resolve => setTimeout(resolve, ms)); },
                            async animateWinners(numbers) {
                                if (this.animating) return;
                                this.animating = true;
                                this.expanded = true;
                                window.dispatchEvent(new CustomEvent('raffle-animation-started'));
                                try {
                                    this.displayedWinners = [];
                                    for (const winner of numbers) {
                                        this.displayedWinners.push(winner);
                                        await this.$nextTick();
                                        await this.sleep(150);
                                    }
                                } finally {
                                    this.animating = false;
                                    window.dispatchEvent(new CustomEvent('raffle-animation-finished'));
                                }
                            }
                        }"
                        x-on:raffle-prize-drawn.window="if (Number($event.detail.prizeId) === prizeId) animateWinners($event.detail.numbers || [])"
                        class="min-w-0 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-orange-600 dark:text-orange-400">Prize Item {{ $prize->item_number }}</p>
                                <h3 class="mt-1 break-words text-lg font-bold text-zinc-900 dark:text-white">{{ $prize->name ?: 'Unnamed Prize' }}</h3>
                            </div>
                            @php
                                $prizeBadgeColor = $prize->status === 'completed' ? 'green' : ($prize->status === 'ongoing' ? 'purple' : 'zinc');
                            @endphp
                            <flux:badge :color="$prizeBadgeColor">{{ ucfirst($prize->status) }}</flux:badge>
                        </div>

                        <div class="mt-4 flex items-center justify-between rounded-xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800/70">
                            <div>
                                <p class="text-2xl font-black text-zinc-900 dark:text-white">{{ number_format($prize->allocated_winners) }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Allocated Winners</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-black text-orange-600 dark:text-orange-400" x-text="displayedWinners.length"></p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Displayed Winners</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <flux:button variant="primary" class="w-full"
                                wire:click="startDraw({{ $prize->id }})"
                                wire:confirm="Are you sure you want to draw winners for this prize? This action cannot be undone."
                                wire:loading.attr="disabled" wire:target="startDraw"
                                x-bind:disabled="drawAnimating || animating || @js($prize->status !== 'pending' || $event->status === 'completed')">
                                {{ $prize->status === 'completed' ? 'Completed' : 'Start Draw' }}
                            </flux:button>
                        </div>

                        {{-- Completed results are collapsed by default; newly drawn results expand automatically. --}}
                        <div x-show="displayedWinners.length > 0" x-cloak class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <button type="button" x-on:click="expanded = !expanded"
                                class="flex w-full items-center justify-between gap-2 text-left text-sm font-semibold text-zinc-800 hover:text-orange-600 dark:text-zinc-100 dark:hover:text-orange-400">
                                <span>Winning Raffle Numbers (<span x-text="displayedWinners.length"></span>)</span>
                                <span x-text="expanded ? 'Hide ▲' : 'Show ▼'" class="text-xs text-orange-600 dark:text-orange-400"></span>
                            </button>
                            <div x-show="expanded" class="mt-3">
                                <div x-ref="winnerGrid" class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                    <template x-for="(number, index) in displayedWinners" :key="index">
                                        <div x-text="number" class="raffle-winner-land break-all rounded-lg border border-orange-500/20 bg-orange-50 px-2 py-2 text-center text-sm font-bold text-orange-700 dark:bg-orange-500/10 dark:text-orange-300"></div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <style>
        [x-cloak] { display: none !important; }
        @keyframes raffleWinnerLand {
            0% { opacity: 0; transform: translateY(-25px) scale(1.12); }
            60% { opacity: 1; transform: translateY(5px) scale(.96); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        .raffle-winner-land { animation: raffleWinnerLand 250ms ease-out both; }
        @media (prefers-reduced-motion: reduce) {
            .raffle-winner-land { animation: none; }
        }
    </style>
</div>
