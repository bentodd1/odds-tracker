<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    use HasFactory;

    protected $fillable = ['game_id', 'home_score', 'away_score', 'period'];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
