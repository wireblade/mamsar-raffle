<div
    wire:poll.1s="checkForNewDraw"
    wire:ignore.self
    x-data="{
        currentPrizeId: @js($currentPrize?->id),
        lastAnimatedPrizeId: @js($currentPrize?->id),
        prizeName: @js($currentPrize?->name ?: 'Waiting for Draw'),
        sequenceNumber: @js($currentPrize?->sequence?->sequence_number),
        itemNumber: @js($currentPrize?->item_number),
        displayedWinners: @js($currentPrize?->winners->pluck('attendee.raffle_number')->values()->all() ?? []),
        spinningNumber: @js($currentPrize?->winners->last()?->attendee?->raffle_number ?? '--------'),
        animating: false,
        falling: false,
        fallingNumber: '',
        fallingStyle: {},
        pendingDraw: null,

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        receiveDraw(detail) {
            const prizeId = Number(detail.prizeId);

            console.log('DRAW CHECK:', {
    incomingPrizeId: prizeId,
    currentPrizeId: this.currentPrizeId,
    animating: this.animating
});

           if (!prizeId || prizeId === this.lastAnimatedPrizeId) {
    return;
}

            const draw = {
                prizeId: prizeId,
                numbers: detail.numbers || [],
                prizeName: detail.prizeName || 'Waiting for Draw',
                sequenceNumber: detail.sequenceNumber,
                itemNumber: detail.itemNumber
            };

            if (this.animating) {
                this.pendingDraw = draw;
                return;
            }

            this.startDrawAnimation(draw);
        },

        async startDrawAnimation(draw) {
            this.currentPrizeId = draw.prizeId;
            this.lastAnimatedPrizeId = draw.prizeId;
            this.prizeName = draw.prizeName;
            this.sequenceNumber = draw.sequenceNumber;
            this.itemNumber = draw.itemNumber;

            await this.animateWinners(draw.numbers);
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
                pointerEvents: 'none',
                opacity: 1
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

            // Clear all winners from the previous prize.
            this.displayedWinners = [];
            this.spinningNumber = '--------';
            this.falling = false;
            this.fallingNumber = '';

            await this.$nextTick();

            for (const winner of numbers) {
                // Spin through random raffle numbers.
                for (let i = 0; i < 5; i++) {
                    this.spinningNumber = numbers[
                        Math.floor(Math.random() * numbers.length)
                    ];

                    await this.sleep(30);
                }

                // Stop on the actual winning number.
                this.spinningNumber = winner;

                await this.sleep(50);

                // Drop the winning number into the grid.
                await this.dropWinner(winner);
            }

            this.spinningNumber = numbers.at(-1) ?? '--------';
            this.animating = false;

            if (this.pendingDraw) {
                const next = this.pendingDraw;
                this.pendingDraw = null;

                if (next.prizeId !== this.currentPrizeId) {
                    await this.startDrawAnimation(next);
                }
            }
        }
    }"
    x-on:audience-draw-detected.window="console.log('LIVEWIRE EVENT RECEIVED:', $event.detail); receiveDraw($event.detail)"
    class="min-h-screen bg-slate-950 px-4 py-8 text-white sm:px-8 lg:px-12"
>
    <div wire:ignore class="mx-auto max-w-7xl">

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

            <h2
                class="text-3xl font-extrabold sm:text-5xl"
                x-text="prizeName"
            ></h2>

            <p
                class="mt-4 text-lg text-slate-400"
                x-text="sequenceNumber
                    ? `Sequence ${sequenceNumber} · Prize Item ${itemNumber}`
                    : 'The raffle will begin shortly'"
            ></p>

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

        {{-- Floating Winning Number --}}
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
                x-ref="winnerGrid"
                class="grid min-h-40 grid-cols-2 content-start gap-3 sm:grid-cols-4 lg:grid-cols-6"
            >
                <template
                    x-for="(number, index) in displayedWinners"
                    :key="`${currentPrizeId}-${index}`"
                >
                    <div
                        x-text="number"
                        class="raffle-winner-land rounded-xl border border-orange-500/30 bg-slate-950 px-3 py-4 text-center text-lg font-bold text-orange-400"
                    ></div>
                </template>
            </div>

            <p
                x-show="displayedWinners.length === 0 && !animating"
                x-cloak
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

    [x-cloak] {
        display: none !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .raffle-floating-winner {
            transition: none;
        }

        .raffle-winner-land,
        .raffle-number-spinning {
            animation: none;
        }
    }
</style>
