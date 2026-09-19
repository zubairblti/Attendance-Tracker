<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ApprovalHistory extends Model {protected $fillable=['approvable_type','approvable_id','acted_by','stage','action','previous_status','new_status','note'];public function approvable(){return $this->morphTo();}public function actor(){return $this->belongsTo(User::class,'acted_by');}}
