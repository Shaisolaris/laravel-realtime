<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['team_id', 'user_id', 'action', 'subject_type', 'subject_id', 'description', 'metadata'];
    protected $casts = ['metadata' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function scopeForTeam($query, int $teamId) { return $query->where('team_id', $teamId); }
}
