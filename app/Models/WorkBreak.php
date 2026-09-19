<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkBreak extends Model { protected $table='breaks'; public const TYPES=['Meal Break - 40 Minutes','Second Break - 30 minutes','Meeting','Extra Break','Downtime','Cigarette Break','Chai/Coffee Break','Washroom Break','Namaz Break']; protected $fillable=['time_clock_id','break_in','break_out','break_type']; protected function casts():array{return ['break_in'=>'datetime','break_out'=>'datetime'];} public function timeClock(){return $this->belongsTo(TimeClock::class);} public function duration():int{return $this->break_in?(int)$this->break_in->diffInSeconds($this->break_out??now()):0;} }
