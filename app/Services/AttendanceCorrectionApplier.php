<?php

namespace App\Services;

use App\Models\{EditRequest,WorkBreak};

class AttendanceCorrectionApplier
{
    public function apply(EditRequest $request):void
    {
        $clock=$request->timeClock()->with(['breaks','user'])->firstOrFail();
        if(in_array($request->request_type,['clock_in','clock_tower_not_working'],true)){
            $clock->update(['clock_in'=>$request->requested_clock_in]);
        }elseif($request->request_type==='forgot_break_start'){
            WorkBreak::firstOrCreate(
                ['time_clock_id'=>$request->time_clock_id,'break_in'=>$request->requested_break_in??$request->requested_clock_in],
                ['break_out'=>$request->requested_break_out,'break_type'=>$request->break_reason?:'Corrected Break']
            );
        }elseif($request->request_type==='forgot_break_end'){
            $break=$clock->breaks()->whereNull('break_out')->latest('break_in')->first();
            $break?->update(['break_out'=>$request->requested_break_out??$request->requested_clock_in]);
        }
        $clock->refresh()->recalculateAfterCorrection();
    }
}
