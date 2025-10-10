<?php

namespace App\Http\Controllers\Mobile;

use App\Models\Pengajuanizin;  
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class PresensiController extends BaseMobileController
{

    public function create()
    {
        $hariini = date("Y-m-d");
        $nik = $this->user->nik;
        $cek = DB::table('presensi')->where('tgl_presensi', $hariini)->where('nik', $nik)->count();
        return view('mobile.presensi.create', compact('cek'));
    }

    public function store(Request $request)
{
    $nik = $this->user->nik;
    $tgl_presensi = date("Y-m-d");
    $jam = date("H:i:s");
    $lokasi = $request->lokasi;
    $image = $request->image;
    $inoutmode = $request->inoutmode; // 1 = Masuk, 2 = Pulang

    if (!$image) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gambar tidak ditemukan.'
        ]);
    }

    // Validasi mode
    if (!in_array($inoutmode, [1, 2])) {
        return response()->json([
            'status' => 'error',
            'message' => 'Mode presensi tidak valid.'
        ]);
    }

    // Cek apakah sudah ada presensi masuk/pulang
    $cekMasuk = DB::table('presensi')
        ->where('nik', $nik)
        ->where('tgl_presensi', $tgl_presensi)
        ->where('inoutmode', 1)
        ->first();

    $cekPulang = DB::table('presensi')
        ->where('nik', $nik)
        ->where('tgl_presensi', $tgl_presensi)
        ->where('inoutmode', 2)
        ->first();

    if ($inoutmode == 1 && $cekMasuk) {
        return response()->json([
            'status' => 'error',
            'message' => 'Anda sudah absen masuk hari ini.'
        ]);
    }

    if ($inoutmode == 2 && !$cekMasuk) {
        return response()->json([
            'status' => 'error',
            'message' => 'Anda belum melakukan absen masuk hari ini.'
        ]);
    }

    if ($inoutmode == 2 && $cekPulang) {
        return response()->json([
            'status' => 'error',
            'message' => 'Anda sudah absen pulang hari ini.'
        ]);
    }

    // Simpan foto
    $formatName = $nik . "-" . $tgl_presensi . "-" . ($inoutmode == 1 ? "in" : "out");
    $image_parts = explode(";base64,", $image);
    if (count($image_parts) < 2) {
        return response()->json([
            'status' => 'error',
            'message' => 'Format gambar tidak valid.'
        ]);
    }

    $image_base64 = base64_decode($image_parts[1]);
    $fileName = $formatName . ".png";
    $filePath = 'uploads/absensi/' . $fileName;

    // Simpan data presensi
    $data = [
        'nik' => $nik,
        'tgl_presensi' => $tgl_presensi,
        'jam_in' => $jam,
        'inoutmode' => $inoutmode,
        'foto_in' => $fileName,
        'lokasi' => $lokasi,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    $simpan = DB::table('presensi')->insert($data);

    if ($simpan) {
        Storage::disk('public')->put($filePath, $image_base64);

        return response()->json([
            'status' => 'success',
            'message' => $inoutmode == 1 ? 'Absen masuk berhasil!' : 'Absen pulang berhasil!',
            'type' => $inoutmode == 1 ? 'in' : 'out'
        ]);
    } else {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal menyimpan presensi, silakan coba lagi.'
        ]);
    }
}



    public function  editprofile()
    {
        $nik = $this->user->nik;
        $karyawan = DB::table('karyawan')->where('nik', $nik)->first();
        return view('presensi.editprofile', compact('karyawan'));
    }

    public function updateprofile(Request $request)
    {
        $nik = $this->user->nik;
        $nama_lengkap = $request->nama_lengkap;
        $no_hp = $request->no_hp;
        $password = Hash::make($request->password);
        $karyawan = DB::table('karyawan')->where('nik', $nik)->first();

        if ($request->hasFile('foto')) {
            $foto = $nik . "." . $request->file('foto')->getClientOriginalExtension();
        
            $folderPath = 'uploads/karyawan/';
        
            // Menyimpan foto di disk 'public'
            $request->file('foto')->storeAs($folderPath, $foto, 'public');  // Menggunakan disk 'public' tanpa 'public/' di path
        } else {
            // Jika tidak ada foto, gunakan foto lama
            $foto = $karyawan->foto;
        }    

        // data yang akan diupdate
        if (empty($request->password)) {
            $data = [
                'nama_lengkap' => $nama_lengkap,
                'no_hp' => $no_hp,
                'foto' => $foto
            ];
        } else {
            $data = [
                'nama_lengkap' => $nama_lengkap,
                'no_hp' => $no_hp,
                'password' => $password,
                'foto' => $foto
            ];
        }

        // update data ke database
        $update = DB::table('karyawan')->where('nik', $nik)->update($data);

        if ($update) {
            return Redirect::back()->with(['success' => 'Data Berhasil Di Update']);
        } else {
            return Redirect::back()->with(['error' => 'Data Gagal Di Update']);
        }
    }

    public function histori()
    {
        $namabulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni","Juli", "Agustus","September", "Oktober", "November", "Desember"];
        return view('presensi.histori', compact('namabulan'));
    }

    public function gethistori(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $nik = $this->user->nik;

        $histori = DB::table('presensi')
        ->whereRaw('MONTH(tgl_presensi)="'.$bulan.'"')
        ->whereRaw('YEAR(tgl_presensi)="'.$tahun.'"')
        ->where('nik',$nik)
        ->orderBy('tgl_presensi')
        ->get();

        return view('presensi.gethistori', compact('histori'));
    }
    public function izin()
    {
        $nik = $this->user->nik;
        $dataizin = DB::table('pengajuan_izin')->where('nik', $nik)->get();
        return view('presensi.izin', compact('dataizin'));
    }

    public function buatizin()
    {
        return view('presensi.buatizin');
    }

    public function storeizin(Request $request)
    {
        $nik = $this->user->nik;
        $tgl_izin = $request->tgl_izin;
        $status = $request->status;
        $keterangan = $request->keterangan;

        $data = [
            'nik' => $nik,
            'tgl_izin' => $tgl_izin,
            'status' => $status,
            'keterangan' => $keterangan
        ];

        $simpan = DB::table('pengajuan_izin')->insert($data);

        if( $simpan) {
            return redirect('/presensi/izin')->with(['success' => 'Data Berhasil Disimpan']);
        } else {
            return redirect('/presensi/izin')->with(['error' => 'Data Gagal Disimpan']);
        }
    }

    public function monitoring()
    {
        return view('presensi.monitoring');
    }

    public function getpresensi(Request $request)
    {
        $tanggal = $request->tanggal;
        $presensi = DB::table('presensi')
            ->select('presensi.*', 'nama_lengkap', 'nama_dept')
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->join('departemen', 'karyawan.kode_dept', '=', 'departemen.kode_dept')
            ->where('tgl_presensi', $tanggal)
            ->get();

            return view('presensi.getpresensi', compact('presensi'));
    }

    public function tampilkanpeta(Request $request)
    {
        $id = $request->id;
        $presensi = DB::table('presensi')->where('id', $id)
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->first();

        return view('presensi.showmap', compact('presensi'));
    }

    public function laporan()
    {
        $namabulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni","Juli", "Agustus","September", "Oktober", "November", "Desember"];
        $karyawan = DB::table('karyawan')->orderBy('nama_lengkap')->get();
        return view('presensi.laporan', compact('namabulan', 'karyawan'));
    }

    public function cetaklaporan(Request $request)
    {
        $nik = $request->nik;
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $namabulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni","Juli", "Agustus","September", "Oktober", "November", "Desember"];
        $karyawan = DB::table('karyawan')->where('nik', $nik)
        ->join('departemen', 'karyawan.kode_dept', '=', 'departemen.kode_dept')
        ->first();

        $presensi = DB::table('presensi')
        ->where('nik',$nik)
        ->whereRaw('MONTH(tgl_presensi)="'.$bulan.'"')
        ->whereRaw('YEAR(tgl_presensi)="'.$tahun.'"')
        ->orderBy('tgl_presensi')
        ->get();

        if  (isset($_POST['exportexel'])) {
            $time = date("d-M-Y H:i:s");
            //Fungsi header dengan mengirimkan raw data exel
           header("Content-type: application/vnd-ms-exel");
            //Mendefiniskan Nama File Export "hasil-export.xls"
            header("Content-Disposition: attachment; filename=Laporan Presensi Ahad Pagi RS PKU $time.xls");
            return view('presensi.cetaklaporanexel', compact('bulan', 'tahun', 'namabulan',  'karyawan', 'presensi'));
        }
        return view('presensi.cetaklaporan', compact('bulan', 'tahun', 'namabulan',  'karyawan', 'presensi'));
    }

    public function rekap()
    {
        $namabulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni","Juli", "Agustus","September", "Oktober", "November", "Desember"];
        return view('presensi.rekap', compact('namabulan'));
    }

    public function cetakrekap(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $namabulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni","Juli", "Agustus","September", "Oktober", "November", "Desember"];
        $rekap = DB::table('presensi')
        ->select('presensi.nik', 'karyawan.nama_lengkap', 'presensi.tgl_presensi', 'presensi.jam_in')
        ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
        ->whereRaw('MONTH(tgl_presensi) = ?', [$bulan])
        ->whereRaw('YEAR(tgl_presensi) = ?', [$tahun]) 
        ->whereRaw('DAYOFWEEK(tgl_presensi) = 1') 
        ->orderBy('presensi.tgl_presensi', 'ASC') 
        ->get();

    if  (isset($_POST['exportexel'])) {
        $time = date("d-M-Y H:i:s");
        //Fungsi header dengan mengirimkan raw data exel
       header("Content-type: application/vnd-ms-exel");
        //Mendefiniskan Nama File Export "hasil-export.xls"
        header("Content-Disposition: attachment; filename=Rekap Presensi Ahad Pagi RS PKU $time.xls");
    }
        return view('presensi.cetakrekap', compact('bulan', 'tahun', 'namabulan', 'rekap'));
    }

    public function izinsakit(Request $request)
    {
        $query = Pengajuanizin::query();
        $query->select('id', 'tgl_izin', 'pengajuan_izin.nik', 'nama_lengkap', 'jabatan', 'status', 'status_approved', 'keterangan');
        $query->join('karyawan', 'pengajuan_izin.nik', '=', 'karyawan.nik');
        if (!empty($request->dari) && !empty($request->sampai)) {
            $query->whereBetween('tgl_izin', [$request->dari, $request->sampai]);
        }

        if (!empty($request->nik)) {
            $query->where('pengajuan_izin.nik', $request->nik);
        }

        if (!empty($request->nama_lengkap)) {
            $query->where('nama_lengkap', 'like', '%'. $request->nama_lengkap. '%');
        }

        if ($request->status_approved === '0' || $request->status_approved === '1' || $request->status_approved === '2') {
            $query->where('status_approved', $request->status_approved);
        }
        $query->orderBy('tgl_izin', 'desc');
        $izinsakit =$query->paginate(50);
        $izinsakit->appends($request->all());
        return view('presensi.izinsakit', compact('izinsakit'));
    }

    public function approvedizinsakit(Request $request){
        $status_approved = $request->status_approved;
        $id_izinsakit_form = $request->id_izinsakit_form;
        $update = DB::table('pengajuan_izin')->where('id', $id_izinsakit_form)->update([
            'status_approved' => $status_approved
        ]);
        if ($update) {
            return Redirect::back()->with(['success' => 'Data Berhasil Di Update']);
        } else {
            return Redirect::back()->with(['warning' => 'Data Gagal Di Update']);
        }
    }

    public function batalkanizinsakit($id)
    {
        $update = DB::table('pengajuan_izin')->where('id', $id)->update([
            'status_approved' => 0
        ]);
        if ($update) {
            return Redirect::back()->with(['success' => 'Data Berhasil Di Update']);
        } else {
            return Redirect::back()->with(['warning' => 'Data Gagal Di Update']);
        }
    }
    
    public function deleteizinsakit($id)
    {
        $delete = DB::table('pengajuan_izin')->where('id', $id)->delete();
        if ($delete) {
            return Redirect::back()->with(['success' => 'Data Berhasil Dihapus']);
        } else {
            return Redirect::back()->with(['warning' => 'Data Gagal Dihapus']);
        }
    }

    public function cekpengajuanizin(Request $request)
    {
        $tgl_izin = $request->tgl_izin;
        $nik = $this->user->nik;
        
        $cek = DB::table('pengajuan_izin')->where('nik', $nik)->where('tgl_izin', $tgl_izin)->count();;
        return $cek;
    }
}
