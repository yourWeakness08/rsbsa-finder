<?php

namespace App\Http\Controllers;

use App\Models\Assistance;
use App\Models\AssistanceHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Str;
use Inertia\Inertia;

class AssistanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request){
        $paginate = $request->paginate ? intval($request->paginate): 10;
        $assistance = Assistance::LeftJoin('users', 'users.id', '=', 'assistance.created_by')
        ->select(DB::raw('CONCAT(users.firstname, " ", users.lastname) as created_name, assistance.id, assistance.livelihoods, assistance.name, assistance.created_at'))
        ->where('assistance.is_archived', 0)
        ->where(function($query) use($request){
            if($request->search){
                $query->where('assistance.livelihoods', 'like', '%'.$request->search.'%')
                ->orWhere('assistance.name', 'like', '%'.$request->search.'%');
            }
        })
        ->orderBy('assistance.created_at', 'desc')
        ->paginate($paginate);
        $assistance->appends(['paginate' => $paginate]);
        
        if($request->paginate == 'All'){
            $assistance = Assistance::LeftJoin('users', 'users.id', '=', 'assistance.created_by')
            ->select(DB::raw('CONCAT(users.firstname, " ", users.lastname) as created_name, assistance.id, assistance.livelihoods, assistance.name, assistance.created_at'))
            ->where('assistance.is_archived', 0)
            ->where(function($query) use($request){
                if($request->search){
                    $query->where('assistance.livelihoods', 'like', '%'.$request->search.'%')
                    ->orWhere('assistance.name', 'like', '%'.$request->search.'%');
                }
            })
            ->orderBy('assistance.created_at', 'desc')
            ->get();
            $assistance->all();
        }

        foreach($assistance as $key => $rs) {
            $meta = @unserialize($rs->livelihoods) ? @unserialize($rs->livelihoods) : array('No livelihood found');
            $rs->livelihood = $meta;

            $meta = array_map(function ($v) {
                return preg_match('/[^a-zA-Z0-9]+/', $v)
                    ? trim(preg_replace('/[^a-zA-Z0-9]+/', ' ', $v))
                    : $v;
            }, $meta);

            $rs->livelihoods = implode(', ', $meta);
        }

