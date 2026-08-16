<?php

namespace App\Filament\Pages;

use App\CdnAdapters\CdnAdapterFactory;
use App\Enums\ConnectionKind;
use App\Models\AuditLog;
use App\Models\Connection;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Throwable;

class Media extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static string $view = 'filament.pages.media';

    public ?int $connectionId = null;

    public string $prefix = '';

    public function mount(): void
    {
        $this->connectionId = Connection::where('kind', ConnectionKind::Cdn)->value('id');
    }

    public function connection(): ?Connection
    {
        return $this->connectionId ? Connection::find($this->connectionId) : null;
    }

    /** @return Collection<int, Connection> */
    public function connections(): Collection
    {
        return Connection::where('kind', ConnectionKind::Cdn)->orderBy('name')->get();
    }

    /** @return array<int, array{path: string, size: int, url: string, updatedAt: string}> */
    public function assets(): array
    {
        if (! $this->connection()) {
            return [];
        }

        try {
            return CdnAdapterFactory::make($this->connection())->listAssets($this->prefix ?: null);
        } catch (Throwable $e) {
            Notification::make()->title('Failed to load assets')->body($e->getMessage())->danger()->send();

            return [];
        }
    }

    public function uploadAction(): Action
    {
        return Action::make('upload')
            ->label('Upload')
            ->icon('heroicon-o-arrow-up-tray')
            ->form([
                TextInput::make('path')->label('Path')->required()->helperText('e.g. images/hero.jpg'),
                FileUpload::make('file')->required(),
            ])
            ->action(function (array $data) {
                $adapter = CdnAdapterFactory::make($this->connection());
                $contents = file_get_contents(\Illuminate\Support\Facades\Storage::disk('local')->path($data['file']));
                $adapter->uploadAsset($data['path'], $contents, mime_content_type(\Illuminate\Support\Facades\Storage::disk('local')->path($data['file'])) ?: 'application/octet-stream');

                AuditLog::record('media.uploaded', $this->connection(), ['path' => $data['path']]);
                Notification::make()->title('Uploaded')->success()->send();
            });
    }

    public function deleteAssetAction(): Action
    {
        return Action::make('deleteAsset')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (array $arguments) {
                CdnAdapterFactory::make($this->connection())->deleteAsset($arguments['path']);
                AuditLog::record('media.deleted', $this->connection(), ['path' => $arguments['path']]);
                Notification::make()->title('Deleted')->success()->send();
            });
    }

    public function purgeAssetAction(): Action
    {
        return Action::make('purgeAsset')
            ->label('Purge cache')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->action(function (array $arguments) {
                CdnAdapterFactory::make($this->connection())->purgeCache([$arguments['path']]);
                AuditLog::record('media.purged', $this->connection(), ['path' => $arguments['path']]);
                Notification::make()->title('Purged')->success()->send();
            });
    }
}
