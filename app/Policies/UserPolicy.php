<?php
namespace App\Policies;
use App\Models\User;
class UserPolicy {public function viewAny(User $u):bool{return $u->hasPermission('employees.manage')||$u->hasPermission('team.view');}public function create(User $u):bool{return $u->hasPermission('employees.manage');}public function update(User $u,User $target):bool{return $u->hasPermission('employees.manage')&&(!$target->isSuperAdmin()||$u->isSuperAdmin());}public function delete(User $u,User $target):bool{return $this->update($u,$target)&&$u->id!==$target->id;}}
