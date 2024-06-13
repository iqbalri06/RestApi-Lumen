<?php
namespace App\Http\Controllers;

use App\Models\Lending;
use Illuminate\Http\Request;
use App\Helpers\ApiFormatter;
use App\Models\StuffStock;

class LendingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'logout']]);
    }

    public function index()
    {
        try {
            $getLending = Lending::with('stuff', 'user')->get();

            return ApiFormatter::sendResponse(200, true, 'Successfully Get All Lending Data', $getLending);
        } catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, false, $e->getMessage());
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'stuff_id' => 'required',
                'date_time' => 'required',
                'name' => 'required',
                'user_id' => 'required',
                'notes' => 'required',
                'total_stuff' => 'required',
            ]);

            $createLending = Lending::create([
                'stuff_id' => $request->stuff_id,
                'date_time' => $request->date_time,
                'name' => $request->name,
                'user_id' => $request->user_id,
                'notes' => $request->notes,
                'total_stuff' => $request->total_stuff,
            ]);

            $getStuffStock = StuffStock::where('stuff_id', $request->stuff_id)->first();

            return ApiFormatter::sendResponse(200, true, 'Successfully Create A Lending Data', $createLending);
        } catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, false, $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $getLending = Lending::where('id', $id)->with('stuff', 'user')->first();

            if (!$getLending) {
                return ApiFormatter::sendResponse(404, false, 'Data Lending Not Found');
            } else {
                return ApiFormatter::sendResponse(200, true, 'Successfully Get A Lending Data', $getLending);
            }
        } catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, false, $e->getMessage());
        }
    }

    public function edit(Lending $lending)
    {
        //
    }

    public function update(Request $request, $id)
    {
        try {
            $getLending = Lending::find($id);

            if (!$getLending) {
                return ApiFormatter::sendResponse(404, false, 'Data Lending Not Found');
            }

            $this->validate($request, [
                'stuff_id' => 'required',
                'date_time' => 'required',
                'name' => 'required',
                'user_id' => 'required',
                'notes' => 'required',
                'total_stuff' => 'required',
            ]);

            $getStuffStock = StuffStock::where('stuff_id', $request->stuff_id)->first();
            $getCurrentStock = $getStuffStock; // Corrected variable assignment

            if ($request->stuff_id == $getCurrentStock['stuff_id']) {
                $updateStock =  $getCurrentStock->update([
                    'total_available' => $getCurrentStock['total_available'] + $getLending['total_stuff'] - $request->total_stuff
                ]); 
            } else {
                $updateStockOld = $getCurrentStock->update([
                    'total_available' => $getCurrentStock['total_available'] + $getLending['total_stuff']
                ]); 

                $updateStockNew = $getStuffStock->update([
                    'total_available' => $getStuffStock['total_available'] - $request['total_stuff'],
                ]); 
            }

            $updateLending = $getLending->update([
                'stuff_id' => $request->stuff_id,
                'date_time' => $request->date_time,
                'name' => $request->name,
                'user_id' => $request->user_id,
                'notes' => $request->notes,
                'total_stuff' => $request->total_stuff,
            ]);

            $getUpdateLending = Lending::where('id', $id)->with('stuff', 'user')->first();

            return ApiFormatter::sendResponse(200, true, 'Successfully Update A Lending Data', $getUpdateLending);
        } catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, false, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $lending = Lending::find($id);
            if (!$lending) {
                return ApiFormatter::sendResponse(404, false, 'Data Lending Not Found');
            }

            $lending->delete();
            return ApiFormatter::sendResponse(200, true, 'Successfully Delete A Lending Data');
        } catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, false, $e->getMessage());
        }
    }

    public function deleted()
    {
        try {
            $data = Lending::onlyTrashed()->get();
            return ApiFormatter::sendResponse(200, true, 'Successfully Get Deleted Lending Data', $data);
        } catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, false, $e->getMessage());
        }
    }

    public function restore($id)
    {
        try {
            $lending = Lending::onlyTrashed()->where('id', $id)->first();
            if (!$lending) {
                return ApiFormatter::sendResponse(404, false, 'Data Lending Not Found');
            }

            $lending->restore();
            return ApiFormatter::sendResponse(200, true, 'Successfully Restore Lending Data');
        } catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, false, $e->getMessage());
        }
    }

    public function permanentDelete($id)
    {
        try {
            $lending = Lending::onlyTrashed()->where('id', $id)->first();
            if (!$lending) {
                return ApiFormatter::sendResponse(404, false, 'Data Lending Not Found');
            }

            $lending->forceDelete();
            return ApiFormatter::sendResponse(200, true, 'Successfully Permanently Delete Lending Data');
        } catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, false, $e->getMessage());
        }
    }
}
