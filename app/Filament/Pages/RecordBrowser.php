<?php

namespace App\Filament\Pages;

use App\DbAdapters\DbAdapterFactory;
use App\Enums\ConnectionKind;
use App\Models\AuditLog;
use App\Models\Connection;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Throwable;

class RecordBrowser extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Records';

    protected static string $view = 'filament.pages.record-browser';

    public ?int $connectionId = null;

    public ?string $collection = null;

    public int $page = 1;

    public int $pageSize = 10;

    public ?string $editingId = null;

    public function mount(): void
    {
        $this->connectionId = Connection::where('kind', ConnectionKind::Database)->value('id');
        $this->syncFirstCollection();
    }

    public function updatedConnectionId(): void
    {
        $this->collection = null;
        $this->page = 1;
        $this->syncFirstCollection();
    }

    public function updatedCollection(): void
    {
        $this->page = 1;
    }

    private function syncFirstCollection(): void
    {
        if (! $this->collection && $this->connection()) {
            $this->collection = $this->collections()->first();
        }
    }

    public function connection(): ?Connection
    {
        return $this->connectionId ? Connection::find($this->connectionId) : null;
    }

    /** @return Collection<int, Connection> */
    public function connections(): Collection
    {
        return Connection::where('kind', ConnectionKind::Database)->orderBy('name')->get();
    }

    /** @return Collection<int, string> */
    public function collections(): Collection
    {
        if (! $this->connection()) {
            return collect();
        }

        try {
            return collect(DbAdapterFactory::make($this->connection())->listCollections());
        } catch (Throwable) {
            return collect();
        }
    }

    /** @return array<int, array{name: string, type: string, nullable: bool}> */
    public function schema(): array
    {
        if (! $this->connection() || ! $this->collection) {
            return [];
        }

        try {
            return DbAdapterFactory::make($this->connection())->getSchema($this->collection);
        } catch (Throwable) {
            return [];
        }
    }

    /** @return array{records: array<int, array<string, mixed>>, total: int} */
    public function results(): array
    {
        if (! $this->connection() || ! $this->collection) {
            return ['records' => [], 'total' => 0];
        }

        try {
            return DbAdapterFactory::make($this->connection())->listRecords($this->collection, [
                'page' => $this->page,
                'pageSize' => $this->pageSize,
            ]);
        } catch (Throwable $e) {
            Notification::make()->title('Failed to load records')->body($e->getMessage())->danger()->send();

            return ['records' => [], 'total' => 0];
        }
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    /** @return Component[] */
    private function recordFormFields(): array
    {
        return collect($this->schema())
            ->reject(fn ($field) => $field['name'] === 'id')
            ->map(function (array $field) {
                $input = match ($field['type']) {
                    'boolean' => Toggle::make($field['name']),
                    'datetime' => DateTimePicker::make($field['name']),
                    'number' => TextInput::make($field['name'])->numeric(),
                    default => TextInput::make($field['name']),
                };

                return $input->label(str($field['name'])->headline())->required(! $field['nullable']);
            })
            ->values()
            ->all();
    }

    public function createRecordAction(): Action
    {
        return Action::make('createRecord')
            ->label('New record')
            ->icon('heroicon-o-plus')
            ->modalHeading(fn () => "New {$this->collection} record")
            ->form($this->recordFormFields())
            ->action(function (array $data) {
                $adapter = DbAdapterFactory::make($this->connection());
                $adapter->createRecord($this->collection, $data);
                AuditLog::record('record.created', $this->connection(), ['collection' => $this->collection]);
                Notification::make()->title('Record created')->success()->send();
            });
    }

    public function editRecordAction(): Action
    {
        return Action::make('editRecord')
            ->label('Edit')
            ->icon('heroicon-o-pencil')
            ->modalHeading(fn () => "Edit {$this->collection} record")
            ->fillForm(fn (array $arguments) => DbAdapterFactory::make($this->connection())->getRecord($this->collection, $arguments['id']) ?? [])
            ->form($this->recordFormFields())
            ->action(function (array $data, array $arguments) {
                $adapter = DbAdapterFactory::make($this->connection());
                $adapter->updateRecord($this->collection, $arguments['id'], $data);
                AuditLog::record('record.updated', $this->connection(), ['collection' => $this->collection, 'id' => $arguments['id']]);
                Notification::make()->title('Record updated')->success()->send();
            });
    }

    public function deleteRecordAction(): Action
    {
        return Action::make('deleteRecord')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (array $arguments) {
                $adapter = DbAdapterFactory::make($this->connection());
                $adapter->deleteRecord($this->collection, $arguments['id']);
                AuditLog::record('record.deleted', $this->connection(), ['collection' => $this->collection, 'id' => $arguments['id']]);
                Notification::make()->title('Record deleted')->success()->send();
            });
    }
}
