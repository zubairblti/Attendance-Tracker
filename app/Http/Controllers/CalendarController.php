<?php
namespace App\Http\Controllers;use Illuminate\Http\Request;
class CalendarController extends Controller {public function index(Request $r){$month=$r->month?:now()->format('Y-m');$start=\Illuminate\Support\Carbon::parse($month.'-01');$clocks=$r->user()->timeClocks()->with('breaks')->whereBetween('clock_in',[$start->copy()->startOfMonth(),$start->copy()->endOfMonth()])->get()->keyBy(fn($x)=>$x->clock_in->toDateString());return view('time_clocks.calendar',compact('clocks','start'));}}
