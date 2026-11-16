<div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

    {{-- Page Header --}}
<div class="flex flex-wrap items-start justify-between gap-4">

    <div class="space-y-2">
        <flux:heading size="xl">
            Raffle Results Summary
        </flux:heading>

        <flux:text>
            View raffle results, sequences, prizes, and winners.
        </flux:text>
    </div>

    
<div class="flex flex-wrap items-center gap-3">

    {{-- Export to Excel --}}
    <button
        type="button"
        wire:click="exportExcel"
        wire:loading.attr="disabled"
        wire:target="exportExcel"
        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-50"
    >
        <span wire:loading.remove wire:target="exportExcel">
            Export to Excel
        </span>

        <span wire:loading wire:target="exportExcel">
            Exporting...
        </span>
    </button>

    {{-- Back to Events --}}
    <a
        href="{{ route('raffle.events') }}"
        wire:navigate
        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        &larr; Back to Events
    </a>

</div>


</div>

    {{-- Event Information --}}
    <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">

        <flux:heading size="lg">
            {{ $event->name }}
        </flux:heading>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">

            <div>
                <flux:text>Total Attendees</flux:text>
                <div class="mt-1 text-2xl font-bold">
                    {{ number_format($event->total_attendees) }}
                </div>
            </div>

            <div>
                <flux:text>Total Winners Drawn</flux:text>
                <div class="mt-1 text-2xl font-bold">
                    {{ number_format($totalWinners) }}
                </div>
            </div>

            <div>
                <flux:text>Event Status</flux:text>
                <div class="mt-2">
                    <flux:badge>
                        {{ ucfirst($event->status) }}
                    </flux:badge>
                </div>
            </div>

        </div>
    </div>

    {{-- Sequences --}}
<div
    x-data="{ expandAll: false }"
    class="space-y-4"
>
    <div class="flex flex-wrap items-center justify-between gap-3">

      
        
<button
    type="button"
    x-on:click="expandAll = !expandAll"
    class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
>
    <span x-text="expandAll ? 'Collapse All' : 'Expand All'"></span>
</button>


    </div>

        <flux:heading size="lg">
            Sequence Results
        </flux:heading>

       @foreach ($sequences as $sequence)
    <div
        wire:key="sequence-{{ $sequence->id }}"
        x-data="{ expanded: false }"
x-effect="expanded = expandAll"
        class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700"
    >
                
<button
    type="button"
    @click="expanded = !expanded"
    :aria-expanded="expanded.toString()"
    class="flex w-full items-center justify-between gap-4 text-left"
>
    <div class="flex items-center gap-3">
        <flux:heading>
    Sequence {{ $sequence->sequence_number }}
</flux:heading>

<flux:badge>
    {{ ucfirst($sequence->status) }}
</flux:badge>

<span class="text-sm text-zinc-500 dark:text-zinc-400">
    {{ $sequence->prizes->sum(fn ($prize) => $winners->get($prize->id, collect())->count()) }}
    /
    {{ $sequence->allocated_winners }}
    Winners
</span>
    </div>

    <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5 shrink-0 text-zinc-500 transition-transform duration-200"
        :class="{ 'rotate-180': expanded }"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
    >
        <path stroke-linecap="round" stroke-linejoin="round"
            d="m6 9 6 6 6-6" />
    </svg>
</button>


               <div
    x-show="expanded"
    x-cloak
    class="mt-4 space-y-2"
>

                    @foreach ($sequence->prizes as $prize)
                        
<div
    wire:key="prize-{{ $prize->id }}"
    class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800"
>
    @php
        $prizeWinners = $winners->get($prize->id, collect());
    @endphp

    {{-- Prize Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">

        <div>
            <div class="font-semibold text-zinc-900 dark:text-white">
                {{ $prize->name }}
            </div>

            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                {{ $prizeWinners->count() }} /
                {{ $prize->allocated_winners }} Winners Drawn
            </div>
        </div>

        <flux:badge>
            {{ $prizeWinners->count() >= $prize->allocated_winners
                ? 'Completed'
                : 'Pending' }}
        </flux:badge>

    </div>

    {{-- Winning Raffle Numbers --}}
    @if ($prizeWinners->isNotEmpty())

        <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">

            <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                Winning Raffle Numbers
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($prizeWinners as $winner)
                    <span
                        wire:key="winner-{{ $winner->id }}"
                        class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300"
                    >
                        {{ $winner->attendee?->raffle_number ?? 'N/A' }}
                    </span>
                @endforeach
            </div>

        </div>

    @else

        <div class="mt-3 text-sm text-zinc-400">
            No winners drawn yet.
        </div>

    @endif
</div>

                    @endforeach

                </div>
            </div>
        @endforeach

    </div>

</div>
