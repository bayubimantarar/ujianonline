<?php

namespace App\Repositories;

use App\Soal;
use App\Hasil;
use App\Jawaban;
use App\Mahasiswa;

class JawabanRepository
{
    public function storeJawabanData($data)
    {
        $storeJawaban = Jawaban::insert($data);
        
        return $storeJawaban;
    }

    public function checkMahasiswaHasExam($nim, $kodesoal)
    {
        $getJawaban = Jawaban::where([
            ['nim', '=', $nim],
            ['kode_soal', '=', $kodesoal]
        ])->first();

        return $getJawaban;
    }

    public function getSingleDataByDosenForPeriksa($kodesoal)
    {
        $getJawaban = Jawaban::join('mahasiswa', 'jawaban.nim', '=', 'mahasiswa.nim')
        ->join('soal', 'jawaban.kode_soal', '=', 'soal.kode')
        ->join('hasil', 'jawaban.nim', '=', 'hasil.nim')
        ->select('jawaban.*','soal.kode', 'mahasiswa.nim', 'mahasiswa.nama AS nama_mahasiswa', 'hasil.status', 'hasil.kode_soal')
        ->where('soal.kode', '=', $kodesoal)
        ->where('hasil.kode_soal', '=', $kodesoal)
        ->groupBy('mahasiswa.nim')
        ->get();

        return $getJawaban;
    }

    public function getSingleDataByDosenForPeriksaJawaban($nim, $kodesoal)
    {
        $getJawaban = Jawaban::join('mahasiswa', 'jawaban.nim', '=', 'mahasiswa.nim')
            ->join('soal', 'jawaban.kode_soal', '=', 'soal.kode')
            ->join('pertanyaan', 'jawaban.nomor_pertanyaan', '=', 'pertanyaan.id')
            ->select('jawaban.*', 'soal.kode', 'pertanyaan.pertanyaan', 'pertanyaan.gambar', 'pertanyaan.pilihan_a', 'pertanyaan.pilihan_b', 'pertanyaan.pilihan_c', 'pertanyaan.pilihan_d', 'pertanyaan.pilihan_e','pertanyaan.jenis_pertanyaan', 'pertanyaan.jawaban_pilihan AS jawaban_pilihan_pertanyaan')
            ->where('jawaban.nim', '=', $nim)
            ->where('jawaban.kode_soal', '=', $kodesoal)
            ->get();

        return $getJawaban;
    }
}
