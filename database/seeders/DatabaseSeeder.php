<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\ConfigurationVersion;
use App\Models\OrganizationSetting;
use App\Models\TimeClock;
use App\Models\Leave;
use App\Models\EditRequest;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email'=>'admin@example.com'],['name'=>'System Admin','password'=>'password','role'=>'super_admin','department'=>"HOD'S",'shift_time'=>'18:00','employeed'=>true]);
        User::updateOrCreate(['email'=>'employee@example.com'],['name'=>'Demo Employee','password'=>'password','role'=>'Executive','department'=>'WEB','shift_time'=>'18:00','employeed'=>true]);
        $departmentNames=['SEO','SALES','ADS','PMO','WEB','SMM','CST','HR','IT','CONTENT','QA','ACCOUNTS','CORE',"HOD'S",'HAB BDR'];
        $departments=collect($departmentNames)->mapWithKeys(fn($name)=>[$name=>Department::updateOrCreate(['name'=>$name],['code'=>str($name)->slug('_')->upper()->toString(),'active'=>true])]);
        $admin=User::where('email','admin@example.com')->firstOrFail();
        OrganizationSetting::firstOrCreate([],['name'=>'QuadRoots','tagline'=>'Attendance Tracker','primary_color'=>'#7357b5','accent_color'=>'#58399d','updated_by'=>$admin->id]);

        // Deterministic demo organization: one manager and two employees per department.
        // The demo.* namespace avoids collisions when the original Rails data is imported.
        $demoUsers=collect();
        foreach($departmentNames as $departmentName){
            $slug=str($departmentName)->lower()->replace("'",'')->replace(' ','-')->toString();
            $manager=User::updateOrCreate(['email'=>"demo.manager.$slug@example.com"],[
                'name'=>"$departmentName Demo Manager",'password'=>'password','role'=>$departmentName==="HOD'S"?'HOD':'Manager','department'=>$departmentName,'shift_time'=>'18:00','status'=>'active','employeed'=>true,
            ]);
            $demoUsers->push($manager);
            foreach([1,2] as $number)$demoUsers->push(User::updateOrCreate(['email'=>"demo.employee{$number}.$slug@example.com"],[
                'name'=>"$departmentName Demo Employee $number",'password'=>'password','role'=>'Executive','department'=>$departmentName,'shift_time'=>'18:00','status'=>'active','employeed'=>true,
            ]));
        }

        // Link the legacy department text to normalized departments and assign each employee to its department manager.
        foreach($departments as $name=>$department){
            User::where('department',$name)->update(['department_id'=>$department->id]);
            $manager=User::where('department_id',$department->id)->where('role','Manager')->first();
            if($manager)User::where('department_id',$department->id)->where('role','Executive')->update(['manager_id'=>$manager->id]);
        }

        // Rails treats the HOD'S account as the HOD for these four core departments.
        $digitalHod=User::where('email','demo.manager.hods@example.com')->firstOrFail();
        $digitalHod->hodDepartments()->syncWithoutDetaching($departments->only(['WEB','SEO','ADS','CONTENT'])->pluck('id')->all());

        // A small, idempotent request/attendance dataset for testing manager screens.
        $sampleUsers=$demoUsers->filter(fn($user)=>$user->role==='Executive')->take(6)->values();
        foreach($sampleUsers as $index=>$user){
            $clock=TimeClock::firstOrCreate(['user_id'=>$user->id,'clock_in'=>now()->subDays($index+1)->setTime(18,0)],[
                'clock_out'=>now()->subDays($index+1)->setTime(3,0)->addDay(),'total_duration'=>32400,'break_duration'=>0,'status'=>'on_time','current_state'=>'off','ip_address'=>'127.0.0.1',
            ]);
            $status=['pending','approved','rejected'][$index%3];
            Leave::firstOrCreate(['user_id'=>$user->id,'start_date'=>today()->addDays($index+2),'leave_type'=>$index%2?'medical':'casual'],[
                'end_date'=>today()->addDays($index+2),'reason'=>'Demo leave request for workflow testing','status'=>$status,'approved_by_manager'=>$status==='approved','approved_by'=>$status==='pending'?null:$admin->id,
            ]);
            EditRequest::firstOrCreate(['user_id'=>$user->id,'time_clock_id'=>$clock->id,'request_type'=>'clock_in'],[
                'requested_clock_in'=>$clock->clock_in->copy()->addMinutes(10),'reason'=>'Demo attendance correction request','status'=>$status,'department'=>$user->department,'approved_by_manager'=>$status==='pending'?null:$status==='approved','approved_by_admin'=>$status==='approved','resolved_at'=>$status==='pending'?null:now()->subHours($index+1),
            ]);
        }
        $defaults=[
            'shift'=>['start_time'=>'18:00','end_time'=>'03:00','grace_minutes'=>1,'early_clock_in_minutes'=>30],
            'working_days'=>['weekdays'=>[1,2,3,4,5]],
            'breaks'=>['options'=>[
                ['key'=>'meal','label'=>'Meal Break - 40 Minutes','duration_minutes'=>40,'active'=>true],
                ['key'=>'second','label'=>'Second Break - 30 minutes','duration_minutes'=>30,'active'=>true],
                ['key'=>'meeting','label'=>'Meeting','duration_minutes'=>null,'active'=>true],
                ['key'=>'downtime','label'=>'Downtime','duration_minutes'=>null,'active'=>true],
            ]],
            'attendance'=>['minimum_full_day_minutes'=>270],
        ];
        foreach($defaults as $type=>$value)ConfigurationVersion::firstOrCreate(['configuration_type'=>$type,'scope_type'=>'global','scope_id'=>null,'effective_from'=>'2020-01-01'],['effective_to'=>null,'value'=>$value,'created_by'=>$admin->id]);
    }
}
