<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderReportExport implements FromArray, WithHeadings
{
    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    /**
     * Return the filtered data as an array.
     */
    public function array(): array
    {
        $data = [];

        foreach ($this->orders->groupBy('branch.name') as $branchName => $orders) {
            // Add branch header
            $data[] = [$branchName, '', '', '', '', '', '', ''];

            foreach ($orders as $order) {
                $data[] = [
                    '', // Leave branch column empty for subsequent rows
                    $order->customer->full_name ?? 'N/A',
                    $order->status ? ucfirst($order->status) : 'N/A',
                    $order->order_type ? ucfirst($order->order_type) : 'N/A',
                    number_format($order->total_amount, 2),
                    $order->created_at->format('F j, Y, g:i A'),
                    $order->reservation ? $order->reservation->reservation_date->format('F j, Y, g:i A') : 'N/A',
                ];
            }
        }

        return $data;
    }

    /**
     * Define the headings for the Excel file.
     */
    public function headings(): array
    {
        return [
            'Branch',
            'Customer',
            'Order Status',
            'Order Type',
            'Total Amount',
            'Order Date',
            'Reservation Date',
        ];
    }
}
