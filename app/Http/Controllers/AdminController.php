<?php

namespace App\Http\Controllers;

use App\Http\Resources\Variant;
use Illuminate\Http\Request;
use App\Model\Users;
use App\Model\h_transaksi;
use App\Model\d_jual;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;


class AdminController extends Controller
{
    function logAdmin(){
        Auth::logout();
        return redirect("/");
    }

    function adminLog(Request $req){
        $validateData = $req->validate(
            [
                "password"=>"required",
                "username"=>"required"
            ],
            [
                "required"=>":Attribute tidak boleh kosong"
            ]
        );
        if (Auth::attempt($req->only(["username", "password"]))) {
            if(Auth::user()->status == "Aktif"){
                if(Auth::user()->jabatan == "Owner" || Auth::user()->jabatan == "Admin"){
                    Users::where('username',Auth::user()->username)->update(['Last_Login'=>Carbon::now()]);
                    return \redirect("/admin/home")->with("success", "Selamat Datang!");
                }else{
                    Auth::logout();
                    return redirect("/");
                }
            }else if(Auth::user()->status=="Disabled"){
                Auth::logout();
                return redirect("/admin")->with("gagal","failed");
            }
        }
        return redirect("/admin")->with("errors","ok");
    }

    function adminReg(Request $req){
        $pas = $req->Password;
        $validateData = $req->validate(
            [
                "Username"=>["required","unique:App\Model\Users,username"],
                "Email"=>["required","email","unique:App\Model\Users,email"],
                "Password"=>["required"],
                "ConPass"=>["in:".$pas],
                "NoHp"=>["required","numeric","unique:App\Model\Users,no_telp"],
                "Alamat"=>["required"],
                "Nama"=>["required","min:2"],
                "gender"=>["required"]
            ],
            [
                "required" => ":Attribute tidak boleh kosong",
                "ConPass.in"=>"Password Tidak Sama",
                "unique"=>":Attribute sudah terdaftar",
                "NoHp.unique"=>"Nomor Telefon Sudah terdaftar",
                "NoHp.required"=>"Nomor Telefon Tidak Boleh Kosong",
                "gender.required"=>"Harus memilih salah satu gender"
            ]
        );
        $tempUser = "";
        if(Count(explode(" ",$validateData['Nama']))>1){
           $tempData = explode(" ",$validateData["Nama"]);
           $tempUser = strtoupper(substr($tempData[0],0,1).substr($tempData[1],0,1));
        }else{
            $tempUser = strtoupper(substr($validateData["Nama"],0,2));
        }
        $countData = Users::where("id_user","like","%".$tempUser."%")->count()+1;
        if($countData < 10){
            $tempUser = $tempUser."_00".$countData;
        }else if($countData < 100){
            $tempUser = $tempUser."_0".$countData;
        }else{
            $tempUser = $tempUser."_".$countData;
        }
        $pass = password_hash($validateData["Password"], \PASSWORD_DEFAULT);
        $newUsers = new Users();
        $newUsers->id_user = $tempUser;
        $newUsers->nama_user = $validateData["Nama"];
        $newUsers->username = $validateData["Username"];
        $newUsers->email = $validateData["Email"];
        $newUsers->password = $pass;
        $newUsers->alamat = $validateData["Alamat"];
        $newUsers->jk = $validateData["gender"];
        $newUsers->no_telp = $validateData["NoHp"];
        $newUsers->status="Aktif";
        $newUsers->jabatan="Admin";
        $newUsers->save();
        return redirect("/admin/list")->with('SuccessAdd',"Berhasil");
    }

    // Baju Function
    function addBaju(Request $req){
        $validate = $req->validate(
            [
                "gambarModel"=>"mimetypes:image/jpeg,image/png",
                "namaModel"=>"required",
                "Harga"=>"required"
            ],
            [
                "gambarModel.mimetypes"=>"File yang diupload harus dalam bentuk gambar"
            ]
        );
        if($req->ukuran != null && $req->category!=null){
            if(count($req->namaVariasi) == count($req->ukuran) && count($req->namaVariasi) == count($req->category)){
                $tempId = "";
                if(Count(explode(" ",$req->NamaModel))>1){
                $tempData = explode(" ",$req->NamaModel);
                $tempId = strtoupper(substr($tempData[0],0,1).substr($tempData[1],0,1));
                }else{
                    $tempId = strtoupper(substr($req->NamaModel,0,2));
                }
                $countData = DB::table('h_baju')->where("ID_HBAJU","like","%".$tempId."%")->count()+1;
                if($countData < 10){
                    $tempId = $tempId."_00".$countData;
                }else if($countData < 100){
                    $tempId = $tempId."_0".$countData;
                }else{
                    $tempId = $tempId."_".$countData;
                }
                $req->file("gambarModel")->move(public_path("/baju"),$tempId."-".$req->file("gambarModel")->getClientOriginalName());
                DB::table('h_baju')->insert([
                    "ID_HBAJU"=>$tempId,
                    "NAMA_BAJU"=>$req->NamaModel,
                    "harga"=>$req->Harga,
                    "gambar"=>$tempId."-".$req->file("gambarModel")->getClientOriginalName(),
                    "time_added"=>Carbon::now()
                ]);
                for ($i=0; $i < count($req->namaVariasi); $i++) {
                    DB::table('d_baju')->insert([
                        "ID_HBAJU"=>$tempId,
                        "NAMA_BAJU"=>$req->namaVariasi[$i],
                        "WARNA"=>$req->color[$i],
                        "UKURAN"=>$req->ukuran[$i],
                        "STOK"=>$req->stock[$i],
                        "ID_KATEGORI"=>$req->category[$i]
                    ]);
                }
                return redirect("/admin/home")->with("Sucess","Berhasil Menambahkan Baju");
            }else{
                return redirect("/admin/home")->with("Errors","Gagal Menambahkan Baju");

            }
        }else{
            return redirect("/admin/home")->with("Errors","Berhasil Menambahkan Baju");
        }
    }
    function searchVariant(Request $req){
        $arrData = DB::table("d_baju")->where("ID_HBAJU",$req->nama)->get();
        return Variant::collection($arrData);
    }
    function searchData(Request $req){
        $arrData = DB::table("d_baju")->where("id_dbaju",$req->nama)->get();
        return Variant::collection($arrData);
    }

