<?php

namespace App\Http\Controllers;

use App\models\Stuff;
use App\Models\StuffStock;
use App\Models\InboundStuff;
use Illuminate\Http\Request;
use App\Helpers\ApiFormatter;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class InboundStuffController extends Controller
{
    public function index()
    {
        $inboundStuff = InboundStuff::all();

        return ApiFormatter::sendResponse(200, true, "Lihat semua barang masuk", $inboundStuff);

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Lihat semua barang masuk',
        //     'data' => $inboundStuff
        // ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stuff_id' => 'required',
            'total' => 'required',
            'date' => 'required',
            'proff_file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust file validation as needed
        ]);

        if ($validator->fails()) {

            return ApiFormatter::sendResponse(400, false, "Semua kolom wajib diisi", $validator->errors());
            // return response()->json([
            //     'success' => false,
            //     'message' => 'Semua kolom wajib disi!',
            //     'data' => $validator->errors()
            // ], 400);
        } else {
            $file = $request->file('proff_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(app()->basePath('public/upload-images'), $filename); // Access public directory using app()->basePath('public')

            $result = StuffStock::where('stuff_id', $request->input('stuff_id'))->pluck('total_available')->first();
            $result1 = $result + $request->input('total');

            $stuffStock = StuffStock::where('stuff_id', $request->input('stuff_id'))->update(['total_available' => $result1]);

            $inboundStuff = InboundStuff::create([
                'stuff_id' => $request->input('stuff_id'),
                'total' => $request->input('total'),
                'date' => $request->input('date'),
                'proff_file' => $filename,
            ]);

            // return response()->json([
            //     'success' => true,
            //     'message' => 'Data berhasil disimpan',
            //     'data' => $inboundStuff
            // ], 201);
            return ApiFormatter::sendResponse(201, true, "Data berhasil disimpan", $inboundStuff);
        }

        if ($inboundStuff) {
            // return response()->json([
            //   'success' => true,
            //   'message' => 'Barang berhasil ditambahkan',
            //     'data' => $inboundStuff
            // ],200);
            return ApiFormatter::sendResponse(200, true, "Barang berhasil ditambahkan", $inboundStuff);
        } else{
            // return response()->json([
            //   'success' => false,
            //   'message' => 'Barang gagal ditambahkan',
            // ],400);
            return ApiFormatter::sendResponse(400, false, "Barang gagal ditambahkan", getMessage());
        }   
    }

    public function show($id)
    {
        try {
            $inboundStuff = InboundStuff::findOrFail($id);

            // return response()->json([
            //     'success' => true,
            //     'message' => "Lihat barang masuk dengan id $id",
            //     'data' => $inboundStuff
            // ], 200);
            return ApiFormatter::sendResponse(200, true, "Lihat semua barang masuk dengan id $id", $inboundStuff);
        } catch (\Throwable $th) {
            // return response()->json([
            //     'success' => false,
            //     'message' => "Data barang masuk dengan id $id tidak ditemukan"
            // ], 404);
            return ApiFormatter::sendResponse(404, false, "Data barang masuk dengan id $id tidak ditemukan", $th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $inboundStuff = InboundStuff::with('stuff')->find($id);

            $stuff_id = ($request->stuff_id) ? $request->stuff_id : $inboundStuff->stuff_id;
            $total = ($request->total) ? $request->total : $inboundStuff->total;
            $date = ($request->date) ? $request->date : $inboundStuff->date;
            $proff_file = ($request->proff_file) ? $request->proff_file : $inboundStuff->proff_file;

            if ($request->file('proff_file') !== NULL) {
                $file = $request->file('proff_file');
                $fileName = $stuff_id . '_' . strtotime($date) . strtotime(date('H:i')) . '.' . $file->getClienyOriginalExtension();
                $file->move('proff', $fileName);
            } else {
                $fileName = $inboundStuff->proff_file;
            }

            $total_s = $total - $inboundStuff->total;
            $total_stock = (int)$inboundStuff->stuff->stock->total_available + $total_s;
            $inboundStuff->stuff->stock->update([
                'total_available' => (int)$total_stock
            ]);

            if ($inboundStuff) {
                $inboundStuff->update([
                    'stuff_id' => $stuff_id,
                    'total' => $total,
                    'date' => $date,
                    'proff_file' => $proff_file,
                ]);

                // return response()->json([
                //     'success' => true,
                //     'message' => "Berhasil mengubah data inboundStuff dengan id $id",
                //     'data' => $inboundStuff,
                // ], 200);
                return ApiFormatter::sendResponse(200, true, "Berhasil mengubah data inboundStuff dengan id $id", $inboundStuff);
            } else {
                // return response()->json([
                //     'success' => false,
                //     'message' => "Proses Gagal!"
                // ], 404);
                return ApiFormatter::sendResponse(404, false, "Proses gagal!", getMessage());
            }
        } catch (\Throwable $th) {
            // return response()->json([
            //     'success' => false,
            //     'message' => "Proses Gagal! data inboundStuff dengan id $id tidak ditemukan!",
            // ], 404);
            return ApiFormatter::sendResponse(404, false, "Proses gagal! data inboundStuff dengan id $id tidak ditemukan!", $th->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $inboundStuff = InboundStuff::findOrFail($id);
            $stock = StuffStock::where('stuff_id', $inboundStuff->stuff_id)->first();

            if ($stock) {
                $available_min = $stock->total_available - $inboundStuff->total;
                $available = ($available_min < 0) ? 0 : $available_min;
                $defec = ($available_min < 0) ? $stock->total_defec + abs($available_min) : $stock->total_defec;

                $stock->update([
                    'total_available' => $available,
                    'total_defec' => $defec
                ]);
            }

            $inboundStuff->delete();
            return ApiFormatter::sendResponse(200, true, "Barang masuk berhasil dihapus dengan id $id", ['id' => $id]);
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses gagal! Data barang masuk dengan id $id tidak ditemukan!", $th->getMessage());
        }
    }

    public function deleted()
    {
        try {
            $inboundStuff = InboundStuff::onlyTrashed()->get();

            return ApiFormatter::sendResponse(200, true, "Lihat data barang yang dihapus", $inboundStuff);
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses gagal! silahkan coba lagi!", $th->getMessage());
        }
    }

    public function restore($id)
    {
        try {
            $inboundStuff = InboundStuff::onlyTrashed()->findOrFail($id);
            $has_inbound = InboundStuff::where('stuff_id', $inboundStuff->stuff_id)->get();

            if ($has_inbound->count() == 1) {
                $message = "data stock sudah ada, tidak boleh ada duplikat data stock untuk satu barang silahkan update data stok dengan id stock $inboundStuff->stuff_id";
            } else {
                $inboundStuff->restore();
                $message = "berhasil mengembalikkan data yang yang telah di hapus";
            }

            return ApiFormatter::sendResponse(200, true, $message, ['id' => $id, 'stuff_id' => $inboundStuff->stuff_id]);
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses gagal! silahkan coba lagi!", $th->getMessage());
        }
    }

    public function restoreAll()
    {
        try {
            $inboundStuff = InboundStuff::onlyTrashed();
            
            $inboundStuff->restore();

            return ApiFormatter::sendResponse(200, true, "Berhasil mengembalikan data yang telah di hapus!");
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses gagal! silahkan coba lagi!", $th->getMessage());
        }
    }

    public function permanentDelete($id)
    {
        try {
            $InboundStuff = InboundStuff::onlyTrashed()->where('id', $id)->forceDelete();

            return ApiFormatter::sendResponse(200, true, "Berhasil hapus permanen data yang telah dihapus!", ['id' => $id]);
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses gagal! silahkan coba lagi!", $th->getMessage());
        }
    }

    public function permanentDeleteAll()
    {
        try {
            $inboundStuff = InboundStuff::onlyTrashed();
            
            $inboundStuff->forceDelete();

            return ApiFormatter::sendResponse(200, true, "Berhasil hapus permanen semua data yang telah dihapus!");
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses gagal! silahkan coba lagi!", $th->getMessage());
        }
    }

    public function __construct()
    {
        $this->middleware('auth:api');
    }
}