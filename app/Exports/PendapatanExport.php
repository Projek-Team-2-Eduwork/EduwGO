<?php

namespace App\Exports;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PendapatanExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private $from, private $to, private $typeId) {}

    public function query(): Builder
    {
        $query = Payment::query()
            ->join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->where('payments.status', 'paid')
            ->where('bookings.status', '!=', 'cancelled')
            ->whereBetween('payments.paid_at', [$this->from, $this->to]);

        if ($this->typeId) {
            $query->where('vehicles.vehicle_type_id', $this->typeId);
        }

        return $query->select('payments.paid_at', 'payments.method', 'payments.amount', 'bookings.code', 'bookings.customer_name', 'vehicles.name as vehicle_name')
            ->orderBy('payments.paid_at');
    }

    public function headings(): array
    {
        return ['Kode', 'Motor', 'Penyewa', 'Tanggal', 'Metode', 'Nominal'];
    }

    public function map($row): array
    {
        return [
            $row->code,
            $row->vehicle_name,
            $row->customer_name,
            Carbon::parse($row->paid_at)->format('d/m/Y H:i'),
            ucwords(str_replace('_', ' ', $row->method)),
            (float) $row->amount,
        ];
    }
}
