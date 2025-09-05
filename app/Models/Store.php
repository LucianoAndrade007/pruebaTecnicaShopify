<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'platform',
        'store_url',
        'consumer_key',
        'consumer_secret',
        'active',
        'last_sync_data',
        'last_synced_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_sync_data' => 'array',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'consumer_key',
        'consumer_secret',
    ];
}