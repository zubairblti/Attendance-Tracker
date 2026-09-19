<?php
namespace App\Policies;
use App\Models\{Leave,User};
class LeavePolicy {public function viewAny(User $u):bool{return $u->hasPermission('requests.review')||$u->hasPermission('requests.finalize')||$u->hasPermission('requests.view');}public function decide(User $u,Leave $leave):bool{return $u->hasPermission('requests.finalize')||($u->hasPermission('requests.review')&&$this->manages($u,$leave->user));}private function manages(User $u,User $employee):bool{return $employee->manager_id===$u->id||$u->hodDepartments()->whereKey($employee->department_id)->exists();}}
