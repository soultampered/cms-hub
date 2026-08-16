<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'connection_id',
        'detail',
    ];

    protected $casts = [
        'detail' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function connection()
    {
        return $this->belongsTo(Connection::class);
    }

    public static function record(string $action, ?Connection $connection = null, array $detail = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'connection_id' => $connection?->id,
            'detail' => $detail,
        ]);
    }
}
