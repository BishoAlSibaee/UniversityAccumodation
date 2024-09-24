<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Models\Room;
use App\Models\Floor;

class Suite extends Model
{
    use HasFactory;

    public $timestamps = false;

    function rooms():HasMany {
        return $this->hasMany(Room::class);
    }

    function floor():BelongsTo {
        return $this->belongsTo(Floor::class);
    }
}
