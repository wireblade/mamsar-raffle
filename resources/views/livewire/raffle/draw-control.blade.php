
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
    @if (session()->has('draw_success'))
    <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">
        {{ session('draw_success') }}
    </div>
@endif

@error('draw')
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
        {{ $message }}
    </div>
@enderror

    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <flux:heading size="xl">
                Raffle Control Panel
            </flux:heading>

            <flux:text class="mt-1">
                {{ $event->name }}
            </flux:text>
        </div>

        <div class="flex items-center gap-3">
            <flux:badge color="green">
                {{ ucfirst($event->status) }}
            </flux:badge>

            <flux:badge>
                {{ number_format($event->total_attendees) }} Attendees
            </flux:badge>
        </div>
    </div>

    {{-- Sequence Selector --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5
                dark:border-zinc-700 dark:bg-zinc-900">

        <flux:heading size="lg" class="mb-4">
            Raffle Sequences
        </flux:heading>

        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-9">

            @foreach ($sequences as $sequence)
                <button
                    type="button"
                    wire:key="sequence-{{ $sequence->id }}"
                    wire:click="selectSequence({{ $sequence->sequence_number }})"
                    @class([
                        'rounded-lg border px-3 py-3 text-sm font-semibold transition',
                        'border-orange-500 bg-orange-500 text-white' =>
                            $selectedSequence === $sequence->sequence_number,
                        'border-zinc-200 bg-zinc-50 text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' =>
                            $selectedSequence !== $sequence->sequence_number,
                    ])
                >
                    Sequence {{ $sequence->sequence_number }}
                </button>
            @endforeach

        </div>
    </div>

    {{-- Current Sequence --}}
    @if ($currentSequence)
        <div class="space-y-4">

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">
                        Sequence {{ $currentSequence->sequence_number }}
                    </flux:heading>

                    <flux:text class="mt-1">
                        {{ $currentSequence->item_limit }} Prize Items
                        · {{ $currentSequence->allocated_winners }} Winners
                    </flux:text>
                </div>

                <flux:badge>
                    {{ ucfirst($currentSequence->status) }}
                </flux:badge>
            </div>

            {{-- Prize Cards --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                @foreach ($currentSequence->prizes as $prize)
                    
<div
    wire:key="prize-{{ $prize->id }}"
    x-data="{
        prizeId: {{ $prize->id }},
        animating: false,
        spinningNumber: '--------',
        displayedWinners: @js($prize->status === 'completed' ? $prize->winners->pluck('attendee.raffle_number')->values()->all() : []),
        fallingNumber: '',
        fallingStyle: {},
        falling: false,

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        async dropWinner(number) {
            const source = this.$refs.spinner;
            const target = this.$refs.winnerGrid;

            if (!source || !target) {
                this.displayedWinners.push(number);
                return;
            }

            const start = source.getBoundingClientRect();
            const end = target.getBoundingClientRect();

            this.fallingNumber = number;

            this.fallingStyle = {
                position: 'fixed',
                left: `${start.left}px`,
                top: `${start.top}px`,
                width: `${start.width}px`,
                zIndex: 9999,
                pointerEvents: 'none'
            };

            this.falling = true;

            await this.$nextTick();
            await this.sleep(30);

            const destinationX = end.left + (end.width - start.width) / 2;
            const destinationY = end.top;

            this.fallingStyle = {
                ...this.fallingStyle,
                left: `${destinationX}px`,
                top: `${destinationY}px`,
                transform: 'scale(0.8)',
                opacity: 0.5
            };

           await this.sleep(300);

            this.displayedWinners.push(number);

            await this.$nextTick();

            this.falling = false;
            this.fallingNumber = '';
        },

        
async animateWinners(numbers) {
    if (this.animating) return;

    this.animating = true;
    this.displayedWinners = [];

    for (const winner of numbers) {
        // Fast random number spinning (~150ms)
        for (let i = 0; i < 5; i++) {
            this.spinningNumber = numbers[Math.floor(Math.random() * numbers.length)];
            await this.sleep(30);
        }

        // Stop on the actual winner
        this.spinningNumber = winner;

        await this.sleep(50);

        // Falling animation
        await this.dropWinner(winner);
    }

    this.animating = false;
    this.spinningNumber = '--------';
}

    }"
    x-on:raffle-prize-drawn.window="
        if (Number($event.detail.prizeId) === prizeId) {
            animateWinners($event.detail.numbers);
        }
    "
    class="rounded-xl border border-zinc-200 bg-white p-5
           shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
>

                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <flux:text class="text-xs">
                                    Prize Item {{ $prize->item_number }}
                                </flux:text>

                                <flux:heading size="lg" class="mt-1">
                                    {{ $prize->name ?: 'Unnamed Prize' }}
                                </flux:heading>
                            </div>

                            <flux:badge>
                                {{ ucfirst($prize->status) }}
                            </flux:badge>
                        </div>

                        <div class="mt-5 rounded-lg bg-zinc-50 p-4
                                    dark:bg-zinc-800">
                            <div class="text-2xl font-bold text-zinc-900
                                        dark:text-white">
                                {{ $prize->allocated_winners }}
                            </div>

                            <flux:text class="text-sm">
                                Allocated Winners
                            </flux:text>
                        </div>

                        <div class="mt-4">
                            <flux:button
    variant="primary"
    wire:click="startDraw({{ $prize->id }})"
    wire:confirm="Are you sure you want to draw winners for this prize? This action cannot be undone."
    wire:loading.attr="disabled"
    wire:target="startDraw"
    :disabled="$prize->status !== 'pending'"
>
    {{ $prize->status === 'completed' ? 'Completed' : 'Start Draw' }}
</flux:button>

{{-- Animated Number Display --}}
<div
    x-show="animating"
    x-cloak
    class="mt-4 rounded-xl border border-orange-300 bg-orange-50 p-5 text-center
           dark:border-orange-700 dark:bg-orange-950/30"
>
    <div class="mb-3 text-xs font-semibold uppercase tracking-widest text-orange-600 dark:text-orange-400">
        Drawing Winning Numbers
    </div>

    <div
        x-ref="spinner"
        x-text="spinningNumber"
        class="rounded-lg bg-white px-4 py-4 text-3xl font-black tracking-wider text-orange-600
               shadow-sm dark:bg-zinc-900 dark:text-orange-400"
    ></div>

    <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
        <span x-text="displayedWinners.length"></span>
        / {{ $prize->allocated_winners }} Winners
    </div>
</div>

{{-- Floating Winning Number --}}
<template x-teleport="body">
    <div
        x-show="falling"
        x-cloak
        :style="fallingStyle"
        x-text="fallingNumber"
        class="raffle-floating-winner rounded-lg border border-green-400 bg-green-100
               px-3 py-3 text-center text-2xl font-black text-green-800 shadow-xl"
    ></div>
</template>

{{-- Winning Numbers --}}
<div
    x-show="displayedWinners.length > 0"
    x-cloak
    class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700"
>
    <div class="mb-3 flex items-center justify-between">
        <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">
            Winning Raffle Numbers
        </h4>

        <span class="text-xs text-zinc-500 dark:text-zinc-400">
            <span x-text="displayedWinners.length"></span>
            Winners
        </span>
    </div>

    <div
        x-ref="winnerGrid"
        class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4"
    >
        <template x-for="(number, index) in displayedWinners" :key="index">
            <div
                class="raffle-winner-land rounded-lg border border-green-200 bg-green-50
                       px-3 py-2 text-center text-sm font-semibold text-green-800
                       dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
                x-text="number"
            ></div>
        </template>
    </div>
</div>

                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    @endif

</div>


<style>
    /* Winning number travels downward from the spinner */
    .raffle-floating-winner {
    transition:
        left 300ms cubic-bezier(0.35, 0, 0.75, 0.75),
        top 300ms cubic-bezier(0.35, 0, 0.75, 0.75),
        transform 300ms ease-in,
        opacity 300ms ease-in;
}

    /* Winner bounces when landing in the grid */
    @keyframes raffleWinnerLand {
        0% {
            opacity: 0;
            transform: translateY(-35px) scale(1.2);
        }

        55% {
            opacity: 1;
            transform: translateY(8px) scale(0.92);
        }

        75% {
            transform: translateY(-10px) scale(1.06);
        }

        90% {
            transform: translateY(3px) scale(0.98);
        }

        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .raffle-winner-land {
    animation: raffleWinnerLand 250ms ease-out both;
}

    /* Accessibility */
    @media (prefers-reduced-motion: reduce) {
        .raffle-floating-winner {
            transition: none;
        }

        .raffle-winner-land {
            animation: none;
        }
    }
</style>

