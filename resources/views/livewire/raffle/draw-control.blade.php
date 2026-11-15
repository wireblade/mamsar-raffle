
<div
    x-data="{ drawAnimating: false }"
    x-on:raffle-animation-started.window="drawAnimating = true"
    x-on:raffle-animation-finished.window="drawAnimating = false"
    class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8"
>
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

<a
    href="{{ route('raffle.events') }}"
    wire:navigate
    class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-zinc-500 transition hover:text-orange-600 dark:text-zinc-400 dark:hover:text-orange-400"
>
    <flux:icon.arrow-left class="size-4" />
    Back to Events
</a>

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

       <div class="flex flex-wrap items-center gap-3">

    {{-- Event Status --}}
    <flux:badge color="green">
        {{ ucfirst($event->status) }}
    </flux:badge>

    {{-- Total Attendees --}}
    <flux:badge>
        {{ number_format($event->total_attendees) }} Attendees
    </flux:badge>

    {{-- Registration --}}
    <flux:button
        size="sm"
        variant="outline"
        icon="users"
        :href="route('raffle.registration', $event->id)"
        wire:navigate
    >
        Registration
    </flux:button>

    {{-- Audience Display --}}
    <flux:button
        size="sm"
        variant="primary"
        icon="computer-desktop"
        :href="route('raffle.audience', $event->id)"
        target="_blank"
    >
        Audience Display
    </flux:button>

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
                        
{{ $currentSequence->prizes->count() }} Prize Items
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
       
        displayedWinners: @js($prize->status === 'completed' ? $prize->winners->pluck('attendee.raffle_number')->values()->all() : []),
        
      

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        
async animateWinners(numbers) {
    if (this.animating) return;

    this.animating = true;

    window.dispatchEvent(
        new CustomEvent('raffle-animation-started')
    );

    this.displayedWinners = [];

    for (const winner of numbers) {
        this.displayedWinners.push(winner);

        await this.$nextTick();
        await this.sleep(150);
    }

    this.animating = false;

    window.dispatchEvent(
        new CustomEvent('raffle-animation-finished')
    );
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
    x-bind:disabled="drawAnimating || @js($prize->status !== 'pending' || $event->status === 'completed')"
>
    {{ $prize->status === 'completed' ? 'Completed' : 'Start Draw' }}
</flux:button>

                        </div>



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
    .raffle-winner-land {
        animation: none;
    }
}
</style>

