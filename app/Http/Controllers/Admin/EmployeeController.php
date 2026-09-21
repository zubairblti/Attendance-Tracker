<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Department,User};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny',User::class);
        $filters=$request->validate([
            'q'=>'nullable|string|max:255',
            'department_id'=>'nullable|integer|exists:departments,id',
            'leader_id'=>'nullable|integer|exists:users,id',
        ]);
        $leader=isset($filters['leader_id'])?User::whereIn('role',['Manager','HOD','Director'])->find($filters['leader_id']):null;
        $users=User::with(['departmentRelation','manager'])
            ->where('role','!=','super_admin')
            ->when($filters['q']??null,fn($query,$value)=>$query->where(fn($search)=>$search->where('name','like',"%$value%")->orWhere('email','like',"%$value%")))
            ->when($filters['department_id']??null,fn($query,$departmentId)=>$query->where('department_id',$departmentId))
            ->when($leader,function($query,User $leader){
                if(in_array($leader->role,['HOD','Director'],true)){
                    $departmentIds=$leader->hodDepartments()->pluck('departments.id');
                    $query->where(fn($scope)=>$scope->where('manager_id',$leader->id)->orWhereIn('department_id',$departmentIds));
                }else $query->where('manager_id',$leader->id);
            })
            ->orderBy('name')->paginate(25)->withQueryString();
        $leaders=User::whereIn('role',['Manager','HOD','Director'])->where('employeed',true)->orderBy('name')->get(['id','name','role']);
        return view('admin.employees.index',$this->formData()+compact('users','leaders'));
    }

    public function create(Request $request){$this->authorize('create',User::class);return view('admin.employees.form',$this->formData());}
    public function store(Request $request){$this->authorize('create',User::class);$data=$this->validated($request);$data['department']=$data['department_id']?Department::find($data['department_id'])?->name:null;$data['employeed']=$request->boolean('employeed');$employee=User::create($data);$employee->hodDepartments()->sync($request->input('hod_departments',[]));return redirect()->route('admin.employees.index')->with('success','Employee created.');}
    public function edit(Request $request,User $employee){$this->guardEmployeeAccount($employee);$this->authorize('update',$employee);return view('admin.employees.form',$this->formData()+compact('employee'));}
    public function update(Request $request,User $employee){$this->guardEmployeeAccount($employee);$this->authorize('update',$employee);$data=$this->validated($request,$employee);if(empty($data['password']))unset($data['password']);$data['department']=$data['department_id']?Department::find($data['department_id'])?->name:null;$data['employeed']=$request->boolean('employeed');$employee->update($data);$employee->hodDepartments()->sync($request->input('hod_departments',[]));return redirect()->route('admin.employees.index')->with('success','Employee updated.');}
    public function toggle(Request $request,User $employee){$this->guardEmployeeAccount($employee);$this->authorize('update',$employee);$employee->update(['employeed'=>!$employee->employeed,'status'=>$employee->employeed?'inactive':'active']);return back()->with('success','Employment status updated.');}
    public function destroy(Request $request,User $employee){$this->guardEmployeeAccount($employee);$this->authorize('delete',$employee);$employee->delete();return back()->with('success','Employee deleted.');}

    private function guardEmployeeAccount(User $user):void{abort_if($user->isSuperAdmin(),403,'Super Admin accounts are managed outside the employee directory.');}
    private function validated(Request $request,?User $employee=null):array{return $request->validate(['name'=>'required|string|max:255','email'=>['required','email',Rule::unique('users')->ignore($employee?->id)],'password'=>[$employee?'nullable':'required','confirmed','min:8'],'role'=>['required',Rule::in(['Executive','Manager','HOD','Director','admin','manage_admin','qa_admin'])],'department_id'=>'nullable|exists:departments,id','manager_id'=>'nullable|exists:users,id','shift_time'=>'required|date_format:H:i','phone_number'=>'nullable|string|max:30','address'=>'nullable|string|max:1000','employeed'=>'nullable|boolean','hod_departments'=>'nullable|array','hod_departments.*'=>'exists:departments,id']);}
    private function formData():array{return ['departments'=>Department::orderBy('name')->get(),'managers'=>User::whereIn('role',['Manager','HOD','Director'])->where('employeed',true)->orderBy('name')->get()];}
}
