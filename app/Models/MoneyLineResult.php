<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoneyLineResult extends Model
{
    protected $fillable = ['money_line_id', 'game_id', 'home_won', 'profit_loss'];

    public function moneyLine()
    {
        return $this->belongsTo(MoneyLine::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
