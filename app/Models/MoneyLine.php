<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoneyLine extends Model
{
    protected $fillable = ['game_id', 'casino_id', 'home_odds', 'away_odds', 'recorded_at'];

    protected $dates = ['recorded_at'];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function casino()
    {
        return $this->belongsTo(Casino::class);
    }

    public function result()
    {
        return $this->hasOne(MoneyLineResult::class);
    }
}
