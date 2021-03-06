<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use App\Exports\RekapExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use App\Dosen;

class RekapController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index(Request $request) 
    {
       
        $jam = $request->jam ; 
        $data['dosen'] = DB::table('dosen');
        $id = Auth::user()->prodi;
        $cari_dosen = DB::table('dosen')->groupBy('name')->get();
        $cari_tahun = DB::table('sebaran')->groupBy('tahun_akademik')->get();
        $data['pilih_tahun'] = DB::table('sebaran')->orderBy('tahun_akademik')->groupBy('tahun_akademik')->get();
        $data['pilih_prodi'] = DB::table('sebaran')
                                ->join('prodi','sebaran.prodi','=','prodi.id')
                                ->select('prodi.id','prodi.nama')
                                ->orderBy('prodi.id')->groupBy('prodi.id')->get();
        $get_prodiAndDosen = DB::table('sebaran')
                            ->join('prodi','sebaran.prodi', '=', 'prodi.id')
                            ->join('matkul','sebaran.mata_kuliah','=','matkul.id')
                            ->join('dosen','sebaran.dosen_mengajar','=','dosen.nidn')
                            ->select('dosen.name','dosen.nidn','dosen.jenis_kelamin','dosen.status','prodi.nama','dosen.id','dosen.bidang')
                            ->groupBy('nidn');
                                     
        if(!empty($id)){
        $get_prodiAndDosen = $get_prodiAndDosen->where('dosen.prodi',$id);
        }
        
        //Filter
        if(!empty($_GET)){ 
            if(!empty($_GET['prodi'])){
                $prodi = $_GET['prodi'];
                $get_prodiAndDosen->where('prodi.id',$prodi);
              } 
            if(!empty($_GET['dosen'])){
                $dosen = $_GET['dosen'];
                $get_prodiAndDosen->where('dosen.nidn',$dosen);
                } 
            if(!empty($_GET['tahun'])){
                $tahun = $_GET['tahun'];
                $get_prodiAndDosen->where('sebaran.tahun_akademik',$tahun);
                }
            if(!empty($_GET['semester'])){
                $get_semester = $request->semester;
                if ($get_semester == 'ganjil'){
                    $get_semester = ['1','3','5','7'];
                }else{
                    $get_semester = ['2','4','6','8'];
                }
                $get_prodiAndDosen->whereIn('sebaran.semester',$get_semester);
                }
        } 
    
        $get_prodiAndDosen = $get_prodiAndDosen->get();
       
        foreach($get_prodiAndDosen as &$dosen)
        {
            $arr = [];
            $dosen->jumlah_jam = DB::table('sebaran')
                                ->join('dosen','sebaran.dosen_mengajar','=','dosen.nidn')
                                ->join('matkul','sebaran.mata_kuliah','=','matkul.id')
                                ->join('kelas','sebaran.kd_kelas','=','kelas.id')
                                ->select('kelas.keterangan','dosen.name','dosen.nidn','dosen.bidang','matkul.jam_minggu','matkul.sks','matkul.teori','matkul.praktek','matkul.kurikulum','matkul.semester')
                                ->where('dosen_mengajar',$dosen->nidn)->where('kelas.keterangan','reguler')->sum('jam_minggu'); 
            $dosen->jumlah_jam_karyawan = DB::table('sebaran')
                                ->join('dosen','sebaran.dosen_mengajar','=','dosen.nidn')
                                ->join('matkul','sebaran.mata_kuliah','=','matkul.id')
                                ->join('kelas','sebaran.kd_kelas','=','kelas.id')
                                ->select('kelas.keterangan','dosen.name','dosen.nidn','dosen.bidang','matkul.jam_minggu','matkul.sks','matkul.teori','matkul.praktek','matkul.kurikulum','matkul.semester')
                                ->where('dosen_mengajar',$dosen->nidn)->where('kelas.keterangan','karyawan')->sum('jam_minggu');
            $dosen->total_jam = DB::table('sebaran')
                                ->join('dosen','sebaran.dosen_mengajar','=','dosen.nidn')
                                ->join('matkul','sebaran.mata_kuliah','=','matkul.id')
                                ->select('dosen.name','dosen.nidn','dosen.bidang','matkul.jam_minggu','matkul.sks','matkul.teori','matkul.praktek','matkul.kurikulum','matkul.semester')
                                ->where('dosen_mengajar',$dosen->nidn)->sum('jam_minggu');

            $dosen->tahun_akademik = DB::table('sebaran')->where('dosen_mengajar',$dosen->nidn)->select('tahun_akademik')->groupBy('tahun_akademik')->get();
            $dosen->jumlah_sks = DB::table('sebaran')
                                ->join('dosen','sebaran.dosen_mengajar','=','dosen.nidn')
                                ->join('matkul','sebaran.mata_kuliah','=','matkul.id')
                                ->join('kelas','sebaran.kd_kelas','=','kelas.id')
                                ->select('kelas.keterangan','dosen.name','dosen.nidn','dosen.bidang','matkul.jam_minggu','matkul.sks','matkul.teori','matkul.praktek','matkul.kurikulum','matkul.semester')
                                ->where('dosen_mengajar',$dosen->nidn)->where('kelas.keterangan','reguler')->sum('sks');
            $dosen->jumlah_sks_karyawan = DB::table('sebaran')
                                ->join('dosen','sebaran.dosen_mengajar','=','dosen.nidn')
                                ->join('matkul','sebaran.mata_kuliah','=','matkul.id')
                                ->join('kelas','sebaran.kd_kelas','=','kelas.id')
                                ->select('kelas.keterangan','dosen.name','dosen.nidn','dosen.bidang','matkul.jam_minggu','matkul.sks','matkul.teori','matkul.praktek','matkul.kurikulum','matkul.semester')
                                ->where('dosen_mengajar',$dosen->nidn)->where('kelas.keterangan','karyawan')->sum('sks');
            $dosen->total_sks = DB::table('sebaran')
                                ->join('dosen','sebaran.dosen_mengajar','=','dosen.nidn')
                                ->join('matkul','sebaran.mata_kuliah','=','matkul.id')
                                ->select('kelas.keterangan','dosen.name','dosen.nidn','dosen.bidang','matkul.jam_minggu','matkul.sks','matkul.teori','matkul.praktek','matkul.kurikulum','matkul.semester')
                                ->where('dosen_mengajar',$dosen->nidn)->sum('sks');
            $dosen->matkul_diambil = DB::table('sebaran')
                                    ->join('prodi','prodi.id','=','sebaran.prodi')
                                    ->join('matkul','sebaran.mata_kuliah','=','matkul.id')
                                    ->join('dosen','sebaran.dosen_mengajar','=','dosen.nidn')
                                    ->select('sebaran.prodi','prodi.nama','matkul.matkul','sebaran.dosen_mengajar')->get()
                                    ->where('dosen_mengajar',$dosen->nidn);
            $dosen->prodi_diambil = DB::table('sebaran')->where('dosen_mengajar',$dosen->nidn)->select('prodi')->get();
            $dosen->no = 1 ;
           
            
            }
        $data['get_prodiAndDosen'] = $get_prodiAndDosen;
        $data['cari_dosen'] = $cari_dosen;
        $data['cari_tahun'] = $cari_tahun;
        $data['jam'] = $jam;
       
    
        return view('rekap.index',$data);
    }
    // public function ajax_dosen(Request $request){
    //     $jam = $request->jam;
    //     $kelas= Kelas::where('id','=',$get)->first();
    //     if(isset($kelas)){
    //         $data = array(
                
    //             'kode' => $kelas['id'],
                
    //         ); 
    //     return response()->json($data);
          
    //   }
    // }
    public function export_excel(Request $request)
	{
        
            return Excel::download(new RekapExport($request), 'Rekapitulasi.xlsx');
        
    }
}
