<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Controllers\Reservations;
use App\Messages;
use Exception;

class Users extends Controller
{
    //

    // function createNewStudent(Request $request) {
    //     $validation = Validator::make($request->all(), [
    //         'email' => 'string|email|unique:users,email',
    //         'name' => 'required|string',
    //         'mobile' => 'required|string|min:10|max:10',
    //         'student_number' => 'required|string',
    //         'nationality' => 'string',
    //         'college' => 'string',
    //         'study_year' => 'string',
    //         'term' => 'string',
    //     ]);
    //     if ($validation->fails()) {
    //         return response(['result'=>'failed','code'=>0,'error'=>$validation->errors()],200);
    //     }

    //     $user = new User();
    //     $user->name = $request->name;
    //     $user->email = $request->email;
    //     $user->mobile = $request->mobile;
    //     $user->student_number = $request->student_number;
    //     $user->nationality = $request->nationality;
    //     $user->college = $request->college;
    //     $user->study_year = $request->study_year;
    //     $user->term = $request->term;
    //     $user->password = password_hash($request->student_number, PASSWORD_DEFAULT);

    //     try {
    //         $user->save();
    //         return ['result'=>'success','code'=>1,"user"=>$user,"error"=>""];
    //     }
    //     catch(Exception $e) {
    //         return ['result'=>'failed','code'=>-1,"error"=>$e];
    //     }
    // }

    function loginStudent(Request $request) {
        $validation = Validator::make($request->all(), [
            'student_number' => 'required|exists:users,student_number',
            'password' => 'required|min:4',
        ]);

        if ($validation->fails()) {
            return response(["result"=>"failed",'code'=>0,"error"=>$validation->errors()],200);
        }

        $user= User::where('student_number', $request->student_number)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return ["result"=>"failed",'error' => Messages::getMessage("loginFailed"),'code'=>0];
        }

        $reservation = Reservations::checkStudentHasActiveReservation($user->id);

        if ($reservation == null) {
            return ["result"=>"failed",'error' => Messages::getMessage("noActiveReservation"),'code'=>0];
        }

        $token = $user->createToken('token')->plainTextToken;

        return ['result'=>'success','code'=>1,'token'=>$token,'reservation'=>$reservation];
    }

}
