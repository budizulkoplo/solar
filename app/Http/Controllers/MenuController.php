<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class MenuController extends Controller
{
    public function index(Request $request): view
    {
        return view('master.menu.list', [
            'user' => $request->user(),
            'roles' =>  Role::with('permissions')->get(),
        ]);
    }
    public function datamenu($role){
        $menu=array();
        $data = Menu::orderBy('seq')->get();
        
        foreach ( $data as $value) {
            $cekparent = Menu::where('parent_id',$value->id)->orderBy('seq')->count();
            $level=explode(';', $value->role);
            $hd=array(
                'text'=>$value->name,
                'id'=>$value->id,
                'icon'=>$value->icon,
                'parent'=>empty($value->parent_id)?'#':$value->parent_id,
                'state'=>
                array(
                    'opened'=>true,
                    'role'=>(empty($value->parent_id)?true:false),
                    'disabled'=>(empty($value->parent_id) && $cekparent>0 ? true : false),
                    //'disabled'=>false,
                    'selected'=> in_array($role, $level)?true:false,
                    )
                );
            array_push( $menu,$hd);
            //return response()->json($level);
        }
        return response()->json($menu); 
    }
    public function update(Request $request){
        // $cekparent = Menu::where('deleted',0)
        // ->where('id',$request->id)
        // ->first();
        
            $data = Menu::find($request->id);
            $role=explode(';', $data->role);
            $role = array_filter($role);
            $cari=array_search($request->gp,$role);
           
            if($request->aktif == 'true'){
                array_push($role,$request->gp); 
            }else{
                unset($role[$cari]);
            }
            $hasil=implode(";",$role).';';
            
            $ubah = Menu::find($request->id);
            $ubah->role = ';'.$hasil;
            $ubah->save();
            return response()->json($ubah); 
        
    }
}