<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Conversation extends Model
{
    protected $fillable = ['type', 'name', 'team_id'];

    public function messages(): HasMany { return $this->hasMany(Message::class)->latest(); }
    public function participants(): BelongsToMany { return $this->belongsToMany(User::class, 'conversation_participants')->withPivot('last_read_at')->withTimestamps(); }
    public function latestMessage(): HasMany { return $this->hasMany(Message::class)->latest()->limit(1); }
}
