<?php

namespace App\Http\Controllers;

use App\Http\Resources\resDbaju;
use App\Http\Resources\resourcesSort;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class userController extends Controller
{
    public function addCart(Request $req){
        if (Auth::check()){
            $cart = Session::get('cart');
            if(isset($cart[Auth::user()->nama_user])){
                if(isset($cart[Auth::user()->nama_user][$req->idDbaju])){
                    $cart[Auth::user()->nama_user][$req->idDbaju]['qty'] += 1;
                }else{
                    $cart[Auth::user()->nama_user][$req->idDbaju] = array('id_dbaju' => $req->idDbaju,
                                                                          'id_hbaju' => $req->idHbaju,
                                                                          'qty' => 1);
                }
            }else{
                $cart[Auth::user()->nama_user][$req->idDbaju] = array('id_dbaju' => $req->idDbaju,
                                                                          'id_hbaju' => $req->idHbaju,
                                                                          'qty' => 1);
            }
            Session::put('cart', $cart);
        }

    }
    public function shop(){
        $barang["Hbaju"] = DB::table('h_baju')->get();
        $barang["baju"] = DB::table('d_baju')->get();
        return view("shop")->with($barang);
    }
    public function dbaju(Request $req){
        $dBarang = DB::table('d_baju as d')
        ->where('d.ID_HBAJU','=',$req->hbaju)
        ->get();
        return resDbaju::collection($dBarang);
    }
    public function home(){
        $barang["newArrival"] = DB::table('h_baju as h')
        ->join('d_baju as d', 'h.ID_HBAJU', '=', 'd.ID_HBAJU')
        ->select('d.ID_DBAJU as ID_DBAJU', 'h.gambar as gambar', 'h.harga as harga', 'd.NAMA_BAJU as nama', 'd.ukuran as ukuran', 'd.warna as warna')
        ->orderBy('h.time_added', 'desc')
        ->get();
        return view("Home")->with($barang);
    }
    public function shopSort(Request $req){
        if($req->btnSort == 'tertinggi'){
            $barang['Hbaju'] = DB::table('h_baju')
            ->orderBy('harga', 'desc')
            ->get();
        }else if($req->btnSort == 'terendah'){
            $barang['Hbaju'] = DB::table('h_baju')
            ->orderBy('harga', 'asc')
            ->get();
        }else if($req->btnSort == 'terbaru'){
            $barang['Hbaju'] = DB::table('h_baju')
            ->orderBy('time_added', 'desc')
            ->get();
        }else if($req->btnSort == 'terlama'){
            $barang['Hbaju'] = DB::table('h_baju')
            ->orderBy('time_added', 'asc')
            ->get();
        }else{
            return redirect("shop");
        }
        $barang["baju"] = DB::table('d_baju')->get();
        return view("shop")->with($barang);
    }
    public function shopCategory(Request $req, $kategori){
        if($req->btnKategori){
            $idHbaju = array();
            $barang['baju'] = DB::table('d_baju')->where('ID_KATEGORI',$req->btnKategori)->get();
            $barang['kategori'] = $kategori;
            foreach ($barang['baju'] as $key => $value) {
                array_push($idHbaju, $value->ID_HBAJU);
            }
            $barang["Hbaju"] = DB::table('h_baju')->whereIn('ID_HBAJU',$idHbaju)->get();
            return view('shop')->with($barang);
        }else{
            return redirect("/shop");
        }
    }

    public function detail(Request $req, $namaHbaju){
        $check = DB::table('h_baju')->where('NAMA_BAJU',$namaHbaju)->count();
        $idHbaju = "";
        if(isset($req->btnDetail) || $check == 1){
            $idHbaju = $req->btnDetail;
            if(isset($req->btnDetail)){
                $idHbaju = $req->btnDetail;
            }else{
                $idHbaju = DB::table('h_baju')->where('NAMA_BAJU',$namaHbaju)->pluck('ID_HBAJU');
            }
            $Dbaju = DB::table('d_baju')->where('ID_HBAJU',$idHbaju)->first();
            return redirect('/detail/'.$namaHbaju.'/'.$Dbaju->id_dbaju);
        }else{
            return redirect('shop');
        }
    }
    public function detailItem(Request $req, $namaHbaju, $idDbaju){
        $check = DB::table('h_baju')->where('NAMA_BAJU',$namaHbaju)->count();
        $checkDbaju = DB::table('d_baju')->where('id_dbaju',$idDbaju)->count();
        if($check == 1 && $checkDbaju == 1){
            $idHbaju = DB::table('h_baju')->where('NAMA_BAJU',$namaHbaju)->pluck('ID_HBAJU');
            $barang['Dbaju'] = DB::table('d_baju')->where('id_dbaju',$idDbaju)->get();
            $barang['Hbaju'] = DB::table('h_baju')->where('ID_HBAJU', $idHbaju)->get();
            return view('detail');
        }else{
            return redirect('shop');
        }
    }

    public function shopCategorySort(Request $req, $kategori){
        $idKategori = DB::table('kategori')->where('NAMA_KATEGORI',$kategori)->pluck('ID_KATEGORI');
        $idHbaju = array();
        $barang['baju'] = DB::table('d_baju')->where('ID_KATEGORI',$idKategori[0])->get();
        $barang['kategori'] = $kategori;

        if($req->btnSort == 'tertinggi'){
            foreach ($barang['baju'] as $key => $value) {
                array_push($idHbaju, $value->ID_HBAJU);
            }
            $barang["Hbaju"] = DB::table('h_baju')
                                ->whereIn('ID_HBAJU',$idHbaju)
                                ->orderBy('harga', 'desc')
                                ->get();
                                return view('shop')->with($barang);
        }else if($req->btnSort == 'terendah'){
            foreach ($barang['baju'] as $key => $value) {
                array_push($idHbaju, $value->ID_HBAJU);
            }
            $barang["Hbaju"] = DB::table('h_baju')
                                ->whereIn('ID_HBAJU',$idHbaju)
                                ->orderBy('harga', 'asc')
                                ->get();
                                return view('shop')->with($barang);
        }else if($req->btnSort == 'terbaru'){
            foreach ($barang['baju'] as $key => $value) {
                array_push($idHbaju, $value->ID_HBAJU);
            }
            $barang["Hbaju"] = DB::table('h_baju')
                                ->whereIn('ID_HBAJU',$idHbaju)
                                ->orderBy('time_added', 'desc')
                                ->get();
                                return view('shop')->with($barang);
        }else if($req->btnSort == 'terlama'){
            foreach ($barang['baju'] as $key => $value) {
                array_push($idHbaju, $value->ID_HBAJU);
            }
            $barang["Hbaju"] = DB::table('h_baju')
                                ->whereIn('ID_HBAJU',$idHbaju)
                                ->orderBy('time_added', 'asc')
                                ->get();
                                return view('shop')->with($barang);
        }else{
            return redirect("/shop");
        }
    }
    public function cekSession(){
        // Session::forget('cart');
        // Session::forget('barang');
        dd(Session::all());
    }
}
