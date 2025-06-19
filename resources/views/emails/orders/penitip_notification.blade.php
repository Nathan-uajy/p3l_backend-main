@component('mail::message')
# Produk Anda Dipesan dan Siap Disiapkan

Halo **{{ $namaPenitip }}**,

Kabar baik! Produk titipan Anda berikut ini telah berhasil dipesan dan pembayarannya telah diverifikasi:

**Nama Produk:** {{ $namaProduk }}
**Jumlah Terjual:** {{ $kuantitasTerjual }} item
**Nomor Transaksi Terkait:** {{ $idTransaksi }}

**Instruksi Persiapan:**

@if($metodePengiriman == 'kurir')
Mohon siapkan produk untuk dijemput oleh kurir. Pastikan produk dikemas dengan aman.
    @if($alamatPengiriman)
Alamat Tujuan Pengiriman:
{{ $alamatPengiriman }}
    @else
Alamat Tujuan Pengiriman akan diinformasikan lebih lanjut oleh tim kami.
    @endif
@elseif($metodePengiriman == 'ambil_sendiri')
Produk ini akan **diambil sendiri** oleh pembeli di gudang. Mohon siapkan produk agar mudah ditemukan. Anda akan dihubungi lebih lanjut mengenai detail pengambilan jika diperlukan.
@else
Metode pengiriman/pengambilan akan diinformasikan lebih lanjut.
@endif

{{-- Contoh kondisi berdasarkan kategori produk --}}
@if($kategoriProduk == 'Elektronik')

**Catatan Tambahan untuk Produk Elektronik:** Pastikan semua aksesoris lengkap dan segel (jika ada) dalam kondisi baik.
@elseif($kategoriProduk == 'Pecah Belah')

**Catatan Tambahan untuk Produk Pecah Belah:** Harap kemas produk ini dengan sangat hati-hati menggunakan bubble wrap ekstra dan label "FRAGILE".
@endif

Anda dapat melihat detail transaksi ini lebih lanjut melalui panel admin Anda.
@component('mail::button', ['url' => $detailTransaksiUrl, 'color' => 'success'])
Lihat Detail Transaksi
@endcomponent

Terima kasih atas kerjasamanya.

Salam,
Tim {{ config('app.name') }}
@endcomponent