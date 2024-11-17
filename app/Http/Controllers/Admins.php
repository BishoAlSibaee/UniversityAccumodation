<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Room;
use App\Models\RoomType;
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
            'is_admin' => 'required|numeric',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $admin = new Admin();
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->mobile = $request->mobile;
        $admin->password = password_hash($request->password, PASSWORD_DEFAULT);
        $admin->is_admin = $request->is_admin;
        $admin->is_active = "0";
        try {
            $admin->save();
            return ['result' => 'success', 'code' => 1, "user" => $admin, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e];
        }
    }
    function updateAdmin(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,' . $request->id,
            'name' => 'required|string',
            'mobile' => 'required|string|min:10|max:10',
            'is_admin' => 'required|numeric',
        ]);

        if ($validation->fails()) {
            return ['result' => 'failed', 'code' => 0, 'error' => $validation->errors()];
        }
        $admin = Admin::find($request->id);

        if (!$admin) {
            return ['result' => 'failed', 'code' => -1, 'error' => 'Admin not found'];
        }
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->mobile = $request->mobile;
        $admin->is_admin = $request->is_admin;
        try {
            $admin->save();
            return ['result' => 'success', 'code' => 1, "user" => $admin, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, 'error' => $e->getMessage()];
        }
    }
    function inActiveAdmin(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|numeric',
        ]);

        if ($validation->fails()) {
            return ['result' => 'failed', 'code' => 0, 'error' => $validation->errors()];
        }
        $admin = Admin::find($request->id);

        if ($admin) {
            $admin->is_active = 1;
            $admin->save();
            return ['result' => 'success', 'code' => 1, "user" => $admin, "error" => ""];
        } else {
            return ['result' => 'failed', 'code' => 0, "user" => "", "error" => "Not Found"];
        }
    }
    function getAllAdmin()
    {
        $admin = Admin::where('is_active', '=', 0)->get();
        if (count($admin) > 0) {
            return ['result' => 'success', 'code' => 1, "admin" => $admin, "error" => ""];
        } else {
            return ['result' => 'success', 'code' => 0, "user" => $admin, "error" => ""];
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
        if ($admin->is_active === 1) {
            return ['result' => 'failed', 'code' => 0, 'error' => "The account has been deleted cannot be logged in", 'token' => ""];
        }
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
            'college_id' => 'numeric|nullable',
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
        if ($request->college_id != null) {
            $user->college_id = $request->college_id;
        }
        $user->password = password_hash($request->student_number, PASSWORD_DEFAULT);
        $user->is_active = 0;
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
    function makeReservation1(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'student_id' => 'nullable|numeric|exists:users,id',
            'room_id' => 'required|numeric|exists:rooms,id',
            'student_name' => 'required|string',
            'room_number' => 'required|string',
            'start_date' => 'required|date',
            'expire_date' => 'required|date',
            'start_time' => 'nullable|string',
            'expire_time' => 'nullable|string',
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
        $reservation->start_time = $request->start_time;
        $reservation->expire_time = $request->expire_time;

        $checkReser = $this->checkReservationByRoom($request->room_id, $request->start_date, $request->expire_date);
        if ($checkReser) {
            try {
                $reservation->save();
                return ['result' => 'success', 'code' => 1, "reservation" => $reservation, "error" => ""];
            } catch (Exception $e) {
                return ['result' => 'failed', 'code' => -1, "error" => $e];
            }
        } else {
            return ['result' => 'failed', 'code' => 0, "reservation" => [], "error" => "Room is not available for reservation"];
        }
    }

    function makeReservation(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'Reservations' => 'required|array',
            'Reservations.*.student_id' => 'nullable|numeric|exists:users,id',
            'Reservations.*.room_id' => 'required|numeric|exists:rooms,id',
            'Reservations.*.student_name' => 'required|string',
            'Reservations.*.room_number' => 'required|numeric',
            'Reservations.*.start_date' => 'required|date',
            'Reservations.*.expire_date' => 'required|date',
            'Reservations.*.start_time' => 'nullable|string',
            'Reservations.*.expire_time' => 'nullable|string',
        ]);

        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $failedReservations = [];
        foreach ($request->Reservations as $reservationData) {
            $isAvailable = $this->checkReservationByRoom($reservationData['room_id'], $reservationData['start_date'], $reservationData['expire_date']);

            if ($isAvailable) {
                try {
                    $reservation = new Reservation();
                    $reservation->student_id = $reservationData['student_id'];
                    $reservation->room_id = $reservationData['room_id'];
                    $reservation->student_name = $reservationData['student_name'];
                    $reservation->room_number = $reservationData['room_number'];
                    $reservation->start_date = $reservationData['start_date'];
                    $reservation->expire_date = $reservationData['expire_date'];
                    $reservation->start_time = $reservationData['start_time'];
                    $reservation->expire_time = $reservationData['expire_time'];
                    $reservation->save();
                } catch (Exception $e) {
                    $failedReservations[] = [
                        'reservation' => $reservationData,
                        'error' => $e->getMessage(),
                    ];
                }
            } else {
                $failedReservations[] = [
                    'reservation' => $reservationData,
                    'error' => 'Room is not available for reservation',
                ];

            }
        }
        return ['result' => 'success', 'failed_reservations' => $failedReservations, 'code' => 1, 'error' => !empty($failedReservations) ? 'Some reservations failed' : ''];
    }

    function checkReservation(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'building_id' => 'required|numeric|exists:buildings,id',
            'floor_id' => 'nullable|numeric',
            'capacity' => 'nullable|numeric',
            'room_type' => 'nullable|numeric',
            'start_date' => 'required|date',
            'expire_date' => 'required|date',
            'type_search' => 'required|numeric|in:1,2,3,4',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $availableRooms = array();
        $roomsQuery = Room::where('building_id', '=', $request->building_id);
        if ($request->filled('floor_id')) {
            $roomsQuery->where('floor_id', '=', $request->floor_id);
        }
        if ($request->filled('capacity')) {
            $roomsQuery->where('capacity', '=', $request->capacity);
        }
        if ($request->filled('room_type')) {
            $roomsQuery->where('room_types_id', '=', $request->room_type);
        }
        $rooms = $roomsQuery->get();
        if (count($rooms) > 0) {
            foreach ($rooms as $room) {
                $checkReservationForRoom = Reservation::where('room_id', '=', $room->id)
                    ->where(function ($query) use ($request) {
                        $query->whereBetween('start_date', [$request->start_date, $request->expire_date])
                            ->orWhereBetween('expire_date', [$request->start_date, $request->expire_date])
                            ->orWhere(function ($query) use ($request) {
                                $query->where('start_date', '<=', $request->start_date)
                                    ->where('expire_date', '>=', $request->expire_date);
                            }, );
                    }, )->get();

                switch ($request->type_search) {
                    case 1: // غرفة فارغة: أول غرفة فارغة ترجع فوراً
                        if ($checkReservationForRoom->isEmpty()) {
                            $roomInfo = $room->toArray();
                            $roomInfo['reservations'] = [];
                            $roomInfo['availableCapacity'] = $room->capacity;
                            return ['result' => 'success', 'code' => 1, "AvailableRooms" => [$roomInfo], "error" => ""];
                        }
                        break;
                    case 2: // غرفة متاحة: أول غرفة متاحة ترجع فوراً
                        if (count($checkReservationForRoom) < $room->capacity) {
                            $roomInfo = $room->toArray();
                            $roomInfo['reservations'] = $checkReservationForRoom;
                            $roomInfo['availableCapacity'] = $room->capacity - count($checkReservationForRoom);
                            return ['result' => 'success', 'code' => 1, "AvailableRooms" => [$roomInfo], "error" => ""];
                        }
                        break;

                    case 3: // جميع الغرف الفارغة
                        if ($checkReservationForRoom->isEmpty()) {
                            $roomInfo = $room->toArray();
                            $roomInfo['reservations'] = [];
                            $roomInfo['availableCapacity'] = $room->capacity;
                            array_push($availableRooms, $roomInfo);
                        }

                        break;
                    case 4: // جميع الغرف المتاحة
                        if ($checkReservationForRoom->count() < $room->capacity) {
                            $roomInfo = $room->toArray();
                            $roomInfo['reservations'] = $checkReservationForRoom;
                            $roomInfo['availableCapacity'] = $room->capacity - count($checkReservationForRoom);
                            array_push($availableRooms, $roomInfo);
                        }
                        break;
                }
            }
            if (count($availableRooms) > 0) {
                return ['result' => 'success', 'code' => 1, "AvailableRooms" => $availableRooms, "error" => ""];
            }
            return ['result' => 'failed', 'code' => 0, "AvailableRooms" => [], "error" => "No available rooms found"];
        } else {
            return ['result' => 'failed', 'code' => 0, "AvailableRooms" => [], "error" => "Rooms Not Found"];
        }
    }
    function checkReservationByRoom($roomId, $startDate, $expireDate): bool
    {
        $check = false;
        $room = Room::find($roomId);
        $checkReservationForRoom = Reservation::where('room_id', $room->id)
            ->where(function ($query) use ($startDate, $expireDate) {
                $query->whereBetween('start_date', [$startDate, $expireDate])
                    ->orWhereBetween('expire_date', [$startDate, $expireDate])
                    ->orWhere(function ($query) use ($startDate, $expireDate) {
                        $query->where('start_date', '<=', $startDate)
                            ->where('expire_date', '>=', $expireDate);
                    });
            })->get();
        if (count($checkReservationForRoom) > 0) {
            if (count($checkReservationForRoom) < $room->capacity) {
                $check = true;
            }
        } else {
            $check = true;
        }
        return $check;
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
            'college_id' => 'nullable|numeric',
        ]);

        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        $user = User::where("is_active", '=', 0)->where($request->by, 'LIKE', '%' . $request->value . '%');
        if ($request->has('college_id')) {
            $user->where("college_id", '=', $request->college_id);
        }

        $users = $user->get();

        if (count($users) > 0) {
            return ['result' => 'success', 'code' => 1, "user" => $users, "error" => ""];
        } else {
            return ['result' => 'failed', 'code' => 0, "user" => [], "error" => "Not Found Student"];
        }
    }
    function updateInfoStudent(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:users,id',
            'email' => 'nullable|email|unique:users,email,' . $request->id,
            'name' => 'nullable|string',
            'mobile' => 'nullable|string|min:10|max:10|unique:users,mobile,' . $request->id,
            'student_number' => 'nullable|string|unique:users,student_number,' . $request->id,
            'nationality' => 'nullable|string',
            'college' => 'nullable|string',
            'study_year' => 'nullable|string',
            'term' => 'nullable|string',
        ]);

        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        $student = User::find($request->id);

        if ($request->has('email') && $request->email !== $student->email) {
            $student->email = $request->email;
        }
        if ($request->has('name') && $request->name !== $student->name) {
            $student->name = $request->name;
        }
        if ($request->has('mobile') && $request->mobile !== $student->mobile) {
            $student->mobile = $request->mobile;
        }
        if ($request->has('student_number') && $request->student_number !== $student->student_number) {
            $student->student_number = $request->student_number;
        }
        if ($request->has('nationality') && $request->nationality !== $student->nationality) {
            $student->nationality = $request->nationality;
        }
        if ($request->has('college') && $request->college !== $student->college) {
            $student->college = $request->college;
        }
        if ($request->has('study_year') && $request->study_year !== $student->study_year) {
            $student->study_year = $request->study_year;
        }
        if ($request->has('term') && $request->term !== $student->term) {
            $student->term = $request->term;
        }

        try {
            $student->save();
            return ['result' => 'success', 'code' => 1, "user" => $student, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e->getMessage()];
        }
    }
    function deleteStudent(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:users,id',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        try {
            $student = User::where('id', $request->id)->update(['is_active' => 1]);
            if ($student) {
                return ['result' => 'success', 'code' => 1, 'error' => ''];
            } else {
                return ['result' => 'failed', 'code' => -1, 'error' => 'Student not found'];
            }
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, 'error' => $e->getMessage()];
        }
    }
    function addCollege(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'college_ar' => 'required|string',
            'college_en' => 'required|string',
        ]);

        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $colleg = new College();
        $colleg->college_ar = $request->college_ar;
        $colleg->college_en = $request->college_en;
        try {
            $colleg->save();
            return ['result' => 'success', 'code' => 1, 'colleg' => $colleg, 'error' => ''];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, 'colleg' => '', 'error' => $e];
        }
    }
    function getAllCollege()
    {
        return College::all();
    }
    function getAllRoomType()
    {
        return RoomType::all();
    }
    function addRoomType(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name_ar' => 'required|string',
            'name_en' => 'required|string',
        ]);

        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $roomType = new RoomType();
        $roomType->name_ar = $request->name_ar;
        $roomType->name_en = $request->name_en;
        try {
            $roomType->save();
            return ['result' => 'success', 'code' => 1, 'Room Type' => $roomType, 'error' => ''];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, 'colleg' => '', 'error' => $e];
        }
    }
}
