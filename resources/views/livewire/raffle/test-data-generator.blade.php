
<div class="mx-auto w-full max-w-4xl space-y-6 p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div>
        <flux:heading size="xl">
            Raffle Test Data Generator
        </flux:heading>

        <flux:text class="mt-2">
            Generate a test raffle event with configurable attendees,
            9 sequences, and 55 prize items.
        </flux:text>
    </div>

    {{-- Generator Card --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">

        <flux:heading size="lg">
            Generate Test Event
        </flux:heading>

        <flux:text class="mt-2">
            Enter the number of attendees to generate.
            Raffle numbers will start at 1 and end at your selected number.
        </flux:text>

        <form wire:submit="generateTestData" class="mt-6 space-y-5">

            <flux:input
                wire:model="attendeeCount"
                type="number"
                min="474"
                max="10000"
                step="1"
                label="Number of Attendees"
                description="Default: 1,130 attendees. Minimum: 474."
                placeholder="Enter attendee count"
                required
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Sequences
                    </p>
                    <p class="mt-1 text-2xl font-semibold dark:text-white">
                        9
                    </p>
                </div>

                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Prize Items
                    </p>
                    <p class="mt-1 text-2xl font-semibold dark:text-white">
                        55
                    </p>
                </div>

                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Fixed Winner Slots
                    </p>
                    <p class="mt-1 text-2xl font-semibold dark:text-white">
                        474
                    </p>
                </div>

            </div>

            <div class="flex justify-end">
                <flux:button
                    type="submit"
                    variant="primary"
                    icon="plus"
                    wire:loading.attr="disabled"
                    wire:target="generateTestData"
                >
                    Generate Test Data
                </flux:button>
            </div>

        </form>

    </div>

    {{-- Reset Card --}}
    <div class="rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-900 dark:bg-red-950/20">

        <flux:heading size="lg">
            Reset Test Data
        </flux:heading>

        <flux:text class="mt-2">
            Delete generated test events and their associated
            attendees, prizes, sequences, and winners.
            Real raffle events must remain untouched.
        </flux:text>

        <div class="mt-5 flex justify-end">
            <flux:button
    type="button"
    variant="danger"
    icon="trash"
    wire:click="resetTestData"
    wire:confirm="WARNING: This will permanently delete ALL raffle events, attendees, winners, prizes, and sequences. This action cannot be undone. Are you sure you want to continue?"
    wire:loading.attr="disabled"
    wire:target="resetTestData"
>
    Reset Test Data
</flux:button>
        </div>

        <p class="mt-3 text-xs text-red-600 dark:text-red-400">
            Reset is disabled until the safe deletion logic is implemented.
        </p>

    </div>

</div>
