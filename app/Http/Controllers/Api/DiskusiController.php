<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diskusi;
use App\Models\Pembeli;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DiskusiController extends Controller
{
    // Menampilkan semua diskusi
    public function index()
    {
        $diskusis = Diskusi::with(['pembeli', 'pegawai'])->get()->map(function ($diskusi) {
            return [
                'id' => $diskusi->diskusiID,
                'isi' => $diskusi->isi,
                'tanggal' => $diskusi->tanggal,
                // 'penulis' => $diskusi->penulis,
                // 'role' => $diskusi->role,
                'produkID' => $diskusi->produkID
            ];
        });

        return response()->json($diskusis);
    }

    // public function diskusiProduk($id) {
    // // Ambil diskusi yang punya produkID = $id
    //     $diskusis = Diskusi::where('produkID', $id)
    //         // ->with('pembeli') // Jika ingin juga data pembeli
    //         ->get();

    //     $pembeli = Pembeli::where('pembeliID', $diskusis->pembeliID)->first();
    //     $pegawai = Pegawai::where('pegawaiID', $diskusis->pegawaiID)->first();

    //     if ($diskusis->isEmpty()) {
    //         return response()->json(['message' => 'Tidak ada diskusi'], 404);
    //     }
        
    //     if ($diskusis->pembeliID->isEmpty()) {
    //         return response()->json($pegawai);
    //     }else {
    //         return response()->json($pembeli);
    //     }

    //     return response()->json($diskusis);
    // }


    public function diskusiProduk($id)
    {
        // Ambil diskusi yang punya produkID = $id
        $diskusis = Diskusi::where('produkID', $id)->get();

        if ($diskusis->isEmpty()) {
            return response()->json(['message' => 'Tidak ada diskusi'], 404);
        }

        $result = [];

        foreach ($diskusis as $diskusi) {
            $nama = 'Unknown';
            $role = 'unknown';

            if ($diskusi->pembeliID) {
                $pembeli = Pembeli::find($diskusi->pembeliID);
                if ($pembeli) {
                    $nama = $pembeli->nama;
                    $role = 'pembeli';
                }
            } elseif ($diskusi->pegawaiID) {
                $pegawai = Pegawai::find($diskusi->pegawaiID);
                if ($pegawai) {
                    $nama = $pegawai->nama;
                    $role = 'pegawai';
                }
            }

            $result[] = [
                'diskusiID' => $diskusi->diskusiID,
                'isi' => $diskusi->isi,
                'tanggal' => $diskusi->tanggal,
                'nama' => $nama,
                'role' => $role,
            ];
        }

        return response()->json($result);
    }

//     public function diskusiProduk($id)
// {
//     try {
//         \Log::info('Mencari diskusi dengan produkID: ' . $id);

//         // Debug raw query
//         $diskusis = \App\Models\Diskusi::where('produkID', 1)->get();

//         \Log::info('Hasil query:', $diskusis->toArray());

//         if ($diskusis->isEmpty()) {
//             return response()->json(['message' => 'Tidak ada diskusi'], 404);
//         }

//         return response()->json($diskusis);
//     } catch (\Exception $e) {
//         \Log::error('Error: ' . $e->getMessage());
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// }



    // Menambahkan diskusi baru
    public function store(Request $request)
    {
        $request->validate([
            'isi' => 'required|string',
            'produkID' => 'required|integer',
            'pembeliID' => 'nullable|integer',
            'pegawaiID' => 'nullable|integer'
        ]);

        $diskusi = Diskusi::create([
            'isi' => $request->isi,
            'tanggal' => now(),
            'produkID' => $request->produkID,
            'pembeliID' => $request->pembeliID,
            'pegawaiID' => $request->pegawaiID
        ]);

        return response()->json([
            'message' => 'Diskusi berhasil ditambahkan!',
            'diskusi' => $diskusi
        ]);
    }

    // 🔥 Menghapus diskusi
    public function destroy($id)
    {
        $diskusi = Diskusi::find($id);

        if (!$diskusi) {
            return response()->json([
                'message' => 'Diskusi tidak ditemukan!'
            ], 404);
        }

        $diskusi->delete();

        return response()->json([
            'message' => 'Diskusi berhasil dihapus.'
        ]);
    }
}
