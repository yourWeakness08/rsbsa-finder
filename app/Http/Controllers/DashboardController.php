<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Assistance;
use App\Models\Assistances;
use App\Models\FarmProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller{
    public function assistanceStatusChart() {
        $data = Assistances::selectRaw("LOWER(status) as status, COUNT(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = [
            'pending' => 0,
            'approved' => 0,
            'disapproved' => 0,
            'cancelled' => 0,
        ];

        foreach ($data as $status => $count) {
            $statuses[$status] = $count;
        }

        return response()->json($statuses);
    }

    public function assistancesByMonth(){
        $status = request('status');
        $year   = (int) (request('year') ?? date('Y'));

        $query = Assistances::query()
            ->selectRaw("MONTH(created_at) as m, COUNT(*) as total")
            ->whereYear('created_at', $year);

        // optional status filter
        if ($status && strtolower($status) !== 'all') {
            $query->whereRaw('LOWER(status) = ?', [strtolower($status)]);
        }

        $rows = $query
            ->groupBy('m')
            ->orderBy('m')
            ->get()
            ->keyBy('m');

        $labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $data   = [];

        for ($m = 1; $m <= 12; $m++) {
            $data[] = (int) ($rows[$m]->total ?? 0);
        }

        return response()->json([
            'labels' => $labels,
            'data'   => $data,
            'year'   => $year,
            'status' => $status ?? 'all',
        ]);
    }

    public function livelihoodTotals() {
        $profiles = FarmProfile::select('main_livelihood')->get();

        $totals = [
            'farmer' => 0,
            'farm_worker' => 0,
            'fisherfolks' => 0,
            'agri_youth' => 0,
        ];

        foreach ($profiles as $profile) {

            $livelihoods = @unserialize($profile->main_livelihood);

            if (is_array($livelihoods)) {
                foreach ($livelihoods as $livelihood) {
                    if (array_key_exists($livelihood, $totals)) {
                        $totals[$livelihood]++;
                    }
                }
            }
        }

        return response()->json($totals);
    }

    public function farmersByBrgyFromFarmerInfo(){
        $city = request('city'); // optional

        $q = DB::table('farmer_information as fi')
            ->selectRaw("TRIM(UPPER(fi.brgy)) as brgy, COUNT(DISTINCT fi.id) as total")
            ->where('fi.is_archived', 0)
            ->whereNotNull('fi.brgy')
            ->where('fi.brgy', '!=', '');

        if ($city) {
            $q->whereRaw("TRIM(UPPER(fi.city)) = ?", [trim(strtoupper($city))]);
        }

        $rows = $q->groupBy('brgy')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'labels' => $rows->pluck('brgy')->values(),
            'data'   => $rows->pluck('total')->map(fn($v) => (int)$v)->values(),
        ]);
    }

    public function dashboardData(){
        $statusFilter = request('status');
        $year = (int) (request('year') ?? date('Y'));
        $city = request('city');

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Assistance Status Totals
        |--------------------------------------------------------------------------
        */
        $statusData = Assistances::selectRaw("LOWER(status) as status, COUNT(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = [
            'pending' => 0,
            'approved' => 0,
            'disapproved' => 0,
            'cancelled' => 0,
        ];

        foreach ($statusData as $status => $count) {
            if (isset($statuses[$status])) {
                $statuses[$status] = (int) $count;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Assistances By Month
        |--------------------------------------------------------------------------
        */
        $monthlyQuery = Assistances::query()
            ->selectRaw("MONTH(created_at) as m, COUNT(*) as total")
            ->whereYear('created_at', $year);

        if ($statusFilter && strtolower($statusFilter) !== 'all') {
            $monthlyQuery->whereRaw('LOWER(status) = ?', [strtolower($statusFilter)]);
        }

        $monthlyRows = $monthlyQuery
            ->groupBy('m')
            ->orderBy('m')
            ->get()
            ->keyBy('m');

        $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $monthData = [];

        for ($m = 1; $m <= 12; $m++) {
            $monthData[] = (int) ($monthlyRows[$m]->total ?? 0);
        }

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Livelihood Totals
        |--------------------------------------------------------------------------
        */
        $livelihoodTotals = [
            'farmer' => 0,
            'farm_worker' => 0,
            'fisherfolks' => 0,
            'agri_youth' => 0,
        ];

        FarmProfile::select('main_livelihood')
            ->whereNotNull('main_livelihood')
            ->chunk(500, function ($profiles) use (&$livelihoodTotals) {
                foreach ($profiles as $profile) {
                    $arr = @unserialize($profile->main_livelihood);
                    if (!is_array($arr)) continue;

                    foreach ($arr as $code) {
                        if (isset($livelihoodTotals[$code])) {
                            $livelihoodTotals[$code]++;
                        }
                    }
                }
            });

        /*
        |--------------------------------------------------------------------------
        | 4️⃣ Farmers By Barangay (Based on Farmer Info)
        |--------------------------------------------------------------------------
        */
        $brgyQuery = DB::table('farmer_information as fi')
            ->selectRaw("TRIM(UPPER(fi.brgy)) as brgy, COUNT(DISTINCT fi.id) as total")
            ->where('fi.is_archived', 0)
            ->whereNotNull('fi.brgy')
            ->where('fi.brgy', '!=', '');

        if ($city) {
            $brgyQuery->whereRaw("TRIM(UPPER(fi.city)) = ?", [trim(strtoupper($city))]);
        }

        $brgyRows = $brgyQuery
            ->groupBy('brgy')
            ->orderByDesc('total')
            ->get();

        $brgyData = [
            'labels' => $brgyRows->pluck('brgy')->values(),
            'data'   => $brgyRows->pluck('total')->map(fn($v) => (int)$v)->values(),
        ];

        /*
        |--------------------------------------------------------------------------
        | 🔥 Final Response
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'assistance_status' => $statuses,
            'assistances_by_month' => [
                'labels' => $monthLabels,
                'data'   => $monthData,
                'year'   => $year,
                'status' => $statusFilter ?? 'all',
            ],
            'livelihood_totals' => $livelihoodTotals,
            'farmers_by_brgy'   => $brgyData,
        ]);
    }
}
