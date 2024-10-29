<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverUnderResult extends Model
{
    protected $fillable = ['over_under_id', 'game_id', 'actual_total', 'went_over', 'profit_loss'];

    public function overUnder()
    {
        return $this->belongsTo(OverUnder::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
