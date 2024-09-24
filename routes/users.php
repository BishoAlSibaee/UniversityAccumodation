<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Users;


Route::post('login',[Users::class,'loginStudent']);

Route::group(['middleware'=>'auth:sanctum'],function(){
    
});