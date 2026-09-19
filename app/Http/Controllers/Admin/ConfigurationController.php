<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ConfigurationVersion,Department,User};
use App\Services\ConfigurationManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConfigurationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);
        $versions=ConfigurationVersion::with('creator')->effectiveOn(today())->orderBy('configuration_type')->orderBy('scope_type')->get();
        $departments=Department::where('active',true)->orderBy('name')->get();
        $employees=User::where('employeed',true)->orderBy('name')->get();
        return view('admin.configurations',compact('versions','departments','employees'));
    }

    public function store(Request $request,ConfigurationManager $manager)
    {
        $this->authorizeAdmin($request);
        $data=$request->validate([
            'configuration_type'=>['required',Rule::in(ConfigurationVersion::TYPES)],'scope_type'=>['required',Rule::in(ConfigurationVersion::SCOPES)],
            'scope_id'=>'nullable|integer','effective_from'=>'required|date','start_time'=>'nullable|date_format:H:i','end_time'=>'nullable|date_format:H:i',
            'grace_minutes'=>'nullable|integer|min:0|max:180','early_clock_in_minutes'=>'nullable|integer|min:0|max:180','weekdays'=>'nullable|array','weekdays.*'=>'integer|between:1,7',
            'break_options'=>'nullable|string','minimum_full_day_minutes'=>'nullable|integer|min:1','auto_clock_out_time'=>'nullable|date_format:H:i',
        ]);
        if($data['scope_type']!=='global'&&empty($data['scope_id']))return back()->withErrors(['scope_id'=>'Select the affected department or employee.'])->withInput();
        if($data['scope_type']==='department'&&!Department::whereKey($data['scope_id'])->exists())abort(422);
        if($data['scope_type']==='employee'&&!User::whereKey($data['scope_id'])->exists())abort(422);
        $value=$this->value($data);
        $manager->createVersion($data+['value'=>$value],$request->user());
        return back()->with('success','Configuration saved and affected users notified.');
    }

    private function value(array $data):array
    {
        return match($data['configuration_type']){
            'shift'=>['start_time'=>$data['start_time']??throw \Illuminate\Validation\ValidationException::withMessages(['start_time'=>'Start time is required.']),'end_time'=>$data['end_time']??throw \Illuminate\Validation\ValidationException::withMessages(['end_time'=>'End time is required.']),'grace_minutes'=>(int)($data['grace_minutes']??1),'early_clock_in_minutes'=>(int)($data['early_clock_in_minutes']??30)],
            'working_days'=>['weekdays'=>array_values(array_map('intval',$data['weekdays']??[]))],
            'breaks'=>['options'=>$this->breakOptions($data['break_options']??'[]')],
            'attendance'=>['minimum_full_day_minutes'=>(int)($data['minimum_full_day_minutes']??270)],default=>[]};
    }
    private function breakOptions(string $json):array{$options=json_decode($json,true);if(!is_array($options))throw \Illuminate\Validation\ValidationException::withMessages(['break_options'=>'Break options must be valid JSON.']);foreach($options as $i=>$o)if(empty($o['label']))throw \Illuminate\Validation\ValidationException::withMessages(['break_options'=>"Break option $i requires a label."]);return $options;}
    private function authorizeAdmin(Request $request):void{abort_unless($request->user()->isSuperAdmin(),403,'Only the Super Admin can change attendance configurations.');}
}
