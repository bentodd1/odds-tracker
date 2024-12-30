<?php

// app/Models/Visitor.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $fillable = [
        'ip_address',
        'user_agent',
        'country',
        'city',
        'region',
        'latitude',
        'longitude',
        'visited_at',
        'page_url'
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];
}
