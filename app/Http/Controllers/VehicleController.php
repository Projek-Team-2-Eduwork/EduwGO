<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;

class VehicleController extends Controller
{
    public function show(Vehicle $vehicle)
    {
        return view('kendaraan.show', compact('vehicle'));
    }
}
