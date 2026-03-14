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
        $paginate = $request->paginate ? intval($request->paginate): 10;

        $registered = FarmerInformation::from('farmer_information as a')
            ->select(DB::raw("
                a.id,
                a.ref_no,
                a.firstname,
                a.middlename,
                a.lastname,
                a.suffix,
                a.city,
                a.brgy,
                a.created_at,

                CONCAT(
                    a.firstname, ' ',
                    IF(a.middlename IS NOT NULL AND a.middlename != '', CONCAT(LEFT(a.middlename,1),'. '),''),
                    a.lastname,
                    IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ',a.suffix),'')
                ) as name,

                CONCAT(b.firstname,' ',b.lastname) as created_name
            "))
            ->leftJoin('users as b','b.id','=','a.created_by')
            ->when($request->search, function($q) use ($request){
                $q->where(function($query) use ($request){
                    $query->where('a.firstname','like','%'.$request->search.'%')
                    ->orWhere('a.lastname','like','%'.$request->search.'%')
                    ->orWhere('a.middlename','like','%'.$request->search.'%')
                    ->orWhere('a.ref_no','like','%'.$request->search.'%');
                });
            })
            ->when($request->city, function($q) use ($request){
                $q->where('a.city','like','%'.$request->city.'%');
            })
            ->when($request->brgy, function($q) use ($request){
                $q->where('a.brgy','like','%'.$request->brgy.'%');
            })
            ->whereBetween(DB::raw('DATE(a.created_at)'),[
                date('Y-m-d',strtotime($request->from)),
                date('Y-m-d',strtotime($request->to))
            ])
            ->where('a.is_archived',0)
            ->orderByRaw("
                CASE
                    WHEN a.brgy IS NULL OR a.brgy = '' THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('a.city','asc')
            ->orderBy('a.brgy','asc')
            ->orderBy('a.created_at','desc')
            ->paginate($paginate);
            $registered->appends(['paginate' => $paginate]);

            if ($request->paginate == 'All') {
                $registered = FarmerInformation::from('farmer_information as a')
            ->select(DB::raw("
                a.id,
                a.ref_no,
                a.firstname,
                a.middlename,
                a.lastname,
                a.suffix,
                a.city,
                a.brgy,
                a.created_at,

                CONCAT(
                    a.firstname, ' ',
                    IF(a.middlename IS NOT NULL AND a.middlename != '', CONCAT(LEFT(a.middlename,1),'. '),''),
                    a.lastname,
                    IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ',a.suffix),'')
                ) as name,

                CONCAT(b.firstname,' ',b.lastname) as created_name
            "))
            ->leftJoin('users as b','b.id','=','a.created_by')
            ->when($request->search, function($q) use ($request){
                $q->where(function($query) use ($request){
                    $query->where('a.firstname','like','%'.$request->search.'%')
                    ->orWhere('a.lastname','like','%'.$request->search.'%')
                    ->orWhere('a.middlename','like','%'.$request->search.'%')
                    ->orWhere('a.ref_no','like','%'.$request->search.'%');
                });
            })
            ->when($request->city, function($q) use ($request){
                $q->where('a.city','like','%'.$request->city.'%');
            })
            ->when($request->brgy, function($q) use ($request){
                $q->where('a.brgy','like','%'.$request->brgy.'%');
            })
            ->whereBetween(DB::raw('DATE(a.created_at)'),[
                date('Y-m-d',strtotime($request->from)),
                date('Y-m-d',strtotime($request->to))
            ])
            ->where('a.is_archived',0)
            ->orderByRaw("
                CASE
                    WHEN a.brgy IS NULL OR a.brgy = '' THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('a.city','asc')
            ->orderBy('a.brgy','asc')
            ->orderBy('a.created_at','desc')
            ->get();
            $registered->all();
        }

        return Inertia::render(
            'Reports/Registered', ['reports' => $registered, 'filter' => $request]
        );
    }

    public function farming(Request $request) {
        return Inertia::render(
            'Reports/Farming', ['reports' => array()]
        );
    }

    public function livelihood(Request $request) {
        $paginate = $request->paginate ? intval($request->paginate): 10;
        $filterLivelihood = $request->livelihood ? $request->livelihood : null;
        $search = $request->search ? $request->search : null;

        $livelihood = FarmerInformation::from('farmer_information as a')
            ->select(DB::raw("a.id, CONCAT(
                    a.firstname, ' ',
                    IF(a.middlename IS NOT NULL AND a.middlename != '', CONCAT(LEFT(a.middlename, 1), '. '), ''),
                    a.lastname,
                    IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ', a.suffix), '')
                ) AS name, b.main_livelihood, b.id as farm_profile_id"))
            ->leftJoin('farm_profile as b', 'b.farmer_id', '=', 'a.id')
            ->where( function($query) use ($request) {
                if ($request->search) {
                    $query->where('a.firstname', 'like', '%'.$request->search.'%')
                    ->orWhere('a.middlename', 'like', '%'.$request->search.'%')
                    ->orWhere('a.lastname', 'like', '%'.$request->search.'%');
                }
            })
            ->where( function($query) use ($filterLivelihood) {
                if ($filterLivelihood) {
                    $query->where('b.main_livelihood', 'like', '%"'.$filterLivelihood.'"%');
                }
            })
            ->where("a.is_archived", 0)
            ->paginate($paginate);
        $livelihood->appends(['paginate' => $paginate]);

        if ($filterLivelihood == 'All') {
            $livelihood = FarmerInformation::from('farmer_information as a')
                ->select(DB::raw("a.id, CONCAT(
                        a.firstname, ' ',
                        IF(a.middlename IS NOT NULL AND a.middlename != '', CONCAT(LEFT(a.middlename, 1), '. '), ''),
                        a.lastname,
                        IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ', a.suffix), '')
                    ) AS name, b.main_livelihood, b.id as farm_profile_id"))
                ->leftJoin('farm_profile as b', 'b.farmer_id', '=', 'a.id')
                ->where( function($query) use ($request) {
                    if ($request->search) {
                        $query->where('a.firstname', 'like', '%'.$request->search.'%')
                        ->orWhere('a.middlename', 'like', '%'.$request->search.'%')
                        ->orWhere('a.lastname', 'like', '%'.$request->search.'%');
                    }
                })
                ->where( function($query) use ($filterLivelihood) {
                    if ($filterLivelihood) {
                        $query->where('b.main_livelihood', 'like', '%"'.$filterLivelihood.'"%');
                    }
                })
                ->where("a.is_archived", 0)
                ->get();
                $livelihood->all();
        }

        $mainLivelihoodLabel = [
            'farmer'      => 'Farmer',
            'farm_worker' => 'Farm Worker / Laborer',
            'fisherfolks' => 'Fisherfolk',
            'agri_youth'  => 'Agri Youth',
        ];
        
        foreach($livelihood as $key => $value) {
            $meta = @unserialize($value->main_livelihood);

            $temp = array();
            if (is_array($meta) && $meta) {
                $main = MainLivelihood::select(DB::raw('main_livelihood, meta, value'))
                    ->where('farmer_profile_id', $value->farm_profile_id)
                    ->whereIn('main_livelihood', $meta)
                    ->get();

                foreach ($main as $k => $v) {
                    // only filter the generated livelihood
                    if ($filterLivelihood && $v->main_livelihood !== $filterLivelihood) {
                        continue;
                    }
                    
                    $temp[$v->main_livelihood]['livelihood'] = $mainLivelihoodLabel[$v->main_livelihood];
                    $temp[$v->main_livelihood]['content'][] = $this->get_farming_type($v->value);
                }
            }

            $value->main_livelihood = $temp;
        }

        return Inertia::render(
            'Reports/Livelihood', ['reports' => $livelihood, 'filter' => $request]
        );
    }

    function get_farming_type($val) {
        if (!is_numeric($val)) return $val;

        $farming_type = FarmingType::where('id', $val)->first();

        return $farming_type->name;
    }
}
