<?php
namespace App\Services;

use App\Models\{EditRequest,Leave,Notification,User};

class RequestNotifier
{
    public function __construct(private WebPushService $push) {}

    public function created(object $request,string $label):void
    {
        $employee=$request->user;
        $recipients=User::whereKey($employee->manager_id)->whereIn('role',['Manager','HOD','Director'])->get();
        $employee->departmentRelation?->hods()->get()->each(fn($user)=>$recipients->push($user));
        $type=$request instanceof Leave?'leave':'attendance correction';
        $title=$request instanceof EditRequest&&$request->request_type==='clock_tower_not_working'?'New clock tower correction request':"New $label request";
        $message="{$employee->name} submitted a $type request.";
        $url=$request instanceof Leave?'/leaves':'/edit-requests';
        $details=$this->details($request);
        foreach($recipients->unique('id') as $user){
            Notification::create(['user_id'=>$user->id,'type'=>'request_created','title'=>$title,'message'=>$message,'details'=>$details,'scope_type'=>'employee','scope_id'=>$employee->id,'department_name'=>$employee->department,'url'=>$url]);
            $this->push->send($user,$title,$message,$url);
        }
    }

    public function decided(object $request,string $label,string $status):void
    {
        $title=ucfirst($label).' request updated';
        $message="Your $label request is now ".str_replace('_',' ',$status).'.';
        $url=$request instanceof Leave?'/my-leaves':'/my-edit-requests';
        $history=$request->histories()->with('actor')->latest()->first();
        $details=$this->details($request)+['decision_by'=>$history?->actor?->name,'decision'=>$history?->action];
        Notification::create(['user_id'=>$request->user_id,'type'=>'request_decided','title'=>$title,'message'=>$message,'details'=>$details,'scope_type'=>'employee','scope_id'=>$request->user_id,'department_name'=>$request->user->department,'url'=>$url]);
        $this->push->send($request->user,$title,$message,$url);
    }

    private function details(object $request):array
    {
        if($request instanceof Leave)return ['request_kind'=>'leave','request_id'=>$request->id,'leave_type'=>str($request->leave_type)->replace('_',' ')->title()->toString(),'start_date'=>$request->start_date->toDateString(),'end_date'=>$request->end_date->toDateString(),'reason'=>$request->reason,'status'=>$request->status];
        return ['request_kind'=>'attendance correction','request_id'=>$request->id,'request_type'=>str($request->request_type)->replace('_',' ')->title()->toString(),'requested_clock_in'=>$request->requested_clock_in?->format('d M Y, h:i A'),'requested_break_in'=>$request->requested_break_in?->format('d M Y, h:i A'),'requested_break_out'=>$request->requested_break_out?->format('d M Y, h:i A'),'reason'=>$request->reason,'status'=>$request->status];
    }
}
