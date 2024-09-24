<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\User;

class Reservations extends Controller
{
    public static function  checkStudentHasActiveReservation($student_id) {
        $student = User::find($student_id);
        $today = date("Y-m-d");
        $reservation = Reservation::where('student_id',$student->id)->where('start_date','<=',$today)->where('expire_date','>=',$today)->first();
        return $reservation;
    }
}
