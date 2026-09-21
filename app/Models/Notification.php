<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Notification extends Model {
 protected $fillable=['user_id','configuration_audit_id','type','title','message','details','previous_value','new_value','effective_date','scope_type','scope_id','department_name','url','read_at'];
 protected function casts():array{return ['read_at'=>'datetime','effective_date'=>'date','details'=>'array','previous_value'=>'array','new_value'=>'array'];}
 public function user(){return $this->belongsTo(User::class);} public function audit(){return $this->belongsTo(ConfigurationAudit::class,'configuration_audit_id');}
 public function markRead():void{if(!$this->read_at)$this->update(['read_at'=>now()]);}
 protected static function booted():void{static::created(fn($notification)=>\App\Events\NotificationCreated::dispatch($notification));}
}