        return Inertia::render(
            'Assistance/Index', [ 'assistance' => $assistance, 'filter' => $request ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $input = array(
            'livelihoods' => $request->livelihoods,
            'name' => $request->name,
        );

        $rules = [
            'livelihoods' => ['required', 'array'],
            'name' => ['required', 'string', 'max:255',],
        ];

        Validator::make($input, $rules)->validate();

        $created = Assistance::create([
            'livelihoods' => serialize($request->livelihoods),
            'name' => trim(strtolower($request->name)), 
            'created_by'=>$request->user_id,
            'uuid'=>Str::random(12)
        ]);

        $state = $created ? true : false;

        // return redirect()
        //     ->route('assistance.index')
        //     ->with([
        //         'response' => [
        //             'state' => $state
        //         ]
        //     ]);
        return redirect()->back()->with('response', [
            'state' => $state
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Assistance $assistance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Assistance $assistance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id, Assistance $assistance)
    {
        $input = array(
            'livelihoods' => $request->livelihoods,
            'name' => $request->name,
        );

        $rules = [
            'livelihoods' => ['required', 'array'],
            'name' => ['required', 'string', 'max:255',],
        ];

        Validator::make($input, $rules)->validate();

        $toUpdate = $assistance::find($id);
        $toUpdate->livelihoods = serialize($request->livelihoods);
        $toUpdate->name = trim(strtolower($request->name));
        $toUpdate->save();

        $state = $toUpdate ? true : false;

        // return redirect()
        //     ->route('assistance.index')
        //     ->with('response', [
        //         'state' => $state
        //     ]);
        return redirect()->back()->with('response', [
            'state' => $state
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Assistance $assistance)
    {
        //
    }

    public function archive_assistance(Request $request, $id, Assistance $assistance) {
        $resultset = array();

        if ($id) {
            $toArchive = $assistance::find($id);
            $toArchive->is_archived = 1;
            $toArchive->archived_by = $request->id;
            $toArchive->save();

            $_assistance = Assistance::paginate(25);
            $resultset["state"] = true;
            $resultset["updated"] = $toArchive;
            $resultset["assistance"] = $_assistance;
            $resultset['message'] = 'Assistance successfully deleted!';
        } else {
            $resultset["state"] = false;
            $resultset['message'] = 'Failed to delete assistance';
        }

        return response()->json($resultset);
    }

    public function save_assistance(Request $request, Assistance $assistance) {
        $id = $request->farmer_id;

        $query = AssistanceHistory::create([
            'farmer_id' => $id,
            'livelihood' => trim(strtolower($request->livelihood)),
            'assistance_id' => $request->assistance,
            'amount' => isset($request->amount) && $request->amount ? $request->amount : 0,
            'remarks' => trim(strtolower($request->remarks)),
            'created_by' => $request->user_id,
            'uuid' => Str::random(12)
        ]);

        $state = $query ? true : false;
        return redirect()
            ->route('farmers.view', $id)
            ->with([
                'response' => [
                    'state' => $state
                ]
            ]);
    }

    public function reports(Request $request) {

        // dd($request->from);
        $paginate = $request->paginate ? intval($request->paginate): 10;
        $assistanceHistory = AssistanceHistory::from('assistance_history as a')
            ->select(DB::raw("a.*, CONCAT(b.firstname, ' ', b.lastname) as created_name, CONCAT(
                    c.firstname, ' ',
                    IF(c.middlename IS NOT NULL AND c.middlename != '', CONCAT(LEFT(c.middlename, 1), '. '), ''),
                    c.lastname,
                    IF(c.suffix IS NOT NULL AND c.suffix != '', CONCAT(' ', c.suffix), '')
                ) AS name"))
            ->leftJoin('users as b', 'b.id', '=', 'a.created_by')
            ->leftJoin('farmer_information as c', 'c.id', '=', 'a.farmer_id')
            ->orderBy('created_at', 'desc')
            ->where( function($query) use ($request) {
                if ($request->search) {
                    $query->where('c.firstname', 'like', '%'.$request->search.'%')
                    ->orWhere('c.lastname', 'like', '%'.$request->search.'%')
                    ->orWhere('c.middlename', 'like', '%'.$request->search.'%');
                }
            })
            ->where( function($query) use ($request) {
                if (isset($request->livelihood) && $request->livelihood){
                    $query->where('a.livelihood', $request->livelihood);
                }
            })
            ->where( function($query) use ($request) {
                if (isset($request->assistance) && $request->assistance) {
                    $query->where('a.assistance_id', $request->assistance);
                }
            })
            ->where( function($query) use ($request) {
                $query->where(DB::raw('DATE(a.created_at)'), '>=', date('Y-m-d', strtotime($request->from)));
                $query->where(DB::raw('DATE(a.created_at)'), '<=', date('Y-m-d', strtotime($request->to)));
            })
            ->where( function($query) use ($request) {
                if($request->from == null && $request->to == null) {
                    $query->limit(0);
                }
            })->paginate($paginate);
        
        $assistanceHistory->appends(['paginate' => $paginate]);

        if($request->paginate == 'All'){
            $assistanceHistory = AssistanceHistory::from('assistance_history as a')
            ->select(DB::raw("a.*, CONCAT(b.firstname, ' ', b.lastname) as created_name, CONCAT(
                    c.firstname, ' ',
                    IF(c.middlename IS NOT NULL AND c.middlename != '', CONCAT(LEFT(c.middlename, 1), '. '), ''),
                    c.lastname,
                    IF(c.suffix IS NOT NULL AND c.suffix != '', CONCAT(' ', c.suffix), '')
                ) AS name"))
            ->leftJoin('users as b', 'b.id', '=', 'a.created_by')
            ->leftJoin('farmer_information as c', 'c.id', '=', 'a.farmer_id')
            ->orderBy('created_at', 'desc')
            ->where( function($query) use ($request) {
                if ($request->search) {
                    $query->where('c.firstname', 'like', '%'.$request->search.'%')
                    ->orWhere('c.lastname', 'like', '%'.$request->search.'%')
                    ->orWhere('c.middlename', 'like', '%'.$request->search.'%');
                }
            })
            ->where( function($query) use ($request) {
                if (isset($request->livelihood) && $request->livelihood){
                    $query->where('a.livelihood', $request->livelihood);
                }
            })
            ->where( function($query) use ($request) {
                if (isset($request->assistance) && $request->assistance) {
                    $query->where('a.assistance_id', $request->assistance);
                }
            })
            ->where( function($query) use ($request) {
                $query->where(DB::raw('DATE(a.created_at)'), '>=', date('Y-m-d', strtotime($request->from)));
                $query->where(DB::raw('DATE(a.created_at)'), '<=', date('Y-m-d', strtotime($request->to)));
            })
            ->where( function($query) use ($request) {
                if($request->from == null && $request->to == null) {
                    $query->limit(0);
                }
            })->get();
            $assistanceHistory->all();
        }

        $assistance = Assistance::select(DB::raw('livelihoods, id, name'))->where('is_archived', 0)->get();
        $assistanceCollection = collect($assistance);

        foreach($assistanceCollection as $key => $rs) {
            $meta = @unserialize($rs->livelihoods) ?? array();
            $rs->livelihoods = $meta;
        }

        $allassistance = Assistance::select(DB::raw('livelihoods, id, name'))->get();
        $allassistanceCollection = collect($allassistance);

        foreach($allassistanceCollection as $key => $rs) {
            $meta = @unserialize($rs->livelihoods) ?? array();
            $rs->livelihoods = $meta;
        }

        return Inertia::render(
            'Reports/Assistance', ['reports' => $assistanceHistory, 'assistance' => $assistanceCollection, 'allassistance' => $allassistanceCollection]
        );
    }
}
