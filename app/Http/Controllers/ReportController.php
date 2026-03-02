<?php

namespace App\Http\Controllers;

use App\Models\Assistance;
use App\Models\AssistanceHistory;
use App\Models\FarmerInformation;
use App\Models\OthersFarmerInformation;
use App\Models\FarmParcel;
use App\Models\FarmParcelInformation;
use App\Models\User;
use App\Models\FarmProfile;
use App\Models\MainLivelihood;
use App\Models\FarmingType;
use App\Models\Attachments;
use App\Models\CorrectedVerified;
use App\Models\ActivityLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use Illuminate\Support\Str;
use Inertia\Inertia;

class ReportController extends Controller{
    public function activities(Request $request) {
        $paginate = $request->paginate ? intval($request->paginate): 10;

        $_activity = ActivityLogs::from('activity_logs as a')
            ->select(DB::raw("a.*, CONCAT(b.firstname, ' ', b.lastname) as user_name"))
            ->leftJoin('users as b', 'b.id', '=', 'a.user_id')
            ->orderBy('created_at', 'desc')
            ->where( function($query) use ($request) {
                if ($request->search) {
                    $query->where('b.firstname', 'like', '%'.$request->search.'%')
                    ->orWhere('b.lastname', 'like', '%'.$request->search.'%')
                    ->orWhere('a.message', 'like', '%'.$request->search.'%');
                }
            });

        if (isset($request->user) && $request->user) {
            $_activity->where('a.user_id', $request->user);
        }

        if (isset($request->from) && isset($request->to)) {
            $_activity->where( function($query) use ($request) {
                $query->where(DB::raw('DATE(a.created_at)'), '>=', date('Y-m-d', strtotime($request->from)));
                $query->where(DB::raw('DATE(a.created_at)'), '<=', date('Y-m-d', strtotime($request->to)));
            });
        }

        $activity = $_activity->paginate($paginate);
        $activity->appends(['paginate' => $paginate]);

        if($request->paginate == 'All'){
            $_activity = ActivityLogs::from('activity_logs as a')
            ->select(DB::raw("a.*, CONCAT(b.firstname, ' ', b.lastname) as user_name"))
            ->leftJoin('users as b', 'b.id', '=', 'a.user_id')
            ->orderBy('created_at', 'desc')
            ->where( function($query) use ($request) {
                if ($request->search) {
                    $query->where('b.firstname', 'like', '%'.$request->search.'%')
                    ->orWhere('b.lastname', 'like', '%'.$request->search.'%')
                    ->orWhere('a.message', 'like', '%'.$request->search.'%');
                }
            });
            
            if (isset($request->user) && $request->user) {
                $_activity->where('a.user_id', $request->user);
            }

            if (isset($request->from) && isset($request->to)) {
                $_activity->where( function($query) use ($request) {
                    $query->where(DB::raw('DATE(a.created_at)'), '>=', date('Y-m-d', strtotime($request->from)));
                    $query->where(DB::raw('DATE(a.created_at)'), '<=', date('Y-m-d', strtotime($request->to)));
                });
            }

            $activity = $_activity->get();
            $activity->all();
        }

        $users = User::select(DB::raw("id, UPPER(CONCAT(firstname, ' ', lastname)) as text"))->where('is_archived', 0)->get();

        return Inertia::render(
            'Reports/Activities', ['reports' => $activity, 'users' => $users, 'filter' => $request]
        );
    }

    public function registered(Request $request) {
        return Inertia::render(
            'Reports/Registered', ['reports' => array()]
        );
    }

    public function farming(Request $request) {
        return Inertia::render(
            'Reports/Farming', ['reports' => array()]
        );
    }

    //not done
    public function livelihood(Request $request) {
        $paginate = $request->paginate ? intval($request->paginate): 10;
        $livelihood = $request->livelihood ? $request->livelihood : null;

        $livelihood = FarmerInformation::from('farmer_information as a')
            ->select(DB::raw("a.id, CONCAT(
                    a.firstname, ' ',
                    IF(a.middlename IS NOT NULL AND a.middlename != '', CONCAT(LEFT(a.middlename, 1), '. '), ''),
                    a.lastname,
                    IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ', a.suffix), '')
                ) AS name, b.main_livelihood"))
            ->leftJoin('farm_profile as b', 'b.farmer_id', '=', 'a.id')
            ->where( function($query) use ($request) {
                if ($request->search) {
                    $query->where('a.firstname', 'like', '%'.$request->search.'%')
                    ->orWhere('a.middlename', 'like', '%'.$request->search.'%')
                    ->orWhere('a.lastname', 'like', '%'.$request->search.'%');
                }
            })
            ->where( function($query) use ($livelihood) {
                if ($livelihood) {
                    $query->where('b.main_livelihood', 'like', '%"'.$livelihood.'"%');
                }
            })
            ->where("a.is_archived", 0)
            ->paginate($paginate);
        $livelihood->appends(['paginate' => $paginate]);

        return Inertia::render(
            'Reports/Livelihood', ['reports' => $livelihood, 'filter' => $request]
        );
    }
}
