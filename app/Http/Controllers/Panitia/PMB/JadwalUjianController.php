<?php

namespace App\Http\Controllers\Panitia\PMB;

use PDF;
use Mail;
use QrCode;
use DataTables;
use Carbon\Carbon;
use App\Mail\PMB\JadwalUjian;
use App\Jobs\KirimJadwalUjianJob;
use App\Http\Controllers\Controller;
use App\Repositories\PMB\SesiRepository;
use App\Repositories\PMB\GelombangRepository;
use App\Repositories\Prodi\PMB\SoalRepository;
use App\Repositories\PMB\JadwalUjianRepository;
use App\Http\Requests\Panitia\JadwalUjianRequest;
use App\Repositories\PMB\CalonMahasiswaRepository;
use App\Repositories\Dasbor\Master\ProdiRepository;
use App\Repositories\Dasbor\Master\TahunAjaranRepository;

class JadwalUjianController extends Controller
{
    private $soalRepo;
    private $sesiRepo;
    private $prodiRepo;
    private $GelombangRepo;
    private $jadwalUjianRepo;
    private $tahunAjaranRepo;
    private $calonMahasiswaRepo;

    public function __construct(
        SoalRepository $soalRepository,
        SesiRepository $sesiRepository,
        ProdiRepository $prodiRepository,
        GelombangRepository $gelombangRepository,
        JadwalUjianRepository $jadwalUjianRepository,
        TahunAjaranRepository $tahunAjaranRepository,
        CalonMahasiswaRepository $calonMahasiswaRepository
    ) {
        $this->soalRepo = $soalRepository;
        $this->sesiRepo = $sesiRepository;
        $this->prodiRepo = $prodiRepository;
        $this->gelombangRepo = $gelombangRepository;
        $this->jadwalUjianRepo = $jadwalUjianRepository;
        $this->tahunAjaranRepo = $tahunAjaranRepository;
        $this->calonMahasiswaRepo = $calonMahasiswaRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function data()
    {
        $jadwalUjian = $this
            ->jadwalUjianRepo
            ->getAllData();

        return DataTables::of($jadwalUjian)
            ->addColumn('action', function($jadwalUjian){
                return '<center><a href="/panitia/pmb/jadwal-ujian/form-ubah/'.$jadwalUjian->id.'" class="btn btn-warning btn-xs"><i class="fa fa-pencil"></i></a> <a href="#hapus" onclick="destroy('.$jadwalUjian->id.')" class="btn btn-xs btn-danger"><i class="fa fa-times"></i></a></center>';
            })
            ->editColumn('tanggal_mulai_ujian', function($jadwalUjian){
                return $jadwalUjian->tanggal_mulai_ujian->formatLocalized('%c');
            })
            ->editColumn('tanggal_selesai_ujian', function($jadwalUjian){
                return $jadwalUjian->tanggal_selesai_ujian->formatLocalized('%c');
            })
            ->rawColumns(['action', 'tanggal_mulai_ujian', 'tanggal_selesai_ujian'])
            ->make(true);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('panitia.pmb.jadwal_ujian.jadwal_ujian');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tahunAjaran = $this
            ->tahunAjaranRepo
            ->getAllData();

        $gelombang = $this
            ->gelombangRepo
            ->getAllData();

        $prodi = $this
            ->prodiRepo
            ->getAllData();

        $soal = $this
            ->soalRepo
            ->getAllDataByJadwalUjian();

        return view('panitia.pmb.jadwal_ujian.form_tambah', compact(
            'tahunAjaran',
            'gelombang',
            'prodi',
            'soal'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(JadwalUjianRequest $jadwalUjianReq)
    {
        $kodeJurusan = $jadwalUjianReq->kode_jurusan;
        $kodeSoal = $jadwalUjianReq->kode_soal;
        $kodeGelombang = $jadwalUjianReq->kode_gelombang;
        $statusPendaftaran = $jadwalUjianReq->status_pendaftaran;
        $tahun = $jadwalUjianReq->tahun;
        $tanggalMulaiUjian = Carbon::parse($jadwalUjianReq->tanggal_mulai_ujian);
        $tanggalSelesaiUjian = Carbon::parse($jadwalUjianReq->tanggal_selesai_ujian);
        $kode = $jadwalUjianReq->kode;
        $jeda = $jadwalUjianReq->durasi_jeda;
        $jumlahSesi = $jadwalUjianReq->total_sesi;
        $kodePendaftaran = $jadwalUjianReq->kode_pendaftaran;
        $ruangan = $jadwalUjianReq->ruangan;

        foreach ($kodePendaftaran as $item) {
            $dataSesi[] = [
                'kode_jadwal_ujian' => $kode,
                'kode_pendaftaran' => $item,
                'status' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];

            $dataCalonMahasiswa = [
                'status_jadwal_ujian' => 1
            ];

            $updateCalonMahasiswaData = $this
                ->calonMahasiswaRepo
                ->updateCalonMahasiswaDataByJadwalUjian($dataCalonMahasiswa, $item);
        }

        $data = [
            'kode' => $kode,
            'kode_soal' => $kodeSoal,
            'kode_gelombang' => $kodeGelombang,
            'kode_jurusan' => $kodeJurusan,
            'status_pendaftaran' => $statusPendaftaran,
            'tahun' => $tahun,
            'tanggal_mulai_ujian' => $tanggalMulaiUjian,
            'tanggal_selesai_ujian' => $tanggalSelesaiUjian,
            'ruangan' => $ruangan,
            'status' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];

        $store = $this
            ->jadwalUjianRepo
            ->storeJadwalUjianData($data);

        $storeSesi = $this
            ->sesiRepo
            ->storeSesiData($dataSesi);

        return redirect('/panitia/pmb/jadwal-ujian')
            ->with([
                'notification' => 'Data berhasil disimpan'
            ]);

        // if($totalCalonMahasiswa == 0){
        //     return redirect('/panitia/pmb/jadwal-ujian/form-tambah')
        //         ->with([
        //             'notification' => 'Tidak ada calon mahasiswa yang terdaftar'
        //         ]);
        // }else{

        //     $selisihDurasi = $tanggalMulaiUjian->diffInMinutes($tanggalSelesaiUjian);
        //     $totalSesi = $jadwalUjianReq->total_sesi;
        //     $tempTotalSesi = $totalSesi;
        //     $sesiPertama = TRUE;
        //     $i = 0;
        //     $a = 1;
        //     $jeda = $jadwalUjianReq->durasi_jeda;
        //     $temp = ceil(round($totalCalonMahasiswa / $totalSesi));

        //     while($i < $totalCalonMahasiswa){
        //         while($i < $tempTotalSesi){
        //                 $dataSesi[] = [
        //                     'kode_jadwal_ujian' => $kode.$a,
        //                     'kode_pendaftaran' => $calonMahasiswa[$i]['kode'],
        //                     'created_at' => Carbon::now(),
        //                     'updated_at' => Carbon::now()
        //                 ];
        //             $i++;
        //         }
        //         $a++;
        //         $tempTotalSesi++;
        //     }

        //     $sesiKe = 1;

        //     for ($i=0; $i<$temp; $i++) {
        //         if($sesiPertama == TRUE){
        //             $data[] = [
        //                     'kode' => $kode.$sesiKe++,
        //                     'kode_soal' => $kodeSoal,
        //                     'kode_gelombang' => $kodeGelombang,
        //                     'kode_jurusan' => $kodeJurusan,
        //                     'status_pendaftaran' => $statusPendaftaran,
        //                     'tahun' => $tahun,
        //                     'tanggal_mulai_ujian' => $tanggalMulaiUjian,
        //                     'tanggal_selesai_ujian' => $tanggalSelesaiUjian,
        //                     'status' => 0,
        //                     'created_at' => Carbon::now(),
        //                     'updated_at' => Carbon::now()
        //                 ];
        //             $sesiPertama = FALSE;
        //         }else{
        //             $tempTanggalMulaiUjian = $tanggalSelesaiUjian->copy()->addMinutes($jeda);
        //             $tempTanggalSelesaiUjian = $tempTanggalMulaiUjian->copy()->addMinutes($selisihDurasi);
        //             $tanggalSelesaiUjian = $tempTanggalSelesaiUjian;

        //             $data[] = [
        //                 'kode' => $kode.$sesiKe++,
        //                 'kode_soal' => $kodeSoal,
        //                 'kode_gelombang' => $kodeGelombang,
        //                 'kode_jurusan' => $kodeJurusan,
        //                 'status_pendaftaran' => $statusPendaftaran,
        //                 'tahun' => $tahun,
        //                 'tanggal_mulai_ujian' => $tempTanggalMulaiUjian,
        //                 'tanggal_selesai_ujian' => $tempTanggalSelesaiUjian,
        //                 'status' => 0,
        //                 'created_at' => Carbon::now(),
        //                 'updated_at' => Carbon::now()
        //             ];
        //         }
        //     }


        //     $storeSesi = $this
        //         ->sesiRepo
        //         ->storeSesiData($dataSesi);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $jadwalUjian = $this
            ->jadwalUjianRepo
            ->getSingleData($id);

        $tahunAjaran = $this
            ->tahunAjaranRepo
            ->getAllData();

        $gelombang = $this
            ->gelombangRepo
            ->getAllData();

        $prodi = $this
            ->prodiRepo
            ->getAllData();

        $soal = $this
            ->soalRepo
            ->getAllDataByJadwalUjian();

        $tanggalMulaiUjian = $jadwalUjian
            ->tanggal_mulai_ujian
            ->format('d-m-Y H:i:s');

        $tempTanggalMulaiUjian = $tanggalMulaiUjian = $jadwalUjian
            ->tanggal_mulai_ujian->format('d-m-Y');

        $tanggalSelesaiUjian = $jadwalUjian
            ->tanggal_selesai_ujian
            ->format('d-m-Y H:i:s');

        return view('panitia.pmb.jadwal_ujian.form_ubah', compact(
            'jadwalUjian',
            'tahunAjaran',
            'gelombang',
            'prodi',
            'soal',
            'tanggalMulaiUjian',
            'tanggalSelesaiUjian',
            'tempTanggalMulaiUjian'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(JadwalUjianRequest $jadwalUjianReq, $id)
    {
        $kodeJurusan = $jadwalUjianReq->kode_jurusan;
        $kodeSoal = $jadwalUjianReq->kode_soal;
        $kodeGelombang = $jadwalUjianReq->kode_gelombang;
        $statusPendaftaran = $jadwalUjianReq->status_pendaftaran;
        $tahun = $jadwalUjianReq->tahun;
        $tanggalMulaiUjian = Carbon::parse($jadwalUjianReq->tanggal_mulai_ujian);
        $tanggalSelesaiUjian = Carbon::parse($jadwalUjianReq->tanggal_selesai_ujian);
        $kode = $jadwalUjianReq->kode;
        $jeda = $jadwalUjianReq->durasi_jeda;
        $jumlahSesi = $jadwalUjianReq->total_sesi;
        $kodePendaftaran = $jadwalUjianReq->kode_pendaftaran;
        $ruangan = $jadwalUjianReq->ruangan;
        $kodeJadwalUjian = $jadwalUjianReq->kode_jadwal_ujian;

        $sesi = $this
            ->sesiRepo
            ->getAllDataByJadwalUjianForDestroy($kodeJadwalUjian);

        foreach($sesi as $item ){
            $dataCalonMahasiswa = [
                'status_jadwal_ujian' => 0
            ];

            $updateCalonMahasiswaData = $this
                ->calonMahasiswaRepo
                ->updateCalonMahasiswaDataByJadwalUjian($dataCalonMahasiswa, $item->kode_pendaftaran);
        }

        $destroySesi = $this
            ->sesiRepo
            ->destroySesiDataByJadwalUjian($kodeJadwalUjian);

        foreach ($kodePendaftaran as $item) {
            $dataSesi[] = [
                'kode_jadwal_ujian' => $kode,
                'kode_pendaftaran' => $item,
                'status' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];

            $dataCalonMahasiswa = [
                'status_jadwal_ujian' => 1
            ];

            $updateCalonMahasiswaData = $this
                ->calonMahasiswaRepo
                ->updateCalonMahasiswaDataByJadwalUjian($dataCalonMahasiswa, $item);
        }

        $data = [
            'kode' => $kode,
            'kode_soal' => $kodeSoal,
            'kode_gelombang' => $kodeGelombang,
            'kode_jurusan' => $kodeJurusan,
            'status_pendaftaran' => $statusPendaftaran,
            'tahun' => $tahun,
            'tanggal_mulai_ujian' => $tanggalMulaiUjian,
            'tanggal_selesai_ujian' => $tanggalSelesaiUjian,
            'ruangan' => $ruangan,
            'status' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];

        $storeSesi = $this
            ->sesiRepo
            ->storeSesiData($dataSesi);

        $store = $this
            ->jadwalUjianRepo
            ->updateJadwalUjianData($data, $id);

        return redirect('/panitia/pmb/jadwal-ujian')
            ->with([
                'notification' => 'Data berhasil disimpan'
            ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $jadwalUjian = $this
            ->jadwalUjianRepo
            ->getSingleData($id);

        $kodeJadwalUjian = $jadwalUjian->kode;

        $sesi = $this
            ->sesiRepo
            ->getAllDataByJadwalUjianForDestroy($kodeJadwalUjian);

        foreach($sesi as $item ){
            $dataCalonMahasiswa = [
                'status_jadwal_ujian' => 0
            ];

            $updateCalonMahasiswaData = $this
                ->calonMahasiswaRepo
                ->updateCalonMahasiswaDataByJadwalUjian($dataCalonMahasiswa, $item->kode_pendaftaran);
        }

        $destroy = $this
            ->jadwalUjianRepo
            ->destroyJadwalUjianData($id);

        $destroySesi = $this
            ->sesiRepo
            ->destroySesiDataByJadwalUjian($kodeJadwalUjian);

        return response()
            ->json([
                'destroyed' => $sesi
            ]);
    }

    public function checkData($startExam)
    {
        $tanggalSekarang = Carbon::today()->toDateString();

        $tanggalMulaiUjian = date('Y-m-d', strtotime($startExam));

        $totalJadwalUjian = $this
            ->jadwalUjianRepo
            ->getSingleDataForCount($tanggalMulaiUjian);

        $total = $totalJadwalUjian + 1;

        return response()
            ->json([
                'status' => TRUE,
                'total' => $total
            ]);
    }

    public function sendEmail(
        $id, $kode
    ) {
        $sesi = $this
            ->sesiRepo
            ->getAllDataForEmail($kode);

        $tahunAjaran = date('Y');

        foreach ($sesi as $item) {
            $password = str_random(7);

            $dataCalonMahasiswa = [
                'password' => bcrypt($password)
            ];

            $update = $this
                ->calonMahasiswaRepo
                ->updateCalonMahasiswaData($dataCalonMahasiswa, $item->calon_mahasiswa_id);

            $kodePendaftaran = $item->kode_pendaftaran;
            $nama = $item->nama;
            $kotaLahir = $item->kota_lahir;
            $tanggalBulan = $item->bulan;
            $tahun = $item->tahun;
            $tanggal = $item->tanggal;
            $foto4x6 = $item->foto_4x6;
            $tanggalMulaiUjian = $item->tanggal_mulai_ujian->formatLocalized('%c');
            $tanggalSelesaiUjian = $item->tanggal_selesai_ujian->formatLocalized('%c');
            $durasi = $item->tanggal_mulai_ujian->diffInMinutes($item->tanggal_selesai_ujian);

            if($tanggalBulan == '1'){
                $bulan = "Januari";
            }else if($tanggalBulan == '2'){
                $bulan = "Februari";
            }else if($tanggalBulan == '3'){
                $bulan = "Maret";
            }else if($tanggalBulan == '4'){
                $bulan = "April";
            }else if($tanggalBulan == '5'){
                $bulan = "Mei";
            }else if($tanggalBulan == '6'){
                $bulan = "Juni";
            }else if($tanggalBulan == '7'){
                $bulan = "Juli";
            }else if($tanggalBulan == '8'){
                $bulan = "Agustus";
            }else if($tanggalBulan == '9'){
                $bulan = "September";
            }else if($tanggalBulan == '10'){
                $bulan = "Oktober";
            }else if($tanggalBulan == '11'){
                $bulan = "November";
            }else if($tanggalBulan == '12'){
                $bulan = "Desember";
            }

            $pdfKartuUjian = PDF::LoadView('panitia.pmb.jadwal_ujian.kartu_ujian', compact(
                'kodePendaftaran',
                'nama',
                'tahun',
                'kotaLahir',
                'bulan',
                'tahun',
                'tanggal',
                'tahunAjaran',
                'foto4x6'
            ));

            $fileKartuUjian = $pdfKartuUjian->save(public_path("/files/Kartu Ujian | ".$item->nama."-".$item->kode_pendaftaran.".pdf"));

            $realFileKartuUjian = public_path("/files/Kartu Ujian | ".$item->nama."-".$item->kode_pendaftaran.".pdf");

            $fileNameKartuUjian = "Kartu Ujian | ".$item->nama."-".$item->kode_pendaftaran.".pdf";

            $sendEmail = Mail::to($item->email)->send(new JadwalUjian(
                $item->nama,
                $item->kode_pendaftaran,
                $password,
                $tanggalMulaiUjian,
                $tanggalSelesaiUjian,
                $realFileKartuUjian,
                $fileNameKartuUjian,
                $durasi
            ));
        }

        $dataJadwalUjian = [
            'status' => 1
        ];

        $updateJadwalUjian = $this
            ->jadwalUjianRepo
            ->updateJadwalUjianData($dataJadwalUjian, $id);

        return redirect('/panitia/pmb/jadwal-ujian')
            ->with([
                'notification' => 'Email berhasil dibroadcast'
            ]);
    }

    public function checkPeserta($kodeJurusan, $kodeGelombang, $statusPendaftaran)
    {
        $calonMahasiswa = $this
            ->calonMahasiswaRepo
            ->getAllDayaForJadwalUjian($kodeJurusan, $kodeGelombang, $statusPendaftaran);

        $total = $calonMahasiswa->count();

        return response()
            ->json([
                'total' => $total,
                'data' => $calonMahasiswa
            ]);
    }

    public function checkUbahPeserta($kodeJurusan, $kodeGelombang, $statusPendaftaran)
    {
        $calonMahasiswa = $this
            ->calonMahasiswaRepo
            ->getAllDayaForJadwalUjianForEdit(
                $kodeJurusan,
                $kodeGelombang,
                $statusPendaftaran
            );

        $total = $calonMahasiswa->count();

        return response()
            ->json([
                'total' => $total,
                'data' => $calonMahasiswa
            ]);
    }
}
