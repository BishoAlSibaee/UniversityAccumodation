<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admins;
use App\Http\Controllers\Buildings;
use App\Http\Controllers\Reservations;

Route::post('create_admin', [Admins::class, 'createNewAdmin']);
Route::post('login', [Admins::class, 'loginAdmin']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    //=========================================Admin=============================================
    Route::post('createStudent', [Admins::class, 'createNewStudent']);
    Route::get('getAllStudents', [Admins::class, 'getAllStudents']);
    Route::post('searchStudentByName', [Admins::class, 'searchStudentByName']);
    Route::post('makeReservation', [Admins::class, 'makeReservation']);
    Route::post('serachStudentBy', [Admins::class, 'serachStudentBy']);
    Route::post('getAllAdmin', [Admins::class, 'getAllAdmin']);
    Route::post('updateAdmin', [Admins::class, 'updateAdmin']);
    Route::post('inActiveAdmin', [Admins::class, 'inActiveAdmin']);
    Route::post('updateInfoStudent', [Admins::class, 'updateInfoStudent']);
    Route::post('deleteStudent', [Admins::class, 'deleteStudent']);
    Route::post('addCollege', [Admins::class, 'addCollege']);
    Route::get('getAllCollege', [Admins::class, 'getAllCollege']);
    Route::post('checkReservation', [Admins::class, 'checkReservation']);
    Route::get('getAllRoomType', [Admins::class, 'getAllRoomType']);
    Route::post('addRoomType', [Admins::class, 'addRoomType']);
    Route::post('addFacilitie', [Admins::class, 'addFacilitie']);
    Route::get('getFacilitie', [Admins::class, 'getFacilitie']);
    Route::post('getFacilitieByBuilding', [Admins::class, 'getFacilitieByBuilding']);
    Route::post('getFacilitieByRoom', [Admins::class, 'getFacilitieByRoom']);
    Route::post('updateReservation', [Admins::class, 'updateReservation']);
    //=========================================Buildings=============================================
    Route::post('addBuilding', [Buildings::class, 'addBuilding']);
    Route::post('addFloor', [Buildings::class, 'addFloor']);
    Route::post('addSuite', [Buildings::class, 'addSuite']);
    Route::post('addRoom', [Buildings::class, 'addRoom']);
    Route::get('getBuildings', [Buildings::class, 'getBuildings']);
    Route::get('getFloors', [Buildings::class, 'getFloors']);
    Route::get('getSuites', [Buildings::class, 'getSuites']);
    Route::get('getRooms', [Buildings::class, 'getRooms']);
    Route::post('deleteRoom', [Buildings::class, 'deleteRoom']);
    Route::post('deleteFloor', [Buildings::class, 'deleteFloor']);
    Route::post('deleteSuite', [Buildings::class, 'deleteSuite']);
    Route::post('deleteBuilding', [Buildings::class, 'deleteBuilding']);
    Route::post('updateRoom', [Buildings::class, 'updateRoom']);
    Route::post('updateSuite', [Buildings::class, 'updateSuite']);
    Route::post('updateFloor', [Buildings::class, 'updateFloor']);
    Route::post('updateBuilding', [Buildings::class, 'updateBuilding']);
    Route::post('getDataById', [Buildings::class, 'getDataById']);
    Route::post('getBuildingData', [Buildings::class, 'getBuildingData']);
    Route::post('addMultiRoom', [Buildings::class, 'addMultiRoom']);
    //=========================================Reservation=============================================
    Route::post('getReservationByDate', [Reservations::class, 'getReservationByDate']);
    Route::get('getReservation', [Reservations::class, 'getReservation']);
    Route::post('getReservationByStudent', [Reservations::class, 'getReservationByStudent']);
    Route::post('setReservationUnavailable', [Reservations::class, 'setReservationUnavailable']);
    Route::post('getReservationByRoom', [Reservations::class, 'getReservationByRoom']);
});
