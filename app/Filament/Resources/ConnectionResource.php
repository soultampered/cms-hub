<?php

namespace App\Filament\Resources;

use App\CdnAdapters\CdnAdapterFactory;
use App\DbAdapters\DbAdapterFactory;
use App\Enums\ConnectionKind;
use App\Enums\ConnectionType;
use App\Filament\Resources\ConnectionResource\Pages;
use App\Models\AuditLog;
use App\Models\Connection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Throwable;

class ConnectionResource extends Resource
{
    protected static ?string $model = Connection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\Select::make('kind')
                        ->options(collect(ConnectionKind::cases())->mapWithKeys(fn ($kind) => [$kind->value => $kind->getLabel()]))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('type', null)),
                    Forms\Components\Select::make('type')
                        ->options(function (Get $get) {
                            $kind = ConnectionKind::tryFrom($get('kind') ?? '');

                            return $kind
                                ? collect(ConnectionType::forKind($kind))->mapWithKeys(fn ($type) => [$type->value => $type->getLabel()])
                                : [];
                        })
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Connection details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('config.uri')
                        ->label('Connection URI')
                        ->password()->revealable()
                        ->required()
                        ->columnSpanFull()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::Mongo->value),
                    Forms\Components\TextInput::make('config.database')
                        ->label('Database name')
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::Mongo->value),

                    Forms\Components\TextInput::make('config.host')
                        ->required()
                        ->visible(fn (Get $get) => in_array($get('type'), [ConnectionType::Postgres->value, ConnectionType::Mysql->value])),
                    Forms\Components\TextInput::make('config.port')
                        ->required()
                        ->visible(fn (Get $get) => in_array($get('type'), [ConnectionType::Postgres->value, ConnectionType::Mysql->value])),
                    Forms\Components\TextInput::make('config.database')
                        ->label('Database name')
                        ->required()
                        ->visible(fn (Get $get) => in_array($get('type'), [ConnectionType::Postgres->value, ConnectionType::Mysql->value])),
                    Forms\Components\TextInput::make('config.username')
                        ->required()
                        ->visible(fn (Get $get) => in_array($get('type'), [ConnectionType::Postgres->value, ConnectionType::Mysql->value])),
                    Forms\Components\TextInput::make('config.password')
                        ->password()->revealable()
                        ->required()
                        ->visible(fn (Get $get) => in_array($get('type'), [ConnectionType::Postgres->value, ConnectionType::Mysql->value])),

                    Forms\Components\TextInput::make('config.access_key_id')
                        ->label('Access key ID')
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::Cloudflare->value),
                    Forms\Components\TextInput::make('config.secret_access_key')
                        ->label('Secret access key')
                        ->password()->revealable()
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::Cloudflare->value),
                    Forms\Components\TextInput::make('config.bucket')
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::Cloudflare->value),
                    Forms\Components\TextInput::make('config.endpoint')
                        ->label('R2 endpoint')
                        ->helperText('e.g. https://<account_id>.r2.cloudflarestorage.com')
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::Cloudflare->value),
                    Forms\Components\TextInput::make('config.public_base_url')
                        ->label('Public base URL')
                        ->helperText('Custom domain or r2.dev URL assets are served from')
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::Cloudflare->value),
                    Forms\Components\TextInput::make('config.zone_id')
                        ->label('Cloudflare zone ID')
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::Cloudflare->value),
                    Forms\Components\TextInput::make('config.api_token')
                        ->label('Cloudflare API token')
                        ->password()->revealable()
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::Cloudflare->value),

                    Forms\Components\TextInput::make('config.access_key_id')
                        ->label('Access key ID')
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::S3Cloudfront->value),
                    Forms\Components\TextInput::make('config.secret_access_key')
                        ->label('Secret access key')
                        ->password()->revealable()
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::S3Cloudfront->value),
                    Forms\Components\TextInput::make('config.bucket')
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::S3Cloudfront->value),
                    Forms\Components\TextInput::make('config.region')
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::S3Cloudfront->value),
                    Forms\Components\TextInput::make('config.distribution_id')
                        ->label('CloudFront distribution ID')
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::S3Cloudfront->value),

                    Forms\Components\TextInput::make('config.cloud_name')
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::Cloudinary->value),
                    Forms\Components\TextInput::make('config.api_key')
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::Cloudinary->value),
                    Forms\Components\TextInput::make('config.api_secret')
                        ->password()->revealable()
                        ->required()
                        ->visible(fn (Get $get) => $get('type') === ConnectionType::Cloudinary->value),
                ])
                ->visible(fn (Get $get) => filled($get('type'))),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('kind')
                    ->badge(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('last_test_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? ucfirst($state) : 'Not tested')
                    ->color(fn (?string $state) => match ($state) {
                        'ok' => 'success',
                        'error' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('last_tested_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kind')
                    ->options(collect(ConnectionKind::cases())->mapWithKeys(fn ($kind) => [$kind->value => $kind->getLabel()])),
            ])
            ->actions([
                Tables\Actions\Action::make('test')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    ->color('gray')
                    ->action(function (Connection $record) {
                        try {
                            $adapter = $record->kind === ConnectionKind::Database
                                ? DbAdapterFactory::make($record)
                                : CdnAdapterFactory::make($record);

                            $adapter->testConnection();

                            $record->update([
                                'last_tested_at' => now(),
                                'last_test_status' => 'ok',
                                'last_test_message' => null,
                            ]);

                            AuditLog::record('connection.test.ok', $record);

                            Notification::make()->title('Connection OK')->success()->send();
                        } catch (Throwable $e) {
                            $record->update([
                                'last_tested_at' => now(),
                                'last_test_status' => 'error',
                                'last_test_message' => $e->getMessage(),
                            ]);

                            AuditLog::record('connection.test.error', $record, ['message' => $e->getMessage()]);

                            Notification::make()->title('Connection failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(fn (Connection $record) => AuditLog::record('connection.deleted', null, ['name' => $record->name])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConnections::route('/'),
            'create' => Pages\CreateConnection::route('/create'),
            'edit' => Pages\EditConnection::route('/{record}/edit'),
        ];
    }
}
