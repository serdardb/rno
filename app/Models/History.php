<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory;

    protected $table = "histories";
    protected $fillable = ["user_id", "video_id", "earning", "created_at", "updated_at"];

    public function scopeLastxhour($query,$x)
    {
        return $query->where('created_at', '>=', Carbon::now()->subHours($x))->get();
        return $query->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)')->get();
    }

    public function scopeLast2hours($query)
    {
        return $query->where('created_at ', '=', Carbon::today())
            ->whereTime('created_at', '>', Carbon::now()->subHours(2));
    }

    public function scopeLast24hours($query)
    {
        return $query->where('created_at ', '=', Carbon::today())
            ->whereTime('created_at', '>', Carbon::now()->subHours(24));
    }
}
