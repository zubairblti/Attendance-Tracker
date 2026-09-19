<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class OrganizationSetting extends Model { protected $fillable=['name','tagline','logo_path','primary_color','accent_color','updated_by']; public static function current():self{return static::firstOrCreate([],['name'=>'QuadRoots','tagline'=>'Attendance Tracker','primary_color'=>'#7357b5','accent_color'=>'#58399d']);} }
