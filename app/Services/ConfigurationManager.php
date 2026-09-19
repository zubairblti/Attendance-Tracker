<?php

namespace App\Services;

use App\Events\ConfigurationChanged;
use App\Models\{ConfigurationAudit,ConfigurationVersion,Department,Notification,User};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ConfigurationManager
{
    public function createVersion(array $data,User $actor):ConfigurationVersion
    {
        return DB::transaction(function()use($data,$actor){
            $date=Carbon::parse($data['effective_from'])->startOfDay();$scopeId=$data['scope_type']==='global'?null:(int)$data['scope_id'];
            $base=ConfigurationVersion::where('configuration_type',$data['configuration_type'])->forScope($data['scope_type'],$scopeId)->lockForUpdate();
            $sameDate=(clone $base)->whereDate('effective_from',$date)->first();
            $previous=(clone $base)->effectiveOn($date)->latest('effective_from')->first();
            $next=(clone $base)->whereDate('effective_from','>',$date)->oldest('effective_from')->first();
            $previousValue=$sameDate?->value??$previous?->value;
            if($sameDate){
                $sameDate->update(['value'=>$data['value'],'created_by'=>$actor->id]);
                $version=$sameDate->fresh();
            }else{
                $version=ConfigurationVersion::create(['configuration_type'=>$data['configuration_type'],'scope_type'=>$data['scope_type'],'scope_id'=>$scopeId,'effective_from'=>$date,'effective_to'=>$next?$next->effective_from->copy()->subDay():null,'value'=>$data['value'],'created_by'=>$actor->id]);
                if($previous)$previous->update(['effective_to'=>$date->copy()->subDay()]);
            }
            $audit=ConfigurationAudit::create(['configuration_version_id'=>$version->id,'configuration_type'=>$version->configuration_type,'scope_type'=>$version->scope_type,'scope_id'=>$scopeId,'previous_value'=>$previousValue,'new_value'=>$version->value,'effective_from'=>$date,'changed_by'=>$actor->id,'changed_at'=>now()]);
            $recipients=$this->recipients($version);$department=$version->scope_type==='department'?Department::find($scopeId):null;
            $title=str($version->configuration_type)->replace('_',' ')->title().' Updated';
            foreach($recipients as $user)Notification::create(['user_id'=>$user->id,'configuration_audit_id'=>$audit->id,'type'=>'configuration_changed','title'=>$title,'message'=>$this->description($version,$department),'previous_value'=>$previousValue,'new_value'=>$version->value,'effective_date'=>$date,'scope_type'=>$version->scope_type,'scope_id'=>$scopeId,'department_name'=>$department?->name,'url'=>'/notifications']);
            ConfigurationChanged::dispatch($audit,$recipients->pluck('id')->all());
            return $version;
        });
    }

    private function recipients(ConfigurationVersion $version)
    {
        $query=User::query();
        if($version->scope_type==='global')return $query->where(fn($q)=>$q->where('employeed',true)->orWhereIn('role',['super_admin','admin']))->get()->unique('id');
        if($version->scope_type==='employee')return $query->whereKey($version->scope_id)->orWhere('role','super_admin')->get()->unique('id');
        $department=Department::findOrFail($version->scope_id);$hodIds=$department->hods()->pluck('users.id')->unique();
        return $query->where('department',$department->name)->orWhere('role','super_admin')->orWhereIn('id',$hodIds)->get()->unique('id');
    }
    private function description(ConfigurationVersion $version,?Department $department):string{return sprintf('%s configuration changed for %s, effective %s.',str($version->configuration_type)->replace('_',' ')->title(),$department?->name??ucfirst($version->scope_type),$version->effective_from->format('d F Y'));}
}
