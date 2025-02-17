<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Validator;


class Reservations extends Controller
{

    function getReservationByStudent(Request $request)
    {
        $column = '';
        $validation = Validator::make($request->all(), [
            'type' => 'required|string',
            'word' => 'required|string',
            'start_date' => 'nullable|date',
            'expire_date' => 'nullable|date',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        if ($request->type == '0') {
            $column = 'mobile';
        } else {
            $column = 'student_number';
        }
        $user = User::where($column, '=', $request->word)->first('id');
        if ($user) {
            $query = Reservation::where('student_id', '=', $user->id)->where('is_available', '=', 0);
            if ($request->start_date && $request->expire_date) {
                $query->where(function ($q) use ($request) {
                    $q->where('start_date', '<=', $request->expire_date)
                        ->where('expire_date', '>=', $request->start_date);
                });
            }
            $reservations = $query->get();
            if ($reservations->isNotEmpty()) {
                return ['result' => 'success', 'code' => 1, "reservations" => $reservations, "error" => ""];
            } else {
                return ['result' => 'success', 'code' => 0, "reservations" => [], "error" => "Not Found Reservation For This User"];
            }
        } else {
            return ['result' => 'failed', 'code' => 0, "reservations" => [], "error" => "Not Found User"];
        }
    }

    function getReservationByDate(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'expire_date' => 'required|date',
            'building_id' => 'nullable|integer',
            'floor_id' => 'nullable|integer',
            'suite_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $query = Reservation::where('start_date', '<=', $request->expire_date)->where('expire_date', '>=', $request->start_date)->where('is_available', '=', 0);
        $query->whereHas('room', function ($roomQuery) use ($request) {
            if ($request->building_id) {
                $roomQuery->where('building_id', $request->building_id);
            }
            if ($request->floor_id) {
                $roomQuery->where('floor_id', $request->floor_id);
            }
            if ($request->suite_id) {
                $roomQuery->where('suite_id', $request->suite_id);
            }
            if ($request->room_id) {
                $roomQuery->where('id', $request->room_id);
            }
        });
        $reservations = $query->get();
        if ($reservations->isEmpty()) {
            return ['result' => 'success', 'code' => 0, "reservations" => [], "error" => 'Not Found Reservation'];
        } else {
            return ['result' => 'success', 'code' => 1, "reservations" => $reservations, "error" => ""];
        }
    }

    function getReservationByRoom(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'roomId' => 'required|numeric',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $reservations = Reservation::where('room_id', '=', $request->roomId)
            ->where('expire_date', '>=', now())->where('is_available', '=', 0)->get();
        return ['result' => 'success', 'code' => 1, 'reservations' => $reservations, "error" => ""];
    }

    function getReservation()
    {
        return Reservation::all();
    }

    function setReservationUnavailable(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|integer|exists:reservations,id'
        ]);
        if ($validation->fails()) {

            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        Reservation::find($request->id)->update(['is_available' => 1]);
        return ['result' => 'success', 'code' => 1, 'error' => ''];
    }

    public static function checkStudentHasActiveReservation($student_id)
    {
        // $student = User::find($student_id);
        $today = date("Y-m-d");
        $reservation = Reservation::where('student_id', $student_id)->where('start_date', '<=', $today)->where('expire_date', '>=', $today)->where('is_available', '=', 0)->first();
        return $reservation;
    }
}
