<?php

namespace App\Mail;

use App\Models\Transaksi;
use App\Models\Produk;
use App\Models\Penitip;
use Illuminate\Bus\Queueable;
// Hapus 'ShouldQueue' jika tidak menggunakan antrian untuk contoh sederhana ini
// use Illuminate\Contracts\Queue\ShouldQueue; 
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotifyPenitipOrderPrepared extends Mailable // Hapus 'implements ShouldQueue' jika tidak pakai queue
{
    use Queueable, SerializesModels; // Queueable tetap ada tidak masalah, tapi tidak akan di-queue jika tidak dipanggil dengan ->queue()

    public Transaksi $transaksi;
    public Produk $produk;    // Produk spesifik yang terjual
    public Penitip $penitip;  // Penitip produk ini

    /**
     * Create a new message instance.
     */
    public function __construct(Transaksi $transaksi, Produk $produk, Penitip $penitip)
    {
        $this->transaksi = $transaksi;
        $this->produk = $produk;
        $this->penitip = $penitip;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Subjek email
        $subject = "Produk Anda \"{$this->produk->namaProduk}\" Telah Dipesan! (Order #" . ($this->transaksi->nomor_transaksi_unik ?? $this->transaksi->idTransaksi) . ")";
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Ambil data yang diperlukan untuk kondisi
        $metodePengiriman = $this->transaksi->metodePengiriman; // Asumsi ada kolom 'metodePengiriman' di tabel 'transaksis'
        $kategoriProduk = $this->produk->kategori; // Asumsi ada kolom 'kategori' di tabel 'produks'
        $alamatLengkapPembeli = null;

        // Jika metode pengiriman 'kurir' dan ada alamat pengiriman yang tersimpan
        // Anda mungkin perlu memuat relasi alamatPengiriman jika belum ada
        // $this->transaksi->loadMissing('alamatPengiriman'); // Contoh memuat relasi jika belum ada
        if ($metodePengiriman === 'kurir') { // Asumsi ada relasi alamatPengiriman di model Transaksi
            $alamatLengkapPembeli = $this->transaksi->alamatPengiriman->alamat . ', ' . $this->transaksi->alamatPengiriman->kota; // Sesuaikan dengan field Anda
        } elseif ($metodePengiriman === 'ambil_sendiri') {
            $alamatLengkapPembeli = 'Diambil Sendiri oleh Pembeli';
        }


        return new Content(
            markdown: 'emails.orders.penitip_notification',
            with: [
                'namaPenitip' => $this->penitip->nama,
                'namaProduk' => $this->produk->namaProduk,
                'idTransaksi' => $this->transaksi->nomor_transaksi_unik ?? $this->transaksi->idTransaksi,
                'kuantitasTerjual' => 1,
                'metodePengiriman' => $metodePengiriman, // KIRIM DATA INI KE VIEW
                'kategoriProduk' => $kategoriProduk,   // KIRIM DATA INI KE VIEW
                'alamatPengiriman' => $alamatLengkapPembeli, // KIRIM DATA INI KE VIEW (bisa null)
                'detailTransaksiUrl' => url('/admin/transaksi/' . $this->transaksi->idTransaksi) // Sesuaikan URL
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}