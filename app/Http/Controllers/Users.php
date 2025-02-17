<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Controllers\Reservations;
use App\Messages;
use App\Models\Facilitie;
use App\Models\Room;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Suite;
use Exception;

class Users extends Controller
{
    function createNewStudent(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'string|email|unique:users,email',
            'name' => 'required|string',
            'mobile' => 'required|string|min:10|max:10',
            'student_number' => 'required|string',
            'nationality' => 'string',
            'college' => 'string',
            'study_year' => 'string',
            'term' => 'string',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->mobile = $request->mobile;
        $user->student_number = $request->student_number;
        $user->nationality = $request->nationality;
        $user->college = $request->college;
        $user->study_year = $request->study_year;
        $user->term = $request->term;
        $user->password = password_hash($request->student_number, PASSWORD_DEFAULT);

        try {
            $user->save();
            return ['result' => 'success', 'code' => 1, "user" => $user, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e];
        }
    }

    function loginStudent1(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'student_number' => 'required|exists:users,student_number',
            'password' => 'required'
            // 'password' => 'required|min:4',
        ]);

        if ($validation->fails()) {
            return response(["result" => "failed", 'code' => 0, "error" => $validation->errors()], 200);
        }

        $user = User::where('student_number', $request->student_number)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ["result" => "failed", 'error' => Messages::getMessage("loginFailed"), 'code' => 0];
        }

        $reservation = Reservations::checkStudentHasActiveReservation($user->id);

        if ($reservation == null) {
            return ["result" => "failed", 'error' => Messages::getMessage("noActiveReservation"), 'code' => 0];
        }

        $token = $user->createToken('token')->plainTextToken;

        return ['result' => 'success', 'code' => 1, 'token' => $token, 'reservation' => $reservation, 'user' => $user];
    }

    function loginStudent(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'student_number' => 'required|numeric',
            'password' => 'required|min:4',
        ]);
        if ($validation->fails()) {
            return response(["result" => "failed", 'code' => 0, "error" => $validation->errors()], 200);
        }
        $user = User::where('student_number', $request->student_number)->orWhere('mobile', $request->student_number)->first();
        if (!$user) {
            // 'error' => '0'=> Student Number Failed
            return ["result" => "failed", 'error' => 0, 'code' => 0];
        }

        if (!Hash::check($request->password, $user->password)) {
            // 'error' => '1'=> Student Password Failed
            return ["result" => "failed", 'error' => 1, 'code' => 0];
        }
        if ($user->is_active == 1) {
            //0=>The account is deleted
            return ['result' => 'success', 'code' => 1, 'user' => "0"];
        }
        $token = $user->createToken('token')->plainTextToken;
        return ['result' => 'success', 'code' => 1, 'token' => $token, 'userID' => $user->id];
    }

    function getInfoUser(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
        ]);
        if ($validation->fails()) {
            return response(["result" => "failed", 'code' => 0, "error" => $validation->errors()], 200);
        }
        $user = User::where('id', '=', $request->user_id)->first();
        if ($user->is_active == 1) {
            //0=>The account is deleted
            return ['result' => 'success', 'code' => 1, 'user' => "0"];
        }
        $reservation = Reservations::checkStudentHasActiveReservation(student_id: $user->id);
        if ($reservation != null) {
            // return $reservation->room_id;
            $myRoom = Room::find($reservation->room_id);
            if ($reservation->facility_ids != '[]') {
                $facilitie = $this->getFacilitieByRoomForApp($reservation->room_id, $reservation->facility_ids);
            }
        }
        return ['result' => 'success', 'code' => 1, 'user' => $user, 'reservation' => $reservation, 'facilitie' => $facilitie ?? [], 'myRoom' => $myRoom ?? []];

    }

    function getFacilitieByRoomForApp($roomId, $facilitie_ids)
    {
        $facilitie_ids = json_decode($facilitie_ids, true);
        $room = Room::find($roomId);
        $allFacilities = Facilitie::all();
        $facilities = [];
        if ($room->suite_id != 0) {
            //  المرافق المرتبطة بالسويت
            $suiteFacilities = $allFacilities->where('suite_id', $room->suite_id);
            $facilities = array_merge($facilities, $suiteFacilities->toArray());
        }
        //  المرافق المرتبطة بالطابق
        $floorFacilities = $allFacilities->where('building_id', $room->building_id)->where('floor_id', $room->floor_id)->where('suite_id', 0);
        if (count($floorFacilities) > 0) {
            $facilities = array_merge($facilities, $floorFacilities->toArray());
        }

        //  المرافق المرتبطة بالمبنى
        $buildingFacilities = $allFacilities->where('building_id', $room->building_id)->where('floor_id', 0)->where('suite_id', 0);
        if (count($buildingFacilities) > 0) {
            $facilities = array_merge($facilities, $buildingFacilities->toArray());
        }
        //  المرافق العامة
        $generalFacilities = $allFacilities->where('building_id', 0)->where('floor_id', 0)->where('suite_id', 0);
        if (count($generalFacilities) > 0) {
            $facilities = array_merge($facilities, $generalFacilities->toArray());
        }

        $filteredFacilities = array_filter($facilities, function ($facility) use ($facilitie_ids) {
            return in_array($facility['id'], $facilitie_ids);
        });

        // جلب أسماء المباني والطوابق والسويتات دفعة واحدة
        $buildingIds = array_column($filteredFacilities, 'building_id');
        $floorIds = array_column($filteredFacilities, 'floor_id');
        $suiteIds = array_column($filteredFacilities, 'suite_id');

        $buildings = Building::whereIn('id', $buildingIds)->get()->keyBy('id');
        $floors = Floor::whereIn('id', $floorIds)->get()->keyBy('id');
        $suites = Suite::whereIn('id', $suiteIds)->get()->keyBy('id');

        // تحديث المرافق بالأسماء
        foreach ($filteredFacilities as &$facility) {
            $facility['building_name'] = $buildings[$facility['building_id']]->name ?? '0';
            $facility['floor_number'] = $floors[$facility['floor_id']]->number ?? '0';
            $facility['suite_number'] = $suites[$facility['suite_id']]->number ?? '0';
        }
        return $filteredFacilities;
    }

    function checkReservationAndUser(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
        ]);
        if ($validation->fails()) {
            return response(["result" => "failed", 'code' => 0, "error" => $validation->errors()], 200);
        }

        $user = User::where("id", '=', $request->user_id)->first();
        if (!$user || $user->is_active == 1) {
            //0 = >User deleted or not found
            return ['result' => 'success', 'code' => 1, 'user' => '0', 'reservation' => ''];
        }
        $reservation = Reservations::checkStudentHasActiveReservation(student_id: $user->id);
        if (!$reservation) {
            // => Reservation is inACTIVE OR DELETED
            return ['result' => 'success', 'code' => 1, 'user' => '', 'reservation' => '0'];
        }
        return ['result' => 'success', 'code' => 1, 'user' => 'Done', 'reservation' => 'Done'];
    }
}
