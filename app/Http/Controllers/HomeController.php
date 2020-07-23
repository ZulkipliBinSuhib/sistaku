<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Matkul;
use App\Prodi;
use App\Sebaran;
use App\Dosen;
use App\Kelas;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if(Auth::user()->prodi){
            $data['matkul'] = Matkul::where('prodi',Auth::user()->prodi)->count();
            $data['prodi'] = Prodi::where('id',Auth::user()->prodi)->count();
            $data['sebaran'] = Sebaran::where('prodi',Auth::user()->prodi)->count();
            $data['dosen'] = Dosen::where('prodi',Auth::user()->prodi)->count();
            $data['kelas'] = Kelas::where('prodi',Auth::user()->prodi)->count();
        }
        else{
        $data['matkul'] = Matkul::count();
        $data['prodi'] = Prodi::count();
        $data['sebaran'] = Sebaran::count();
        $data['dosen'] = Dosen::count();
        $data['kelas'] = Kelas::count();
        }
        
        return view('home',$data);
    }
   
}
