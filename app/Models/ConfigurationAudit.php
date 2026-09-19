<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigurationAudit extends Model
{
    protected $fillable = ['configuration_version_id','configuration_type','scope_type','scope_id','previous_value','new_value','effective_from','changed_by','changed_at'];
    protected function casts(): array { return ['previous_value'=>'array','new_value'=>'array','effective_from'=>'date','changed_at'=>'datetime']; }
    public function version() { return $this->belongsTo(ConfigurationVersion::class, 'configuration_version_id'); }
    public function actor() { return $this->belongsTo(User::class, 'changed_by'); }
}
