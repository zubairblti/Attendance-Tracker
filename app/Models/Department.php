<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'code', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }
    public function hods() { return $this->belongsToMany(User::class, 'department_hod'); }
    public function users() { return $this->hasMany(User::class, 'department', 'name'); }
    public function assignedUsers() { return $this->hasMany(User::class); }
    public function managers() { return $this->assignedUsers()->where('role','Manager'); }
}
