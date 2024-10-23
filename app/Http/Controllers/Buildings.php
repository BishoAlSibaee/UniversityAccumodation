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
            'numberOfFloor' => 'required|numeric',
            'numberFloor' => 'required|numeric',
            'lock_id' => 'nullable|string|unique:buildings,lock_id',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        DB::beginTransaction();

        try {
            $building = new Building();
            $building->number = $request->number;
            $building->name = $request->name;
            if ($request->has('lock_id')) {
                $building->lock_id = $request->lock_id;
            }
            $building->save();
            $addFloor = $this->addMultiFloor($building->id, $request->numberFloor, $request->numberOfFloor);
            if (count($addFloor) > 0) {
                DB::commit();
                return ['result' => 'success', 'code' => 1, 'building' => $building, 'floors' => $addFloor, 'error' => ''];
            } else {
                DB::rollBack();
                return ['result' => 'failed', 'code' => -1, 'building' => '', 'error' => 'Failed to add floors'];
            }

        } catch (Exception $e) {
            DB::rollBack();
            return ['result' => 'failed', 'code' => -1, 'building' => '', 'error' => $e->getMessage()];
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
            'room_ids' => 'required',
            'lock_id' => 'nullable|string|unique:suites,lock_id',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        if ($this->checkSuiteInBuilding($request->building_id, $request->number)) {
            return ['result' => 'failed', 'code' => -1, 'suite' => '', 'error' => 'The suite already exists.'];
        }
        DB::beginTransaction();
        try {
            $suite = new Suite();
            $suite->building_id = $request->building_id;
            $suite->floor_id = $request->floor_id;
            $suite->number = $request->number;
            if ($request->has('lock_id')) {
                $suite->lock_id = $request->lock_id;
            }
            $suite->save();
            $Ids = explode("-", $request->room_ids);
            Room::whereIn('id', $Ids)->update(['suite_id' => $suite->id]);
            $rooms = Room::whereIn('id', $Ids)->get();
            DB::commit();
            return ['result' => 'success', 'code' => 1, 'suite' => $suite, 'rooms' => $rooms, 'error' => ''];
        } catch (Exception $e) {
            DB::rollBack();
            return ['result' => 'failed', 'code' => -1, 'suite' => '', 'error' => $e->getMessage()];
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
            'capacity' => 'required|numeric',
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
        $room->capacity = $request->capacity;
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

    function addMultiFloor($building_id, $number, $numberOfFloor)
    {
        $building = Building::find($building_id);
        $existingFloor = $building->floors()->where('building_id', '=', $building_id)->where('number', '>=', $number)->where('number', '<', $number + $numberOfFloor)->exists();
        if ($existingFloor) {
            return [];
        }
        $floors = [];
        for ($i = 0; $i < $numberOfFloor; $i++) {
            $newFloor = new Floor();
            $newFloor->building_id = $building_id;
            $newFloor->number = $number + $i;
            $newFloor->save();
            $floors[] = $newFloor;
        }
        return $floors;
    }
    function addMultiRoom(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'buildingId' => 'required|numeric|exists:buildings,id',
            'floorId' => 'required|numeric|exists:floors,id',
            'numberOfRoom' => 'required|string',
            'numberRoom' => 'required|string',
            'typeRoom' => 'required|string',
            'capacity' => 'required|numeric',
        ]);
        if ($validation->fails()) {
            return ['result' => 'failed', 'code' => 0, 'error' => $validation->errors()];
        }
        try {
            $floor = Floor::where('id', $request->floorId)->where('building_id', $request->buildingId)->first();
            if (!$floor) {
                return ['result' => 'failed', 'code' => -1, 'error' => 'Floor does not belong to this building'];
            }
            $existingRoom = Room::where('building_id', $request->buildingId)->where('number', '>=', $request->numberRoom)->where('number', '<', $request->numberRoom + $request->numberOfRoom)->exists();
            if ($existingRoom) {
                return ['result' => 'failed', 'code' => -1, 'error' => 'Room numbers already exist in this building'];
            }

            $rooms = [];
            for ($i = 0; $i < $request->numberOfRoom; $i++) {
                $newRoom = new Room();
                $newRoom->building_id = $request->buildingId;
                $newRoom->floor_id = $request->floorId;
                $newRoom->number = $request->numberRoom + $i;
                $newRoom->capacity = $request->capacity;
                $newRoom->type_room = $request->typeRoom;
                $newRoom->suite_id = 0;
                $newRoom->save();
                $rooms[] = $newRoom;
            }
            return ['result' => 'success', 'code' => 1, 'rooms' => $rooms, 'error' => ''];

        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e];
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

    function getBuildingData(Request $request)
    {
        // $validation = Validator::make($request->all(), [
        //     'id' => 'required|numeric|exists:buildings,id',
        // ]);

        // if ($validation->fails()) {
        //     return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 400);
        // }
        $building = Building::with(['floors.suites.rooms'])->get();
        $rooms = Room::where('suite_id', 0)->get();
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

        if ($request->has('number') && $request->number != $room->number) {
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
            'number' => 'nullable|string',
            'room_ids_to_add' => 'nullable|string',
            'room_ids_to_remove' => 'nullable|string',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $suite = Suite::find($request->id);
        if ($request->has('number') && $request->number !== null) {
            if ($suite->number !== $request->number && $this->checkSuiteInBuilding($suite->building_id, $request->number)) {
                return ['result' => 'failed', 'code' => -1, 'suite' => '', 'error' => 'The suite already exists.'];
            }
            $suite->number = $request->number;
            $suite->update();
        }
        try {

            if ($request->has('room_ids_to_add') && $request->room_ids_to_add !== null) {
                $roomIdsToAdd = explode('-', $request->room_ids_to_add);
                Room::whereIn('id', $roomIdsToAdd)->update(['suite_id' => $suite->id]);
            }

            if ($request->has('room_ids_to_remove') && $request->room_ids_to_remove !== null) {
                $roomIdsToRemove = explode('-', $request->room_ids_to_remove);
                Room::whereIn('id', $roomIdsToRemove)->update(['suite_id' => 0]);
            }
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

    function updateBuilding1(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:buildings,id',
            'name' => 'required|string',
            'number' => 'required|string',
        ]);
        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        //كمل هون وتأكد بكرا مشان موضوع التكرار
        $building = Building::find($request->id);
        if ($this->checkBuliding($request->name, $request->number)) {
            return ['result' => 'failed', 'code' => -1, 'building' => '', 'error' => 'The building is already exists.'];
        }
        $building->name = $request->name;
        $building->number = $request->number;
        try {
            $building->update();
            return ['result' => 'success', 'code' => 1, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e->getMessage()];
        }

    }

    function updateBuilding(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:buildings,id',
            'name' => 'required|string',
            'number' => 'required|string',
        ]);

        if ($validation->fails()) {
            return response(['result' => 'failed', 'code' => 0, 'error' => $validation->errors()], 200);
        }
        $checkName = Building::where('name', $request->name)->where('id', '!=', $request->id)->exists();
        $checkNumber = Building::where('number', $request->number)->where('id', '!=', $request->id)->exists();
        if ($checkName || $checkNumber) {
            return ['result' => 'failed', 'code' => -1, 'building' => '', 'error' => 'The building name or number already exists.'];
        }

        $building = Building::find($request->id);
        $building->name = $request->name;
        $building->number = $request->number;
        try {
            $building->update();
            return ['result' => 'success', 'code' => 1, 'building' => $building, "error" => ""];
        } catch (Exception $e) {
            return ['result' => 'failed', 'code' => -1, "error" => $e->getMessage()];
        }
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

    function checkBuliding($name, $number)
    {
        $building = Building::where('name', $name)->where('number', $number)->exists();
        if ($building) {
            return true;
        }
    }
}
