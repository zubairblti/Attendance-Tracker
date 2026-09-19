<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ConfigurationAudit,Department,User};
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(),403);
        $audits=ConfigurationAudit::with('actor')->latest('changed_at')->paginate(10);
        $departmentNames=Department::pluck('name','id');
        $employeeNames=User::pluck('name','id');
        return view('admin.audits',compact('audits','departmentNames','employeeNames'));
    }
}