    function editData(Request $req){
        $nama = $req->nama;
        $warna = $req->warna;
        $ukuran = $req->ukuran;
        $stok = $req->stok;
        $kategori = $req->kategori;
        try {
            DB::table('d_baju')->where("id_dbaju",$req->id)->update(["NAMA_BAJU"=>$nama, "WARNA"=>$warna, "UKURAN"=>$ukuran, "ID_KATEGORI"=>$kategori, "STOK"=>$stok]);
            return "succes";
        } catch (\Throwable $th) {
            return "gagal";
        }

    }
    function deleteVariant(Request $req){
        try {
            DB::table('d_baju')->where('id_dbaju',$req->nama)->delete();
            return "succes";
        } catch (\Throwable $th) {
            return "gagal";
        }
    }

    function addVariant(Request $req){
        $data["id"]= $req->idBtn;
        return view('admin.AddVariant')->with($data);
    }
    function addMoreVariant(Request $req){
        if($req->ukuran != null && $req->category != null){
            if(count($req->namaVariasi) == count($req->ukuran) && count($req->namaVariasi) == count($req->category)){
                $id = $req->btnAddNewVariant;
                for ($i=0; $i < count($req->namaVariasi); $i++) {
                    DB::table('d_baju')->insert([
                        "ID_HBAJU"=>$id,
                        "NAMA_BAJU"=>$req->namaVariasi[$i],
                        "WARNA"=>$req->color[$i],
                        "UKURAN"=>$req->ukuran[$i],
                        "STOK"=>$req->stock[$i],
                        "ID_KATEGORI"=>$req->category[$i]
                    ]);
                }
                return redirect("/admin/home")->with("variantDone","ok");
            }
        }else{
            return redirect("/admin/home")->with("errors","kosong");
        }
    }
    //End Baju Function

    // Transaksi Function
    function ProcessOrder(Request $req){
        h_transaksi::where("id_hjual",$req->id)->update(["status"=>"1"]);
        return "Success";
    }

    function getDataJual(Request $req){
        $data = [];
        foreach (d_jual::where('id_hjual',$req->id)->get() as $key => $value) {
            $Newdata = [
                "nama_baju"=> DB::table('d_baju')->where('id_dbaju',$value['id_barang'])->value('NAMA_BAJU'),
                "jumlah"=>$value['qty'],
                "harga"=>$value["harga"],
                "subtotal"=>$value['subtotal']
            ];
            $data[] = $Newdata;
        }
        return json_encode($data);
    }

    function InvalidPayment(Request $req){
        h_transaksi::where("id_hjual",$req->id)->update(["status"=>"3"]);
        return "Success";
    }

    function FinishOrder(Request $req){
        h_transaksi::where("id_hjual",$req->id)->update(["status"=>"2"]);
        return "Success";
    }
    //End Transaksi Function

    //Function Admin List
    function DeleteAdmin(Request $req){
        Users::where("id_user",$req->id)->delete();
        return "Success";
    }

    function AdminStatus(Request $req){
        if($req->type == "enable"){
            Users::where("id_user",$req->id)->update(["status"=>"Aktif"]);
        }else if($req->type =="Disable"){
            Users::where("id_user",$req->id)->update(["status"=>"Disabled"]);
        }
        return "Success";
    }
    //End Function Admin List

    //Function User
    function UserStatus(Request $req){
        if($req->type == "unblacklist"){
            Users::where("id_user",$req->id)->update(["status"=>"Aktif"]);
        }else if($req->type =="blacklist"){
            Users::where("id_user",$req->id)->update(["status"=>"Blacklist"]);
        }
        return "Success";
    }
}
