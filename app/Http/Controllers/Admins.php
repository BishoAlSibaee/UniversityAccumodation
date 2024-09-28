<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use App\Models\User;
use App\Models\Reservation;
use App\Messages;
use Exception;

class Admins extends Controller
{
    function createNewAdmin(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string',
            'mobile' => 'required|string|min:10|max:10',
            'password' => 'required|confirmed|string',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        $admin = new Admin();
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->mobile = $request->mobile;
        $admin->password = password_hash($request->password, PASSWORD_DEFAULT);

        try {
            $admin->save();
            return ['result' => 'success', 'code' => 1, "user" => $admin, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e];
        }
    }

    function loginAdmin(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|exists:admins,email',
            'password' => 'required|min:4',
        ]);

        if ($validation->fails()) {
            return response(["result" => "failed", 'code' => 0, "error" => $validation->errors()], 200);
        }

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return ["result" => "failed", 'error' => Messages::getMessage("loginFailed"), 'code' => 0];
        }

        $token = $admin->createToken('token')->plainTextToken;

        return ['result' => 'success', 'code' => 1, 'token' => $token];
    }

    function createNewStudent(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'nullable|email|unique:users,email',
            'name' => 'required|string',
            'mobile' => 'required|string|min:10|max:10',
            'student_number' => 'required|string',
            'nationality' => 'string|nullable',
            'college' => 'string|nullable',
            'study_year' => 'string|nullable',
            'term' => 'string|nullable',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        $user = new User();
        $user->name = $request->name;
        if ($request->email != null) {
            $user->email = $request->email;
        }
        $user->mobile = $request->mobile;
        if ($request->student_number != null) {
            $user->student_number = $request->student_number;
        }
        if ($request->nationality != null) {
            $user->nationality = $request->nationality;
        }
        if ($request->college != null) {
            $user->college = $request->college;
        }
        if ($request->study_year != null) {
            $user->study_year = $request->study_year;
        }
        if ($request->term != null) {
            $user->term = $request->term;
        }

        $user->password = password_hash($request->student_number, PASSWORD_DEFAULT);

        try {
            $user->save();
            return ['result' => 'success', 'code' => 1, "user" => $user, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e];
        }
    }

    function getAllStudents()
    {
        return User::all();
    }

    function makeReservation(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'student_id' => 'nullable|numeric|exists:users,id',
            'room_id' => 'required|numeric|exists:rooms,id',
            'student_name' => 'required|string',
            'room_number' => 'required|string',
            'start_date' => 'required|date',
            'expire_date' => 'required|date',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        $reservation = new Reservation();
        $reservation->student_id = $request->student_id;
        $reservation->room_id = $request->room_id;
        $reservation->student_name = $request->student_name;
        $reservation->room_number = $request->room_number;
        $reservation->start_date = $request->start_date;
        $reservation->expire_date = $request->expire_date;

        try {
            $reservation->save();
            return ['result' => 'success', 'code' => 1, "reservation" => $reservation, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e];
        }
    }

    function searchStudentByName(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'student_name' => 'required|string',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        try {
            $students = User::where('name', 'LIKE', '%' . $request->student_name . '%')->get();
            return ['result' => 'success', 'code' => 1, "students" => $students, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e];
        }
    }

    function serachStudentBy(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'value' => 'required|string',
            'by' => 'required|string',
        ]);

        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        $user = User::where($request->by, $request->value)->get();
        if (count($user) > 0) {
            return ['result' => 'success', 'code' => 1, "user" => $user, "error" => ""];
        } else {
            return ['result' => 'success', 'code' => 1, "user" => 'Not Found Student', "error" => ""];
        }
    }


    public function updateInfoStudent(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => "required|numeric|exists:users,id",
            'email' => 'nullable|email|unique:users,email',
            'name' => 'nullable|string',
            'mobile' => 'nullable|string|min:10|max:10|unique:users,mobile',
            'student_number' => 'nullable|string|unique:users,student_number',
            'nationality' => 'string|nullable',
            'college' => 'string|nullable',
            'study_year' => 'string|nullable',
            'term' => 'string|nullable',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $student = User::find($request->id);
        if ($request->has('email')) {
            $student->email = $request->email;
        }
        if ($request->has('name')) {
            $student->name = $request->name;
        }
        if ($request->has('mobile')) {
            $student->mobile = $request->mobile;
        }
        if ($request->has('student_number')) {
            $student->student_number = $request->student_number;
        }
        if ($request->has('nationality')) {
            $student->nationality = $request->nationality;
        }
        if ($request->has('college')) {
            $student->college = $request->college;
        }
        if ($request->has('study_year')) {
            $student->study_year = $request->study_year;
        }
        if ($request->has('term')) {
            $student->term = $request->term;
        }
        try {
            $student->save();
            return ['result' => 'success', 'code' => 1, "user" => $student, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e];
        }
    }
}
