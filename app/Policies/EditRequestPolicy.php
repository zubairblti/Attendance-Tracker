<?php
namespace App\Policies;
use App\Models\{EditRequest,User};
class EditRequestPolicy {public function viewAny(User $u):bool{return $u->hasPermission('requests.review')||$u->hasPermission('requests.finalize')||$u->hasPermission('requests.view');}public function decide(User $u,EditRequest $item):bool{return $u->hasPermission('requests.finalize')||($u->hasPermission('requests.review')&&($item->user->manager_id===$u->id||$u->hodDepartments()->whereKey($item->user->department_id)->exists()));}}
