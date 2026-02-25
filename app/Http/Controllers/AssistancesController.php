<?php

namespace App\Http\Controllers;

use App\Models\Assistances;
use App\Models\Assistance;
use App\Models\FarmerInformation;
use App\Models\MainLivelihood;
use App\Models\FarmingType;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Str;
use Inertia\Inertia;

class AssistancesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request){
        $paginate = $request->paginate ? intval($request->paginate): 10;
        $assistances = Assistances::from('assistances as a')
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
            })->paginate($paginate);
        
        $assistances->appends(['paginate' => $paginate]);

        if($request->paginate == 'All'){
            $assistances = Assistances::from('assistances as a')
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
            })->get();
            $assistances->all();
        }
        
        $farmers = $this->get_all_farmers();
        $assistance = $this->get_all_assistance();

        return Inertia::render(
            'Assistances/Index', [ 'assistances' => $assistances, 'filter' => $request, 'farmer' => $farmers, 'assistance' => $assistance ]
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Assistances $assistances)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Assistances $assistances)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Assistances $assistances)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Assistances $assistances)
    {
        //
    }

    public function get_all_farmers() {
        $farmer = FarmerInformation::
            select(DB::raw("a.id, UPPER(CONCAT(
                a.firstname, ' ',
                IF(a.middlename IS NOT NULL AND a.middlename != '', CONCAT(LEFT(a.middlename, 1), '. '), ''),
                a.lastname,
                IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ', a.suffix), '')
            )) AS text, b.id as farmer_profile_id, b.main_livelihood"))
            ->leftJoin('farm_profile as b', 'b.farmer_id', '=', 'a.id')
            ->where('is_archived', 0)
            ->from('farmer_information as a')
            ->get();
        $farmerCollection = collect($farmer);

        foreach ($farmerCollection as $farmer) {
            $meta = @unserialize($farmer->main_livelihood);

            if (is_array($meta) && $meta) {
                $main_livelihood = array(
                    'main_livelihood' => $meta,
                    'contents' => $this->get_main_livelihood_content($farmer->farmer_profile_id, $meta)
                );

                $farmer->main_livelihood = $main_livelihood;
            }
        }

        return $farmerCollection;
    }

    public function get_main_livelihood_content($id, $type){
        $content = MainLivelihood::select(DB::raw('meta, value'))
            ->where('farmer_profile_id', $id)
            ->where('main_livelihood', $type)
            ->get();
        
        $contentCollection = collect($content);

        foreach ($contentCollection as $content) {
            $type = FarmingType::select('name')->where("id", $content->value)->first();
            $content->value = $type->name ?? $content->value;
        }

        return $contentCollection;
    }

    public function get_all_assistance() {
        $allassistance = Assistance::select(DB::raw('livelihoods, id, UPPER(name) as text'))->get();
        $allassistanceCollection = collect($allassistance);

        foreach($allassistanceCollection as $key => $rs) {
            $meta = @unserialize($rs->livelihoods) ?? array();
            $rs->livelihoods = $meta;
        }

        return $allassistanceCollection;
    }
}
