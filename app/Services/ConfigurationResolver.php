<?php

namespace App\Services;

use App\Models\{ConfigurationVersion,Department,User};
use Illuminate\Support\Carbon;

class ConfigurationResolver
{
    public function resolve(User $user, string $type, Carbon|string|null $date=null): array
    {
        $date=Carbon::parse($date??today())->toDateString();
        $departmentId=Department::where('name',$user->department)->value('id');
        foreach ([['employee',$user->id],['department',$departmentId],['global',null]] as [$scope,$id]) {
            if($scope==='department'&&!$id)continue;
            $version=ConfigurationVersion::where('configuration_type',$type)->forScope($scope,$id)->effectiveOn($date)->latest('effective_from')->first();
            if($version)return ['version_id'=>$version->id,'scope_type'=>$scope,'scope_id'=>$id,'effective_from'=>$version->effective_from->toDateString(),'value'=>$version->value];
        }
        return ['version_id'=>null,'scope_type'=>'legacy','scope_id'=>null,'effective_from'=>null,'value'=>$this->legacyDefault($user,$type)];
    }

    public function attendanceSnapshot(User $user, Carbon $date): array
    {
        $snapshot=collect(ConfigurationVersion::TYPES)->mapWithKeys(fn($type)=>[$type=>$this->resolve($user,$type,$date)])->all();
        $snapshot['attendance']['value']['auto_clock_out_time']=$snapshot['shift']['value']['end_time'];
        $snapshot['attendance']['value']['auto_clock_out_source']='shift_end';
        return $snapshot;
    }

    private function legacyDefault(User $user,string $type):array
    {
        return match($type){
            'shift'=>['start_time'=>$user->shift_time?->format('H:i')??'18:00','end_time'=>'03:00','grace_minutes'=>1,'early_clock_in_minutes'=>30],
            'working_days'=>['weekdays'=>[1,2,3,4,5]],
            'breaks'=>['options'=>array_map(fn($label)=>['key'=>str($label)->slug()->toString(),'label'=>$label,'duration_minutes'=>null,'active'=>true],\App\Models\WorkBreak::TYPES)],
            'attendance'=>['minimum_full_day_minutes'=>270],default=>[]};
    }
}
