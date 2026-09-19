<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Leave extends Model { protected $fillable=['user_id','leave_type','start_date','end_date','reason','status','approved_by_manager','approved_by_admin','approved_by','manager_approved_by','admin_approved_by','resolved_at','decision_note','medical_certificate_path']; protected function casts():array{return ['start_date'=>'date','end_date'=>'date','approved_by_manager'=>'boolean','approved_by_admin'=>'boolean','resolved_at'=>'datetime'];} public function user(){return $this->belongsTo(User::class);} public function manager(){return $this->belongsTo(User::class,'approved_by');} public function histories(){return $this->morphMany(ApprovalHistory::class,'approvable');} }
