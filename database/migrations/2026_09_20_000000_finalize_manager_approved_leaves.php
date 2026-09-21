<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up():void
    {
        DB::table('leaves')->where('status','pending_admin')->where('approved_by_manager',true)->update([
            'status'=>'approved',
            'resolved_at'=>DB::raw('COALESCE(resolved_at, updated_at)'),
        ]);
    }

    public function down():void
    {
        DB::table('leaves')->where('status','approved')->where('approved_by_manager',true)->whereNull('admin_approved_by')->update([
            'status'=>'pending_admin',
            'resolved_at'=>null,
        ]);
    }
};
