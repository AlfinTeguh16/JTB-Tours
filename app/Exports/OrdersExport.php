<?php
namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;   
use Maatwebsite\Excel\Concerns\WithHeadings;     
use Maatwebsite\Excel\Concerns\WithMapping;      
use Maatwebsite\Excel\Concerns\ShouldAutoSize;   

class OrdersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $year;
    protected $month;

    public function __construct($year = null, $month = null)
    {
        $this->year  = $year;
        $this->month = $month;
    }

    public function collection()
    {
        $q = Order::with('product')->orderBy('pickup_time','desc');

        if ($this->year) $q->whereYear('pickup_time', $this->year);
        if ($this->month) $q->whereMonth('pickup_time', $this->month);

        return $q->get();
    }

    public function map($order): array
    {
        return [
            $order->id,
            $order->customer_name,
            $order->phone,
            $order->pickup_time?->format('Y-m-d H:i'),
            $order->arrival_time?->format('Y-m-d H:i'),
            $order->product?->name,
            $order->status,
            $order->passengers,
            $order->note,
        ];
    }

    public function headings(): array
    {
        return [
            'Order ID','Customer','Phone','Pickup Time','Arrival Time','Product','Status','Passengers','Note'
        ];
    }
}
