<?php

namespace App\Models;

use App\Enums\ConnectionKind;
use App\Enums\ConnectionType;
use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    protected $fillable = [
        'name',
        'kind',
        'type',
        'config',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    protected $casts = [
        'kind' => ConnectionKind::class,
        'type' => ConnectionType::class,
        'config' => 'encrypted:array',
        'last_tested_at' => 'datetime',
    ];

    protected $hidden = [
        'config',
    ];
}
