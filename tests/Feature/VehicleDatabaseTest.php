<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_factory_creates_valid_model_with_type()
    {
        $vehicle = Vehicle::factory()->create();

        $this->assertNotNull($vehicle->type);
        $this->assertIsString($vehicle->type->name);
    }

    public function test_duplicate_plate_number_throws_database_exception()
    {
        Vehicle::factory()->create(['plate_number' => 'B 1234 XYZ']);

        $this->expectException(QueryException::class);

        // Memaksa insert duplikat di level database, mengabaikan validasi HTTP
        Vehicle::factory()->create(['plate_number' => 'B 1234 XYZ']);
    }
}
