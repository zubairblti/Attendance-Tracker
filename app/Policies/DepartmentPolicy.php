<?php
namespace App\Policies;
use App\Models\{Department,User};
class DepartmentPolicy {public function viewAny(User $u):bool{return $u->hasPermission('departments.manage');}public function create(User $u):bool{return $u->hasPermission('departments.manage');}public function update(User $u,Department $d):bool{return $u->hasPermission('departments.manage');}public function delete(User $u,Department $d):bool{return $u->hasPermission('departments.manage');}}
