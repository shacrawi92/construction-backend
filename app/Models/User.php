<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'locale', 'avatar', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function foreman()
    {
        return $this->hasOne(Foreman::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function assignedProjects()
    {
        return $this->hasMany(Project::class, 'assigned_foreman_id');
    }

    public function laborLogs()
    {
        return $this->hasMany(LaborLog::class, 'foreman_id');
    }

    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class, 'foreman_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForemen($query)
    {
        return $query->role('foreman');
    }

    public function scopeWorkers($query)
    {
        return $query->role('worker');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isAdmin(): bool    { return $this->hasRole('admin'); }
    public function isForeman(): bool  { return $this->hasRole('foreman'); }
    public function isWorker(): bool   { return $this->hasRole('worker'); }

    public function getPreferredLocale(): string
    {
        return $this->locale ?? 'en';
    }
}
