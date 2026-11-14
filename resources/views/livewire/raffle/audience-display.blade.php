
<div
    wire:poll.1s
    x-data="{
        currentPrizeId: @js($currentPrize?->id),
        lastAnimatedPrizeId: @js($currentPrize?->id),
        animating: false,
        spinningNumber: '--------',
        displayedWinners: @js($currentPrize?->winners->pluck('attendee.raffle_number')->values()->all() ?? []),
        fallingNumber: '',
        fallingStyle: {},
        falling: false,
        pendingDraw: null,

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        init() {
            this.$nextTick(() => {
                const observer = new MutationObserver(() => {
                    this.checkDrawData();
                });

                observer.observe(this.$refs.drawData, {
                    attributes: true,
                    attributeFilter: ['data-prize-id', 'data-winners']
                });

                this.checkDrawData();
            });
        },

        checkDrawData() {
            const element = this.$refs.drawData;

            if (!element) return;

            const prizeId = Number(element.dataset.prizeId);

            if (!prizeId || prizeId === this.lastAnimatedPrizeId) {
                return;
            }

            let numbers = [];

            try {
                numbers = JSON.parse(element.dataset.winners || '[]');
            } catch (error) {
                console.error('Invalid raffle winners data:', error);
                return;
            }

            if (this.animating) {
                this.pendingDraw = { prizeId, numbers };
                return;
            }

            this.lastAnimatedPrizeId = prizeId;
            this.currentPrizeId = prizeId;

            this.animateWinners(numbers);
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

            this.fallingStyle = {
                ...this.fallingStyle,
                left: `${end.left + (end.width - start.width) / 2}px`,
                top: `${end.top}px`,
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

    // Clear winners from the previous prize
    this.displayedWinners = [];
    this.falling = false;
    this.fallingNumber = '';
    this.spinningNumber = '--------';

    // Wait for Alpine to update the winners grid
    await this.$nextTick();

            for (const winner of numbers) {
                for (let i = 0; i < 5; i++) {
                    this.spinningNumber = numbers[
                        Math.floor(Math.random() * numbers.length)
                    ];

                    await this.sleep(30);
                }

                this.spinningNumber = winner;

                await this.sleep(50);
                await this.dropWinner(winner);
            }

            this.spinningNumber = '--------';
            this.animating = false;

            if (this.pendingDraw) {
                const next = this.pendingDraw;
                this.pendingDraw = null;

                if (next.prizeId !== this.lastAnimatedPrizeId) {
                    this.lastAnimatedPrizeId = next.prizeId;
                    this.currentPrizeId = next.prizeId;
                    this.animateWinners(next.numbers);
                }
            }
        }
    }"
    class="min-h-screen bg-slate-950 px-4 py-8 text-white sm:px-8 lg:px-12"
>
    {{-- Data updated by Livewire polling --}}
    <div
        x-ref="drawData"
        class="hidden"
        data-prize-id="{{ $currentPrize?->id }}"
        data-winners="{{ json_encode($currentPrize?->winners->pluck('attendee.raffle_number')->values()->all() ?? []) }}"
    ></div>

    <div class="mx-auto max-w-7xl">

        {{-- Event Header --}}
        <div class="mb-10 text-center">
            <div class="mb-3 text-sm font-semibold uppercase tracking-[0.3em] text-orange-400">
                MAMSAR RAFFLE EVENT
            </div>

            <h1 class="text-3xl font-black tracking-tight sm:text-5xl lg:text-6xl">
                {{ $event->name }}
            </h1>

            <p class="mt-4 text-lg text-slate-400">
                Live Raffle Draw
            </p>
        </div>

        {{-- Current Prize --}}
        <div class="rounded-3xl border border-slate-700 bg-slate-900 p-6 text-center shadow-2xl sm:p-10">

            <div class="mb-4 text-sm font-semibold uppercase tracking-widest text-orange-400">
                CURRENT PRIZE
            </div>

            <h2 class="text-3xl font-extrabold sm:text-5xl">
                {{ $currentPrize?->name ?: 'Waiting for Draw' }}
            </h2>

            <p class="mt-4 text-lg text-slate-400">
                @if ($currentPrize)
                    Sequence {{ $currentPrize->sequence->sequence_number }}
                    · Prize Item {{ $currentPrize->item_number }}
                @else
                    The raffle will begin shortly
                @endif
            </p>

            {{-- Main Raffle Number --}}
            <div class="mx-auto mt-10 max-w-3xl rounded-2xl border border-orange-500/30 bg-slate-950 px-4 py-12">

                <div class="mb-4 text-sm font-semibold uppercase tracking-widest text-slate-400">
                    RAFFLE NUMBER
                </div>

               <div
    x-ref="spinner"
    x-text="spinningNumber"
    :class="animating && !falling ? 'raffle-number-spinning' : ''"
    class="text-5xl font-black tracking-wider text-orange-400 sm:text-7xl lg:text-8xl"
></div>

            </div>
        </div>

        {{-- Floating Winner --}}
        <template x-teleport="body">
            <div
                x-show="falling"
                x-cloak
                :style="fallingStyle"
                x-text="fallingNumber"
                class="raffle-floating-winner rounded-xl border border-green-400 bg-green-100 px-5 py-4 text-center text-3xl font-black text-green-800 shadow-2xl"
            ></div>
        </template>

        {{-- Winners Area --}}
        <div class="mt-8 rounded-3xl border border-slate-700 bg-slate-900 p-6 sm:p-8">

            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">

                <h3 class="text-xl font-bold sm:text-2xl">
                    Winning Raffle Numbers
                </h3>

                <span class="rounded-full bg-orange-500/10 px-4 py-2 text-sm font-semibold text-orange-400">
                    <span x-text="displayedWinners.length"></span>
                    Winners
                </span>

            </div>

            <div
    wire:ignore
    x-ref="winnerGrid"
    class="grid min-h-40 grid-cols-2 content-start gap-3 sm:grid-cols-4 lg:grid-cols-6"
>
                <template x-for="(number, index) in displayedWinners" :key="index">
                    <div
                        x-text="number"
                        class="raffle-winner-land rounded-xl border border-orange-500/30 bg-slate-950 px-3 py-4 text-center text-lg font-bold text-orange-400"
                    ></div>
                </template>
            </div>

            <p
                x-show="displayedWinners.length === 0 && !animating"
                class="mt-4 text-center text-lg text-slate-500"
            >
                Winners will appear here automatically
            </p>

        </div>

        {{-- Footer --}}
        <div class="mt-8 text-center text-sm text-slate-500">
            MAMSAR Raffle Management System
        </div>

    </div>
</div>

<style>
    .raffle-floating-winner {
        transition:
            left 300ms cubic-bezier(0.35, 0, 0.75, 0.75),
            top 300ms cubic-bezier(0.35, 0, 0.75, 0.75),
            transform 300ms ease-in,
            opacity 300ms ease-in;
    }

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

    [x-cloak] {
        display: none !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .raffle-floating-winner {
            transition: none;
        }

        .raffle-winner-land {
            animation: none;
        }
    }
</style>


@keyframes raffleNumberSpin {
    0% {
        transform: translateY(-8px) scale(0.96);
        opacity: 0.5;
        filter: blur(2px);
    }

    50% {
        transform: translateY(8px) scale(1.05);
        opacity: 1;
        filter: blur(0);
    }

    100% {
        transform: translateY(-8px) scale(0.96);
        opacity: 0.5;
        filter: blur(2px);
    }
}

.raffle-number-spinning {
    animation: raffleNumberSpin 150ms ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
    .raffle-number-spinning {
        animation: none;
    }
}
