<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'group',
        'title',
        'active',
        'description'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
