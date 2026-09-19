<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
 use HasFactory, Notifiable;
 protected $fillable=['email','password','encrypted_password','name','phone_number','address','role','department','department_id','manager_id','shift_time','status','employeed','sudo_name','image_path'];
 protected $hidden=['encrypted_password','remember_token'];
 protected function casts():array{return ['shift_time'=>'datetime:H:i','employeed'=>'boolean'];}
 public function getAuthPasswordName():string{return 'encrypted_password';} public function getAuthPassword():string{return $this->encrypted_password;} public function setPasswordAttribute($value):void{$this->attributes['encrypted_password']=password_get_info((string)$value)['algo']?$value:password_hash((string)$value,PASSWORD_BCRYPT);}
 public function timeClocks(){return $this->hasMany(TimeClock::class);} public function leaves(){return $this->hasMany(Leave::class);} public function editRequests(){return $this->hasMany(EditRequest::class);} public function notifications(){return $this->hasMany(Notification::class);} public function pushSubscriptions(){return $this->hasMany(PushSubscription::class);}
 public function hodDepartments(){return $this->belongsToMany(Department::class,'department_hod');}
 public function departmentRelation(){return $this->belongsTo(Department::class,'department_id');} public function manager(){return $this->belongsTo(User::class,'manager_id');} public function directReports(){return $this->hasMany(User::class,'manager_id');}
 public function isManager():bool{return $this->role==='Manager';} public function isHod():bool{return in_array($this->role,['HOD','Director'],true)||$this->department==="HOD'S";} public function isSuperAdmin():bool{return $this->role==='super_admin';} public function isAdmin():bool{return in_array($this->role,['admin','super_admin','manage_admin','qa_admin'],true);}
 public function hasPermission(string $permission):bool{if($this->isSuperAdmin())return true;$defaults=['admin'=>['dashboard.view','employees.manage','departments.manage','attendance.view','requests.finalize','reports.export'],'manage_admin'=>['dashboard.view','employees.manage','departments.manage','attendance.view','requests.finalize','reports.export'],'qa_admin'=>['dashboard.view','attendance.view','requests.view','reports.export'],'Manager'=>['dashboard.view','team.view','requests.review'],'HOD'=>['dashboard.view','team.view','requests.review'],'Director'=>['dashboard.view','team.view','requests.review'],'Executive'=>['attendance.own']];return in_array($permission,$defaults[$this->role]??[],true)||\App\Models\RolePermission::where(['role'=>$this->role,'permission'=>$permission])->exists();}
 public function managedDepartmentIds(){return $this->isHod()?$this->hodDepartments()->pluck('departments.id'):collect(array_filter([$this->department_id]));}
 public function initials():string{return collect(preg_split('/\s+/',trim($this->name??'')))->filter()->take(2)->map(fn($p)=>mb_strtoupper(mb_substr($p,0,1)))->join('')?:'?';}
 public function medicalLeavesCount():int{return $this->leaves()->where(['leave_type'=>'medical','status'=>'approved'])->count();} public function casualLeavesCount():int{return $this->leaves()->where(['leave_type'=>'casual','status'=>'approved'])->count();} public function halfDaysCount():int{return $this->leaves()->where(['leave_type'=>'half_day','status'=>'approved'])->count();}
}
