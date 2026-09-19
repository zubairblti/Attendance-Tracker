<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        DB::table('users')->whereNotNull('department')->where('department','!=',"HOD'S")->distinct()->pluck('department')->each(fn($name)=>DB::table('departments')->insertOrIgnore(['name'=>$name,'code'=>str($name)->slug('_')->upper()->toString(),'active'=>true,'created_at'=>now(),'updated_at'=>now()]));

        Schema::create('department_hod', function (Blueprint $table) {
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['department_id', 'user_id']);
        });

        Schema::create('configuration_versions', function (Blueprint $table) {
            $table->id();
            $table->string('configuration_type', 50);
            $table->string('scope_type', 20); // global, department, employee
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->json('value');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['configuration_type', 'scope_type', 'scope_id'], 'config_scope_index');
            $table->index(['effective_from', 'effective_to']);
        });

        Schema::create('configuration_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuration_version_id')->constrained()->cascadeOnDelete();
            $table->string('configuration_type', 50);
            $table->string('scope_type', 20);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->json('previous_value')->nullable();
            $table->json('new_value');
            $table->date('effective_from');
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('changed_at');
            $table->timestamps();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('configuration_audit_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('type', 80)->default('general')->after('configuration_audit_id');
            $table->json('previous_value')->nullable()->after('message');
            $table->json('new_value')->nullable()->after('previous_value');
            $table->date('effective_date')->nullable()->after('new_value');
            $table->string('scope_type', 20)->nullable()->after('effective_date');
            $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
            $table->string('department_name')->nullable()->after('scope_id');
        });

        Schema::table('time_clocks', function (Blueprint $table) {
            $table->json('applied_configuration')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('time_clocks', fn (Blueprint $table) => $table->dropColumn('applied_configuration'));
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['configuration_audit_id']);
            $table->dropColumn(['configuration_audit_id','type','previous_value','new_value','effective_date','scope_type','scope_id','department_name']);
        });
        Schema::dropIfExists('configuration_audits');
        Schema::dropIfExists('configuration_versions');
        Schema::dropIfExists('department_hod');
        Schema::dropIfExists('departments');
    }
};
