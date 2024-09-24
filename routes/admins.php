<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admins;
use App\Http\Controllers\Buildings;


Route::post('create_admin',[Admins::class,'createNewAdmin']);
Route::post('login',[Admins::class,'loginAdmin']);

Route::group(['middleware'=>'auth:sanctum'],function(){
    Route::post('createStudent',[Admins::class,'createNewStudent']);
    Route::post('addBuilding',[Buildings::class,'addBuilding']);
    Route::post('addFloor',[Buildings::class,'addFloor']);
    Route::post('addSuite',[Buildings::class,'addSuite']);
    Route::post('addRoom',[Buildings::class,'addRoom']);
    Route::get('getAllStudents',[Admins::class,'getAllStudents']);
    Route::post('searchStudentByName',[Admins::class,'searchStudentByName']);
    Route::get('getBuildings',[Buildings::class,'getBuildings']);
    Route::get('getFloors',[Buildings::class,'getFloors']);
    Route::get('getSuites',[Buildings::class,'getSuites']);
    Route::get('getRooms',[Buildings::class,'getRooms']);
    Route::post('makeReservation',[Admins::class,'makeReservation']);
});