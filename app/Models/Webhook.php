<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    protected $fillable = ['team_id', 'url', 'secret', 'events', 'is_active', 'last_triggered_at'];
    protected $casts = ['events' => 'array', 'is_active' => 'boolean', 'last_triggered_at' => 'datetime'];

    public function deliveries(): HasMany { return $this->hasMany(WebhookDelivery::class); }
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function subscribesTo(string $event): bool { return in_array('*', $this->events ?? []) || in_array($event, $this->events ?? []); }
}
