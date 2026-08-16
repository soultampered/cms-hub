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

        <div class="w-64">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Path prefix</label>
            <input type="text" wire:model.live.debounce.400ms="prefix" placeholder="images/" class="fi-input mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
        </div>

        @if ($this->connection())
            <div class="ms-auto">
                {{ $this->uploadAction }}
            </div>
        @endif
    </div>

    @if ($this->connection())
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @forelse ($this->assets() as $asset)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="aspect-[4/3] flex items-center justify-center bg-gray-100 dark:bg-gray-800 text-xs font-semibold uppercase text-gray-400">
                        {{ pathinfo($asset['path'], PATHINFO_EXTENSION) ?: 'file' }}
                    </div>
                    <div class="p-2">
                        <div class="truncate text-xs font-mono text-gray-700 dark:text-gray-300" title="{{ $asset['path'] }}">{{ $asset['path'] }}</div>
                        <div class="mt-1 flex items-center justify-between text-xs text-gray-400">
                            <span>{{ number_format($asset['size'] / 1024, 1) }} KB</span>
                            <div class="flex gap-1">
                                {{ ($this->purgeAssetAction)(['path' => $asset['path']]) }}
                                {{ ($this->deleteAssetAction)(['path' => $asset['path']]) }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-sm text-gray-500 dark:text-gray-400">No assets found.</div>
            @endforelse
        </div>
    @else
        <div class="mt-6 text-sm text-gray-500 dark:text-gray-400">
            Add a CDN connection to browse and manage assets here.
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
