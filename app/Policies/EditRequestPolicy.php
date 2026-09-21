<?php
namespace App\Policies;
use App\Models\{EditRequest,User};
class EditRequestPolicy {public function viewAny(User $u):bool{return in_array($u->role,['Manager','HOD','Director'],true)&&$u->hasPermission('requests.review');}public function decide(User $u,EditRequest $item):bool{return $this->viewAny($u)&&($item->user->manager_id===$u->id||$u->hodDepartments()->whereKey($item->user->department_id)->exists());}}
