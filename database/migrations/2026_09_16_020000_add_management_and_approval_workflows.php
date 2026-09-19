<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users',function(Blueprint $t){$t->foreignId('department_id')->nullable()->after('department')->constrained()->nullOnDelete();$t->foreignId('manager_id')->nullable()->after('department_id')->constrained('users')->nullOnDelete();$t->index(['role','employeed']);});
        Schema::table('leaves',function(Blueprint $t){$t->boolean('approved_by_admin')->nullable()->after('approved_by_manager');$t->foreignId('manager_approved_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();$t->foreignId('admin_approved_by')->nullable()->after('manager_approved_by')->constrained('users')->nullOnDelete();$t->dateTime('resolved_at')->nullable();$t->text('decision_note')->nullable();});
        Schema::table('edit_requests',function(Blueprint $t){$t->dateTime('requested_break_in')->nullable();$t->dateTime('requested_break_out')->nullable();$t->foreignId('manager_approved_by')->nullable()->constrained('users')->nullOnDelete();$t->foreignId('admin_approved_by')->nullable()->constrained('users')->nullOnDelete();});
        Schema::create('approval_histories',function(Blueprint $t){$t->id();$t->morphs('approvable');$t->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();$t->string('stage');$t->string('action');$t->string('previous_status');$t->string('new_status');$t->text('note')->nullable();$t->timestamps();});
        Schema::create('role_permissions',function(Blueprint $t){$t->id();$t->string('role');$t->string('permission');$t->timestamps();$t->unique(['role','permission']);});
    }
    public function down(): void {
        Schema::dropIfExists('role_permissions');Schema::dropIfExists('approval_histories');
        Schema::table('edit_requests',fn(Blueprint $t)=>$t->dropConstrainedForeignId('admin_approved_by'));
        Schema::table('edit_requests',fn(Blueprint $t)=>$t->dropConstrainedForeignId('manager_approved_by'));
        Schema::table('edit_requests',fn(Blueprint $t)=>$t->dropColumn(['requested_break_in','requested_break_out']));
        Schema::table('leaves',fn(Blueprint $t)=>$t->dropConstrainedForeignId('admin_approved_by'));
        Schema::table('leaves',fn(Blueprint $t)=>$t->dropConstrainedForeignId('manager_approved_by'));
        Schema::table('leaves',fn(Blueprint $t)=>$t->dropColumn(['approved_by_admin','resolved_at','decision_note']));
        Schema::table('users',fn(Blueprint $t)=>$t->dropConstrainedForeignId('manager_id'));
        Schema::table('users',fn(Blueprint $t)=>$t->dropConstrainedForeignId('department_id'));
    }
};
