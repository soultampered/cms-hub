<x-filament-panels::page>
    <div class="flex flex-wrap items-end gap-4">
        <div class="w-64">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Connection</label>
            <select wire:model.live="connectionId" class="fi-select-input mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                <option value="">Select a connection&hellip;</option>
                @foreach ($this->connections() as $connection)
                    <option value="{{ $connection->id }}">{{ $connection->name }} ({{ $connection->type->getLabel() }})</option>
                @endforeach
            </select>
        </div>

        @if ($this->connection())
            <div class="w-64">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Collection / table</label>
                <select wire:model.live="collection" class="fi-select-input mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                    @foreach ($this->collections() as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($this->collection)
            <div class="ms-auto">
                {{ $this->createRecordAction }}
            </div>
        @endif
    </div>

    @if ($this->collection)
        @php($result = $this->results())
        @php($schema = $this->schema())

        <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        @foreach ($schema as $field)
                            <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ $field['name'] }}</th>
                        @endforeach
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($result['records'] as $record)
                        <tr>
                            @foreach ($schema as $field)
                                <td class="px-4 py-2 align-middle text-gray-900 dark:text-gray-100">
                                    @php($value = $record[$field['name']] ?? null)
                                    {{ is_array($value) ? json_encode($value) : $value }}
                                </td>
                            @endforeach
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                {{ ($this->editRecordAction)(['id' => $record['id']]) }}
                                {{ ($this->deleteRecordAction)(['id' => $record['id']]) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($schema) + 1 }}" class="px-4 py-6 text-center text-gray-500">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <span>Page {{ $page }} &middot; {{ $result['total'] }} total</span>
            <div class="flex gap-2">
                <x-filament::button color="gray" size="sm" wire:click="previousPage" :disabled="$page <= 1">Prev</x-filament::button>
                <x-filament::button color="gray" size="sm" wire:click="nextPage" :disabled="($page * $pageSize) >= $result['total']">Next</x-filament::button>
            </div>
        </div>
    @else
        <div class="mt-6 text-sm text-gray-500 dark:text-gray-400">
            Add a database connection to browse its collections and tables here.
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
