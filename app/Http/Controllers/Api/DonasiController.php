<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Donasi;

class DonasiController extends Controller
{
    public function index()
    {
        return Donasi::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggalDonasi' => 'required|date',
            'statusDonasi' => 'required|string|max:255',
        ]);

        return Donasi::create($validated);
    }
}

