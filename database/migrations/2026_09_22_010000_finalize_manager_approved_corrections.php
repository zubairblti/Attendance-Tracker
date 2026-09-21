<?php

use App\Models\EditRequest;
use App\Services\AttendanceCorrectionApplier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up():void
    {
        $applier=app(AttendanceCorrectionApplier::class);
        EditRequest::where('status','pending_admin')->where('approved_by_manager',true)->orderBy('id')->chunkById(100,function($requests)use($applier){
            foreach($requests as $request)DB::transaction(function()use($request,$applier){
                $applier->apply($request);
                $request->update(['status'=>'approved','resolved_at'=>$request->resolved_at??now()]);
            });
        });
        EditRequest::where('status','pending_admin')->where(fn($query)=>$query->whereNull('approved_by_manager')->orWhere('approved_by_manager',false))->update(['status'=>'pending_manager']);
    }

    public function down():void
    {
        // Applied attendance corrections cannot be safely reversed automatically.
    }
};
