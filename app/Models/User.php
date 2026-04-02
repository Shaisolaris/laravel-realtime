<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $fillable = ['name', 'email', 'password', 'team_id', 'presence_status'];
    protected $hidden = ['password'];
    protected $casts = ['password' => 'hashed'];
}
