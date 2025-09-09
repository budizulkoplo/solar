<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(Request $request): View
    {
        return view('master.unit.list', [
            'units' => Unit::all(),
        ]);
    }
    public function AddForm(Request $request): View
    {
        return view('master.unit.form', [
            'unit' => Unit::all(),
            'isEdit' => false
        ]);
    }
    public function EditForm(Request $request,$id): View
    {
        $code=Crypt::decryptString($id);
        $data = Unit::find($code);
        return view('master.unit.form', [
            'units' => Unit::all(),
            'code'  => $code,
            'unit' => $data,
            'isEdit'  => true
        ]);
    }
    public function Hapus($id)
    {
        $id=Crypt::decryptString($id);
        $unit = Unit::find($id);
        $unit->delete();
        if($unit){
            return redirect()->route('unit.list')->with('success', " $unit->nama_unit Berhasil dihapus");
        }else{
            return redirect()->route('unit.list')->with('error', "Gagal");
        }
    }
    public function Store(Request $request,$id=null)
    {
        $validatedData = $request->validate([
            'nama_unit' => 'required',
            'jenis' => 'required',
        ]);
        if($validatedData){
            if(!empty($id)){
                $id=Crypt::decryptString($id);
                $unit = Unit::find($id);
            }else{
                $unit = new Unit;
            }
            $unit->nama_unit = $request->nama_unit;
            $unit->jenis = $request->jenis;
            $unit->save();
            if($unit){
                if(empty($id)){
                    return redirect()->route('unit.list')->with('success', "Unit $request->nama_unit berhasil ditambahkan");
                }else{
                    return redirect()->route('unit.edit',['id' => Crypt::encryptString($id)])->with('success', "Unit $request->nama_unit, berhasih diubah");
                }
            }
        }
    }
}
