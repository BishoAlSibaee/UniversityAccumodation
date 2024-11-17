<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Validator;


class Reservations extends Controller
{
    public static function checkStudentHasActiveReservation($student_id)
    {
        $student = User::find($student_id);
        $today = date("Y-m-d");
        $reservation = Reservation::where('student_id', $student->id)->where('start_date', '<=', $today)->where('expire_date', '>=', $today)->first();
        return $reservation;
    }

    function getReservationBy(Request $request)
    {
        switch ($request->by) {
            case 'student_id':
                $validation = Validator::make($request->all(), [
                    'value' => 'required|numeric|exists:users,id',
                    'by' => 'required|string',
                ]);

                break;
            case 'room_id':
                $validation = Validator::make($request->all(), [
                    'value' => 'required|numeric|exists:rooms,id',
                    'by' => 'required|string',
                ]);

                break;
            case 'student_name':
                $validation = Validator::make($request->all(), [
                    'value' => 'required|string',
                    'by' => 'required|string',
                ]);
                break;
        }

        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        $reservations = Reservation::where($request->by, $request->value)->get();
        if (count($reservations) > 0) {
            return ['result' => 'success', 'code' => 1, "user" => $reservations, "error" => ""];
        } else {
            return ['result' => 'success', 'code' => 1, "user" => 'Not Found Reservation', "error" => ""];
        }
    }

    function getReservationByDate(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'expire_date' => 'required|date',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $reservations = Reservation::where('start_date', '<=', $request->expire_date)->where('expire_date', '>=', $request->start_date)->get();
        if ($reservations->isEmpty()) {
            return ['result' => 'success', 'code' => 1, "user" => 'Not Found Reservation', "error" => ""];

        } else {
            return ['result' => 'success', 'code' => 1, "user" => $reservations, "error" => ""];

        }
    }

    function getReservation()
    {
        return Reservation::all();
    }
}
