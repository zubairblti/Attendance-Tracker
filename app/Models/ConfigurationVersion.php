<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigurationVersion extends Model
{
    public const TYPES = ['shift', 'breaks', 'working_days', 'attendance'];
    public const SCOPES = ['global', 'department', 'employee'];
    protected $fillable = ['configuration_type','scope_type','scope_id','effective_from','effective_to','value','created_by'];
    protected function casts(): array { return ['effective_from'=>'date','effective_to'=>'date','value'=>'array']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function audit() { return $this->hasOne(ConfigurationAudit::class); }
    public function scopeEffectiveOn($query, $date) { return $query->whereDate('effective_from','<=',$date)->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',$date)); }
    public function scopeForScope($query, string $type, ?int $id) { return $query->where('scope_type',$type)->where('scope_id',$id); }
}
