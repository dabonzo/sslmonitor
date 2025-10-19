<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Services\MonitoringReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonitoringReportController extends Controller
{
    public function __construct(
        protected MonitoringReportService $reportService
    ) {}

    /**
     * Export monitoring data as CSV
     */
    public function exportCsv(Monitor $monitor, Request $request): StreamedResponse
    {
        $startDate = Carbon::parse($request->input('start_date', now()->subDays(30)));
        $endDate = Carbon::parse($request->input('end_date', now()));

        $csv = $this->reportService->generateCsvExport($monitor, $startDate, $endDate);

        $filename = "monitor-{$monitor->id}-{$startDate->format('Y-m-d')}-to-{$endDate->format('Y-m-d')}.csv";

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Get summary report
     */
    public function summary(Monitor $monitor, Request $request)
    {
        $period = $request->input('period', '30d');

        return response()->json([
            'report' => $this->reportService->getSummaryReport($monitor, $period),
        ]);
    }

    /**
     * Get daily breakdown
     */
    public function dailyBreakdown(Monitor $monitor, Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', now()->subDays(30)));
        $endDate = Carbon::parse($request->input('end_date', now()));

        return response()->json([
            'breakdown' => $this->reportService->getDailyBreakdown($monitor, $startDate, $endDate),
        ]);
    }
}
