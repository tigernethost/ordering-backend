<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class SalesReportExport implements FromArray
{
    protected $salesData;
    protected $startDate;
    protected $endDate;

    public function __construct($salesData, $startDate, $endDate)
    {
        $this->salesData = $salesData;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Return the filtered data as an array.
     */
    public function array(): array
    {
        $data = [];

        // Add the date range as the first row
        $data[] = [
            'Date Range:',
            $this->startDate . ' - ' . $this->endDate,
        ];

        // Add another row for the headings
        $data[] = [
            '#',
            'Branch',
            'Total Sales (PHP)',
            'Total Orders',
        ];

        // Add the sales data
        $counter = 1;
        foreach ($this->salesData as $item) {
            $data[] = [
                $counter++, // Row number
                $item['branch_name'] ?? 'Unknown Branch',
                number_format($item['total_sales'], 2), // Total sales formatted as currency
                $item['total_orders'], // Total orders count
            ];
        }

        return $data;
    }
}
