<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\OrganizationSetting; use Illuminate\Http\Request; use Illuminate\Support\Facades\Storage;
class CustomizationController extends Controller {
 public function edit(Request $r){abort_unless($r->user()->isSuperAdmin(),403);return view('admin.customization',['setting'=>OrganizationSetting::current()]);}
 public function update(Request $r){abort_unless($r->user()->isSuperAdmin(),403);$data=$r->validate(['name'=>'required|string|max:80','tagline'=>'nullable|string|max:120','primary_color'=>'required|regex:/^#[0-9A-Fa-f]{6}$/','accent_color'=>'required|regex:/^#[0-9A-Fa-f]{6}$/','logo'=>'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:3072','remove_logo'=>'nullable|boolean']);$setting=OrganizationSetting::current();if($r->hasFile('logo')){if($setting->logo_path)Storage::disk('public')->delete($setting->logo_path);$data['logo_path']=$r->file('logo')->store('branding','public');}elseif($r->boolean('remove_logo')){if($setting->logo_path)Storage::disk('public')->delete($setting->logo_path);$data['logo_path']=null;}unset($data['logo'],$data['remove_logo']);$setting->update($data+['updated_by'=>$r->user()->id]);return back()->with('success','CRM branding updated.');}
}
