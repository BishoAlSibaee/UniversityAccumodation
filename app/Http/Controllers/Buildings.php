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

class Buildings extends Controller
{
    function addBuilding(Request $request) {
        $validation = Validator::make($request->all(), [
            'number' => 'required|numeric|unique:buildings,number',
            'name' => 'required|string',
        ]);
        if ($validation->fails()) {
            return response(['result'=>'failed','code'=>0,'error'=>$validation->errors()],200);
        }

        $building = new Building();
        $building->number = $request->number;
        $building->name = $request->name;

        try {
            $building->save();
            return ['result' =>'success','code' =>1,'building'=>$building,'error'=>''];
        }
        catch (Exception $e) {
            return ['result' =>'failed','code' =>-1,'building'=>'','error'=>$e];
        }

    }

    function addFloor(Request $request) {
        $validation = Validator::make($request->all(), [
            'building_id' => 'required|numeric|exists:buildings,id',
            'number' => 'required|numeric',
        ]);
        if ($validation->fails()) {
            return response(['result'=>'failed','code'=>0,'error'=>$validation->errors()],200);
        }

        $building = Building::find($request->building_id);
        $building->floors();
        foreach ($building->floors() as $f) {
            if ($f->number == $request->number)  {
                return ['result' =>'failed','code' =>-1,'floor'=>'','error'=>Messages::getMessage('floorNumberExistsInBuilding')];
            }
        }
        $floor = new Floor();
        $floor->building_id = $building->id;
        $floor->number = $request->number;

        try {
            $floor->save();
            return ['result' =>'success','code' =>1,'floor'=>$floor,'error'=>''];
        }
        catch (Exception $e) {
            return ['result' =>'failed','code' =>-1,'floor'=>'','error'=>$e];
        }

    }

    function addSuite(Request $request) {
        $validation = Validator::make($request->all(), [
            'building_id' => 'required|numeric|exists:buildings,id',
            'floor_id' => 'required|numeric|exists:floors,id',
            'number' => 'required|numeric',
        ]);
        if ($validation->fails()) {
            return response(['result'=>'failed','code'=>0,'error'=>$validation->errors()],200);
        }

        $floor = Floor::find($request->floor_id);
        $floor->suites();
        foreach ($floor->suites() as $s) {
            if ($s->number == $request->number)  {
                return ['result' =>'failed','code' =>-1,'suite'=>'','error'=>Messages::getMessage('suiteNumberExistsInFloor')];
            }
        }
        $suite = new Suite();
        $suite->building_id = $request->building_id;
        $suite->floor_id = $request->floor_id;
        $suite->number = $request->number;

        try {
            $suite->save();
            return ['result' =>'success','code' =>1,'suite'=>$suite,'error'=>''];
        }
        catch (Exception $e) {
            return ['result' =>'failed','code' =>-1,'suite'=>'','error'=>$e];
        }

    }

    function addRoom(Request $request) {
        $validation = Validator::make($request->all(), [
            'building_id' => 'required|numeric|exists:buildings,id',
            'floor_id' => 'required|numeric|exists:floors,id',
            'suite_id' => 'required|numeric|exists:suites,id',
            'number' => 'required|numeric',
            'capacity' => 'numeric|nullable'
        ]);
        if ($validation->fails()) {
            return response(['result'=>'failed','code'=>0,'error'=>$validation->errors()],200);
        }

        $suite = Suite::find($request->suite_id);
        $suite->rooms();
        foreach ($suite->rooms() as $r) {
            if ($r->number == $request->number)  {
                return ['result' =>'failed','code' =>-1,'suite'=>'','error'=>Messages::getMessage('roomNumberExistsInSuite')];
            }
        }
        $room = new Room();
        $room->building_id = $request->building_id;
        $room->floor_id = $request->floor_id;
        $room->suite_id = $request->suite_id;
        $room->number = $request->number;
        if ($request->capacity != null) {
            $room->capacity = $request->capacity;
        }
        try {
            $room->save();
            return ['result' =>'success','code' =>1,'room'=>$room,'error'=>''];
        }
        catch (Exception $e) {
            return ['result' =>'failed','code' =>-1,'room'=>'','error'=>$e];
        }

    }

    function getBuildings() {
        return Building::all();
    }

    function getFloors() {
        return Floor::all();
    }

    function getSuites() {
        return Suite::all();
    }

    function getRooms() {
        return Room::all();
    }
}
