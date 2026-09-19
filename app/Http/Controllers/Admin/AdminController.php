<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;use App\Models\{EditRequest,Leave,TimeClock};use Illuminate\Http\Request;
class AdminController extends Controller {
 private function guard(Request $r,string $permission='dashboard.view'):void{abort_unless($r->user()->hasPermission($permission),403);}
 public function dashboard(Request $r){$this->guard($r);$stats=['employees'=>\App\Models\User::where('employeed',true)->count(),'working'=>TimeClock::whereNull('clock_out')->count(),'pending_leaves'=>Leave::whereIn('status',['pending','pending_manager','pending_admin'])->count(),'pending_edits'=>EditRequest::whereIn('status',['pending','pending_manager','pending_admin'])->count()];$active=TimeClock::with('user','breaks')->whereNull('clock_out')->latest()->get();return view('admin.dashboard',compact('stats','active'));}
 public function users(Request $r){$this->guard($r,'employees.manage');return redirect()->route('admin.employees.index');}
 public function timesheets(Request $r){$this->guard($r,'attendance.view');$clocks=TimeClock::with('user','breaks')->when($r->date,fn($q,$v)=>$q->whereDate('clock_in',$v))->latest('clock_in')->paginate(30);return view('admin.timesheets',compact('clocks'));}
 public function live(Request $r){$this->guard($r);$active=TimeClock::with('user','breaks')->whereNull('clock_out')->latest()->get()->map(fn($x)=>['name'=>$x->user->name,'department'=>$x->user->department,'clock_in'=>$x->clock_in?->format('h:i A'),'state'=>$x->current_state,'worked'=>$x->formattedDuration()]);return response()->json(['active'=>$active,'count'=>$active->count(),'updated_at'=>now()->toIso8601String()]);}
}
