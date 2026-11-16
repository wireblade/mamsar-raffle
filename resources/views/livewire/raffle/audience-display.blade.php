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
        pendingDraws: [],
        expectedWinners: @js($currentPrize?->winners->count() ?? 0),
        screenWidth: window.innerWidth,
        screenHeight: window.innerHeight,

        init() {
            const updateSize = () => {
                this.screenWidth = window.innerWidth;
                this.screenHeight = window.innerHeight;
            };
            window.addEventListener('resize', updateSize);
            this.resizeHandler = updateSize;
        },

        destroy() {
            window.removeEventListener('resize', this.resizeHandler);
        },

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        get columns() {
            const count = Math.max(this.expectedWinners, this.displayedWinners.length);
            const narrow = this.screenWidth < 750;
            if (count > 36) return narrow ? 5 : 10;
            if (count > 20) return narrow ? 4 : 8;
            if (count > 10) return narrow ? 3 : 6;
            if (count > 4) return narrow ? 2 : 5;
            return narrow ? 2 : 4;
        },

        get rows() {
            return Math.max(1, Math.ceil(Math.max(this.expectedWinners, this.displayedWinners.length, 1) / this.columns));
        },

        receiveDraw(detail) {
            const prizeId = Number(detail.prizeId);
            if (!prizeId || prizeId === this.lastAnimatedPrizeId || this.pendingDraws.some(d => d.prizeId === prizeId)) return;

            const draw = {
                prizeId,
                numbers: Array.isArray(detail.numbers) ? detail.numbers.map(String) : [],
                prizeName: detail.prizeName || 'Waiting for Draw',
                sequenceNumber: detail.sequenceNumber,
                itemNumber: detail.itemNumber
            };

            if (this.animating) {
                this.pendingDraws.push(draw);
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
            this.expectedWinners = draw.numbers.length;
            await this.animateWinners(draw.numbers);
        },

        async dropWinner(number) {
            const source = this.$refs.spinner;
            const target = this.$refs.winnerGrid;
            if (!source || !target || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
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
                top: `${end.top + Math.min(end.height / 3, 60)}px`,
                transform: 'scale(0.65)',
                opacity: 0.45
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
            this.spinningNumber = '--------';
            this.falling = false;
            this.fallingNumber = '';
            await this.$nextTick();

            for (const winner of numbers) {
                for (let i = 0; i < 5; i++) {
                    this.spinningNumber = numbers[Math.floor(Math.random() * numbers.length)] ?? '--------';
                    await this.sleep(30);
                }
                this.spinningNumber = winner;
                await this.sleep(50);
                await this.dropWinner(winner);
            }

            this.spinningNumber = numbers.at(-1) ?? '--------';
            this.animating = false;
            const next = this.pendingDraws.shift();
            if (next && next.prizeId !== this.currentPrizeId) await this.startDrawAnimation(next);
        }
    }"
    x-on:audience-draw-detected.window="receiveDraw($event.detail)"
    class="raffle-audience bg-slate-950 text-white"
>
    <div wire:ignore class="raffle-stage mx-auto w-full max-w-[1920px]">
        {{-- Compact event header --}}
        <header class="raffle-header flex min-w-0 items-center justify-between gap-4 border-b border-slate-700/60">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-400 sm:text-xs">MAMSAR RAFFLE EVENT</p>
                <h1 class="truncate text-lg font-black tracking-tight sm:text-2xl xl:text-3xl">{{ $event->name }}</h1>
            </div>
            <div class="flex shrink-0 items-center gap-2 rounded-full border border-orange-500/25 bg-orange-500/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-orange-300 sm:text-xs">
                <span class="h-2 w-2 rounded-full bg-orange-400"></span>
                Live Draw
            </div>
        </header>

        {{-- Prize and number: intentionally compact to leave room for winners --}}
        <section class="raffle-prize relative min-h-0 overflow-hidden rounded-2xl border border-orange-500/25 bg-slate-900/95">
            <div class="pointer-events-none absolute -right-20 -top-24 h-48 w-48 rounded-full bg-orange-500/10 blur-3xl"></div>
            <div class="relative flex h-full min-h-0 flex-col items-center justify-center gap-2 px-3 py-2 text-center sm:px-6">
                <div class="flex flex-wrap items-center justify-center gap-2 text-[10px] font-bold uppercase tracking-wider sm:text-xs">
                    <span class="rounded-full border border-orange-500/30 bg-orange-500/10 px-3 py-1 text-orange-300"
                          x-text="sequenceNumber ? `Sequence ${sequenceNumber}` : 'Awaiting Draw'"></span>
                    <span x-show="itemNumber" x-cloak
                          class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1 text-slate-300"
                          x-text="`Prize Item ${itemNumber}`"></span>
                </div>

                <h2 class="raffle-prize-name max-w-full font-black leading-tight text-white" x-text="prizeName"></h2>

                <div class="raffle-number-panel flex w-full max-w-4xl min-h-0 flex-col items-center justify-center rounded-xl border border-orange-500/30 bg-slate-950/90 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 sm:text-xs">Winning Raffle Number</p>
                    <div x-ref="spinner" x-text="spinningNumber"
                         :class="animating && !falling ? 'raffle-number-spinning' : ''"
                         class="raffle-main-number max-w-full font-black leading-none tracking-wider text-orange-400"></div>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 sm:text-xs"
                       x-text="animating ? 'Drawing Winners...' : 'Ready for Next Draw'"></p>
                </div>
            </div>
        </section>

        {{-- Floating winning number: stays outside clipping containers --}}
        <template x-teleport="body">
            <div x-show="falling" x-cloak :style="fallingStyle" x-text="fallingNumber"
                 class="raffle-floating-winner rounded-xl border border-orange-400 bg-orange-100 px-4 py-3 text-center text-2xl font-black text-orange-900 shadow-2xl"></div>
        </template>

        {{-- Winner board: takes remaining screen height --}}
        <section class="raffle-results flex min-h-0 flex-col overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-900/95">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-700/60 px-3 py-2 sm:px-5">
                <div class="min-w-0">
                    <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-orange-400 sm:text-[10px]">Official Raffle Results</p>
                    <h3 class="truncate text-base font-black sm:text-xl">Winning Numbers</h3>
                </div>
                <div class="shrink-0 rounded-xl border border-orange-500/20 bg-orange-500/10 px-3 py-1.5 text-center">
                    <p class="text-[9px] uppercase tracking-wider text-slate-400">Winners</p>
                    <p class="text-lg font-black leading-none text-orange-400 sm:text-xl" x-text="displayedWinners.length"></p>
                </div>
            </div>

            <div class="relative min-h-0 flex-1 p-2 sm:p-3">
                <div x-ref="winnerGrid"
                     :style="`grid-template-columns: repeat(${columns}, minmax(0, 1fr)); grid-template-rows: repeat(${rows}, minmax(0, 1fr));`"
                     class="raffle-winner-grid grid h-full min-h-0 w-full gap-1.5 sm:gap-2">
                    <template x-for="(number, index) in displayedWinners" :key="`${currentPrizeId}-${index}`">
                        <div class="raffle-winner-land flex min-h-0 min-w-0 items-center justify-center overflow-hidden rounded-lg border border-orange-500/25 bg-slate-950 px-1 text-center shadow-md">
                            <span x-text="number" class="raffle-grid-number block max-w-full truncate font-black leading-none tracking-wide text-orange-300"></span>
                        </div>
                    </template>
                </div>

                <div x-show="displayedWinners.length === 0 && !animating" x-cloak
                     class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-2 text-center">
                    <p class="text-lg font-bold text-slate-300">Waiting for Winning Numbers</p>
                    <p class="text-xs text-slate-500">Winners will appear automatically</p>
                </div>
            </div>
        </section>

        <footer class="raffle-footer flex items-center justify-center text-[10px] text-slate-500 sm:text-xs">
            MAMSAR Raffle Management System
        </footer>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }

    /* The stage fits the browser viewport, not a presumed LED pixel resolution. */
    .raffle-audience {
        height: 100vh;
        height: 100dvh;
        overflow: hidden;
        padding: clamp(6px, 1.1vh, 14px) clamp(8px, 1.3vw, 24px);
    }
    .raffle-stage {
        display: grid;
        grid-template-rows: auto minmax(0, 32fr) minmax(0, 68fr) auto;
        gap: clamp(5px, 0.9vh, 12px);
        height: 100%;
        min-height: 0;
    }
    .raffle-header { padding: 0 2px clamp(5px, 0.7vh, 10px); }
    .raffle-footer { min-height: 12px; }
    .raffle-prize-name { font-size: clamp(17px, min(2.8vw, 3.7vh), 42px); }
    .raffle-number-panel { flex: 1; max-height: 65%; }
    .raffle-main-number { font-size: clamp(28px, min(5.5vw, 8vh), 90px); }
    .raffle-grid-number { font-size: clamp(10px, min(1.65vw, 2.7vh), 27px); }

    .raffle-floating-winner {
        transition: left 300ms cubic-bezier(0.35, 0, 0.75, 0.75),
                    top 300ms cubic-bezier(0.35, 0, 0.75, 0.75),
                    transform 300ms ease-in, opacity 300ms ease-in;
    }
    @keyframes raffleWinnerLand {
        0% { opacity: 0; transform: translateY(-18px) scale(1.1); }
        60% { opacity: 1; transform: translateY(3px) scale(0.96); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    .raffle-winner-land { animation: raffleWinnerLand 250ms ease-out both; }
    @keyframes raffleNumberSpin {
        0%, 100% { transform: translateY(-3px) scale(0.98); opacity: 0.7; }
        50% { transform: translateY(3px) scale(1.03); opacity: 1; }
    }
    .raffle-number-spinning { animation: raffleNumberSpin 150ms ease-in-out infinite; }

    /* Very short browser windows: give the grid even more space. */
    @media (max-height: 650px) {
        .raffle-stage { grid-template-rows: auto minmax(0, 27fr) minmax(0, 73fr) auto; }
        .raffle-prize-name { font-size: clamp(14px, 3vh, 26px); }
        .raffle-main-number { font-size: clamp(24px, 6vh, 55px); }
    }
    @media (prefers-reduced-motion: reduce) {
        .raffle-floating-winner { transition: none; }
        .raffle-winner-land, .raffle-number-spinning { animation: none; }
    }
</style>
