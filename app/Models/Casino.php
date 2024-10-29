<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Casino extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'api_key', 'is_active'];

    public function spreads()
    {
        return $this->hasMany(Spread::class);
    }

    public function overUnders()
    {
        return $this->hasMany(OverUnder::class);
    }

    public function moneyLines()
    {
        return $this->hasMany(MoneyLine::class);
    }
}
