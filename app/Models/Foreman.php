<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Foreman extends Model
{
    protected $fillable = ['user_id', 'current_project_id', 'phone', 'specialty', 'joined_at'];
    protected $casts    = ['joined_at' => 'date'];

    public function user()           { return $this->belongsTo(User::class); }
    public function currentProject() { return $this->belongsTo(Project::class, 'current_project_id'); }
    public function laborLogs()      { return $this->hasMany(LaborLog::class, 'foreman_id', 'user_id'); }
    public function materialRequests(){ return $this->hasMany(MaterialRequest::class, 'foreman_id', 'user_id'); }

    public function getNameAttribute(): string  { return $this->user->name ?? ''; }
    public function getEmailAttribute(): string { return $this->user->email ?? ''; }
}
