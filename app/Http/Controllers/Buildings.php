<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Suite;
use App\Models\Room;
use App\Messages;
use Exception;
use Illuminate\Support\Facades\DB;


class Buildings extends Controller
{
    //=====================================ADD===============================================
    function addBuilding(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'number' => 'required|numeric|unique:buildings,number',
            'name' => 'required|string|unique:buildings,name',
            'lock_id' => 'nullable|string|unique:buildings,lock_id',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        $building = new Building();
        $building->number = $request->number;
        $building->name = $request->name;
        if ($request->has('lock_id')) {
            $building->lock_id = $request->lock_id;
        }

        try {
            $building->save();
            return ['result' => 'success', 'code' => 1, 'building' => $building, 'error' => ''];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, 'building' => '', 'error' => $e];
        }

    }

    function addFloor(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'building_id' => 'required|numeric|exists:buildings,id',
            'number' => 'required|numeric',
            'lock_id' => 'nullable|string|unique:floors,lock_id',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        $building = Building::find($request->building_id);
        $building->floors();
        foreach ($building->floors as $f) {
            if ($f->number == $request->number) {
                return ['result' => 'failed', 'code' => -1, 'floor' => '', 'error' => Messages::getMessage('floorNumberExistsInBuilding')];
            }
        }

        $floor = new Floor();
        $floor->building_id = $building->id;
        $floor->number = $request->number;
        if ($request->has('lock_id')) {
            $floor->lock_id = $request->lock_id;
        }
        try {
            $floor->save();
            return ['result' => 'success', 'code' => 1, 'floor' => $floor, 'error' => ''];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, 'floor' => '', 'error' => $e];
        }

    }

    function addSuite(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'building_id' => 'required|numeric|exists:buildings,id',
            'floor_id' => 'required|numeric|exists:floors,id',
            'number' => 'required|numeric',
            'lock_id' => 'nullable|string|unique:suites,lock_id',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        // $floor = Floor::find($request->floor_id);
        // $floor->suites();
        // foreach ($floor->suites as $s) {
        //     if ($s->number == $request->number) {
        //         return ['result' => 'failed', 'code' => -1, 'suite' => '', 'error' => Messages::getMessage('suiteNumberExistsInFloor')];
        //     }
        // }

        // $checkSuite = Suite::where('floor_id', $request->floor_id)->where('number', $request->number)->first();
        // if ($checkSuite) {
        //     return ['result' => 'failed', 'code' => -1, 'suite' => '', 'error' => Messages::getMessage('suiteNumberExistsInFloor')];
        // }

        //الجناح ما بيتكرر بنفس المبنى بولكن بيتكرر بمبنى تاني

        if ($this->checkSuiteInBuilding($request->building_id, $request->number)) {
            return ['result' => 'failed', 'code' => -1, 'suite' => '', 'error' => 'The suite is already exists.'];
        }

        $suite = new Suite();
        $suite->building_id = $request->building_id;
        $suite->floor_id = $request->floor_id;
        $suite->number = $request->number;
        if ($request->has('lock_id')) {
            $suite->lock_id = $request->lock_id;
        }

        try {
            $suite->save();
            return ['result' => 'success', 'code' => 1, 'suite' => $suite, 'error' => ''];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, 'suite' => '', 'error' => $e];
        }

    }

    function addRoom(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'building_id' => 'required|numeric|exists:buildings,id',
            'floor_id' => 'required|numeric|exists:floors,id',
            'suite_id' => 'nullable|numeric',
            'number' => 'required|string',
            'type_room' => 'required|string',
            'capacity' => 'numeric|nullable',
            'lock_id' => 'nullable|string|unique:rooms,lock_id',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        // $suite = Suite::find($request->suite_id);
        // if ($request->suite_id != 0) {
        //     $suite->rooms();
        //     foreach ($suite->rooms() as $r) {
        //         if ($r->number == $request->number) {
        //             return ['result' => 'failed', 'code' => -1, 'suite' => '', 'error' => Messages::getMessage('roomNumberExistsInSuite')];
        //         }
        //     }
        // }

        if ($this->checkRoomInBuilding($request->building_id, $request->number)) {
            return ['result' => 'failed', 'code' => -1, 'suite' => '', 'error' => 'The room is already exists.'];
        }
        $room = new Room();
        $room->building_id = $request->building_id;
        $room->floor_id = $request->floor_id;
        $room->suite_id = $request->suite_id;
        $room->number = $request->number;
        $room->type_room = $request->type_room;
        if ($request->capacity != null) {
            $room->capacity = $request->capacity;
        }
        if ($request->has('lock_id')) {
            $room->lock_id = $request->lock_id;
        }
        try {
            $room->save();
            return ['result' => 'success', 'code' => 1, 'room' => $room, 'error' => ''];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, 'room' => '', 'error' => $e];
        }

    }

    //=====================================GET===============================================

    function getBuildings()
    {
        return Building::all();
    }

    function getFloors()
    {
        return Floor::all();
    }

    function getSuites()
    {
        return Suite::all();
    }

    function getRooms()
    {
        return Room::all();
    }

    function getById1(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => "required|numeric|exists:buildings,id",
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        $building = Building::find($request->id);
        return ['result' => 'success', 'code' => 1, 'data' => $building, "error" => ""];

    }

    function getDataById(Request $request)
    {
        $allModels = [
            'building' => Building::class,
            'floor' => Floor::class,
            'suite' => Suite::class,
            'room' => Room::class,
        ];
        $validation = Validator::make($request->all(), [
            'id' => 'required|numeric',
            'model' => 'required|string|in:' . implode(',', array_keys($allModels)),
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 400);
        }
        $data = $allModels[$request->model]::find($request->id);
        if (!$data) {
            return response(['result' => 'failed', 'code' => 0, 'error' => 'Data not found'], 404);
        }
        return response(['result' => 'success', 'code' => 1, 'data' => $data], 200);
    }

    function getBuildingData1(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:buildings,id',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 400);
        }
        $building = Building::find($request->id);
        $fff = $building->floors;
        $rooms = Room::where('building_id', $request->id)->where('suite_id', 0)->get();
        foreach ($fff as $f) {
            $sss = $f->suites;
            foreach ($sss as $s) {
                $s->rooms;
            }
        }

        return response(['result' => 'success', 'code' => 1, 'data' => $building, 'rooms' => $rooms], 200);
    }

    function getBuildingData(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:buildings,id',
        ]);

        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 400);
        }
        $building = Building::with(['floors.suites.rooms'])->find($request->id);
        $rooms = Room::where('building_id', $request->id)->where('suite_id', 0)->get();
        return response(['result' => 'success', 'code' => 1, 'data' => $building, 'rooms' => $rooms], 200);
    }
    //=====================================DELETE===============================================

    function deleteRoom(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => "required|numeric|exists:rooms,id",
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        try {
            Room::find($request->id)->delete();
            return ['result' => 'success', 'code' => 1, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e];
        }
    }

    function deleteSuite(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id_building' => "required|numeric|exists:buildings,id",
            'id_suite' => "required|numeric|exists:suites,id",
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $suite = Suite::where('building_id', $request->id_building)->where('id', $request->id_suite)->first();
        if (!$suite) {
            return response(['result' => 'failed', 'code' => 0, 'error' => 'Suite not found in the specified building.'], 200);
        }
        DB::beginTransaction();

        try {
            foreach ($suite->rooms as $room) {
                $room->delete();
            }
            $suite->delete();
            DB::commit();
            return ['result' => 'success', 'code' => 1, "error" => ""];

        } catch (Exception $e) {
            DB::rollBack();
            return ['result' => 'failed', 'code' => -1, "error" => $e];
        }
    }

    function deleteFloor(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id_building' => "required|numeric|exists:buildings,id",
            'id_floor' => "required|numeric|exists:floors,id",
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $floor = Floor::where('building_id', $request->id_building)->where('id', $request->id_floor)->first();
        if (!$floor) {
            return response(['result' => 'failed', 'code' => 0, 'error' => 'Floor not found in the specified building.'], 200);
        }
        DB::beginTransaction();
        try {
            Room::where('floor_id', $request->id_floor)->delete();
            foreach ($floor->suites as $suite) {
                $suite->rooms()->delete();
                $suite->delete();
            }
            $floor->delete();
            DB::commit();
            return ['result' => 'success', 'code' => 1, "error" => ""];

        } catch (Exception $e) {
            DB::rollBack();
            return ['result' => 'failed', 'code' => -1, "error" => $e];
        }
    }

    function deleteBuilding(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id_building' => "required|numeric|exists:buildings,id",
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $building = Building::find($request->id_building);

        $singleRoom = Room::where('building_id', $request->id_building)->where('suite_id', 0)->get();
        DB::beginTransaction();
        try {
            foreach ($singleRoom as $room) {
                $room->delete();
            }

            foreach ($building->floors as $floor) {
                foreach ($floor->suites as $suite) {
                    foreach ($suite->rooms as $room) {
                        $room->delete();
                    }
                    $suite->delete();
                }
                $floor->delete();
            }
            $building->delete();

            DB::commit();
            return ['result' => 'success', 'code' => 1, "error" => ""];

        } catch (Exception $e) {
            DB::rollBack();
            return ['result' => 'failed', 'code' => -1, "error" => $e->getMessage()];
        }
    }

    //=====================================Update===============================================

    function updateRoom(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:rooms,id',
            'number' => 'nullable|string',
            'type_room' => 'nullable|string',
            'capacity' => 'nullable|numeric',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $room = Room::find($request->id);

        if ($request->has('number')) {
            if ($this->checkRoomInBuilding($room->building_id, $request->number)) {
                return ['result' => 'failed', 'code' => -1, 'suite' => '', 'error' => 'The room is already exists.'];
            } else {
                $room->number = $request->number;
            }
        }
        if ($request->has('type_room')) {
            $room->type_room = $request->type_room;
        }
        if ($request->has('capacity')) {
            $room->capacity = $request->capacity;
        }
        try {
            $room->update();
            return ['result' => 'success', 'code' => 1, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e->getMessage()];
        }
    }

    function updateSuite(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:suites,id',
            'number' => 'required|string',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $suite = Suite::find($request->id);

        if ($suite->number === $request->number && $this->checkSuiteInBuilding($suite->building_id, $request->number)) {
            return ['result' => 'failed', 'code' => -1, 'suite' => '', 'error' => 'The suite is already exists.'];
        }

        $suite->number = $request->number;
        try {
            $suite->update();
            return ['result' => 'success', 'code' => 1, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e->getMessage()];
        }
    }

    function updateFloor(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:floors,id',
            'number' => 'required|string',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $floor = Floor::find($request->id);

        if ($this->checkFloorInBuilding($floor->building_id, $request->number)) {
            return ['result' => 'failed', 'code' => -1, 'suite' => '', 'error' => 'The floor is already exists.'];
        }

        $floor->number = $request->number;
        try {
            $floor->update();
            return ['result' => 'success', 'code' => 1, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e->getMessage()];
        }
    }

    function updateBuilding(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:buildings,id',
            'number' => 'required|string',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }

        $building = Building::find($request->id);
    }

    function checkRoomInBuilding($building_id, $number)
    {
        $CheckRoom = Room::where('building_id', $building_id)->where('number', $number)->exists();
        if ($CheckRoom) {
            return true;
        }
        return false;
    }

    function checkSuiteInBuilding($building_id, $number)
    {
        $CheckSuite = Suite::where('building_id', $building_id)->where('number', $number)->exists();
        if ($CheckSuite) {
            return true;
        }
        return false;
    }

    function checkFloorInBuilding($building_id, $number)
    {
        $floors = Floor::where('building_id', $building_id)->where('number', $number)->exists();
        if ($floors) {
            return true;
        }
    }
}
