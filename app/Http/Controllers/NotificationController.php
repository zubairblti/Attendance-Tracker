<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request){$notifications=$request->user()->notifications()->latest()->paginate(25);return view('notifications.index',compact('notifications'));}
    public function unread(Request $request){return response()->json(['count'=>$request->user()->notifications()->whereNull('read_at')->count(),'notifications'=>$request->user()->notifications()->whereNull('read_at')->latest()->limit(10)->get()]);}
    public function read(Request $request,Notification $notification){abort_unless($notification->user_id===$request->user()->id,403);$notification->markRead();return $request->expectsJson()?response()->json(['ok'=>true]):back();}
    public function readAll(Request $request){$request->user()->notifications()->whereNull('read_at')->update(['read_at'=>now()]);return $request->expectsJson()?response()->json(['ok'=>true]):back()->with('success','All notifications marked as read.');}
    public function destroy(Request $request,Notification $notification){abort_unless($notification->user_id===$request->user()->id,403);$notification->delete();return $request->expectsJson()?response()->json(['ok'=>true]):back()->with('success','Notification deleted.');}
}
