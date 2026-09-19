<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; class AdminUser extends Model {protected $fillable=['email','password','role','department'];protected $hidden=['password'];protected function casts():array{return ['password'=>'hashed'];}}
