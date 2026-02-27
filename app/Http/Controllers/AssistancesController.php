<?php

namespace App\Http\Controllers;

use App\Models\Assistances;
use App\Models\Assistance;
use App\Models\FarmerInformation;
use App\Models\MainLivelihood;
use App\Models\AssistancesAttachments as Attachments;
use App\Models\FarmingType;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

use Illuminate\Support\Str;
use Inertia\Inertia;

class AssistancesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request){
        $paginate = $request->paginate ? intval($request->paginate): 10;

        $assistances = Assistances::leftJoin('users as b', 'b.id', '=', 'assistances.created_by')
            ->select(DB::raw("assistances.*, CONCAT(b.firstname, ' ', b.lastname) as created_name, CONCAT(
                    c.firstname, ' ',
                    IF(c.middlename IS NOT NULL AND c.middlename != '', CONCAT(LEFT(c.middlename, 1), '. '), ''),
                    c.lastname,
                    IF(c.suffix IS NOT NULL AND c.suffix != '', CONCAT(' ', c.suffix), '')
                ) AS name, d.name as assistance_name"))
            ->leftJoin('farmer_information as c', 'c.id', '=', 'assistances.farmer_id')
            ->leftJoin('assistance as d', 'd.id', '=', 'assistances.assistance_id')
            ->orderBy('assistances.created_at', 'desc')
            ->where( function($query) use ($request) {
                if ($request->search) {
                    $query->where('c.firstname', 'like', '%'.$request->search.'%')
                    ->orWhere('c.lastname', 'like', '%'.$request->search.'%')
                    ->orWhere('c.middlename', 'like', '%'.$request->search.'%');
                }
            })->paginate($paginate);
        $assistances->appends(['paginate' => $paginate]);

        if($request->paginate == 'All'){
            $assistances = Assistances::LeftJoin('users as b', 'b.id', '=', 'assistances.created_by')
            ->select(DB::raw("assistances.*, CONCAT(b.firstname, ' ', b.lastname) as created_name, CONCAT(
                    c.firstname, ' ',
                    IF(c.middlename IS NOT NULL AND c.middlename != '', CONCAT(LEFT(c.middlename, 1), '. '), ''),
                    c.lastname,
                    IF(c.suffix IS NOT NULL AND c.suffix != '', CONCAT(' ', c.suffix), '')
                ) AS name, d.name as assistance_name"))
            ->leftJoin('farmer_information as c', 'c.id', '=', 'assistances.farmer_id')
            ->leftJoin('assistance as d', 'd.id', '=', 'assistances.assistance_id')
            ->where(function($query) use($request){
                if ($request->search) {
                    $query->where('c.firstname', 'like', '%'.$request->search.'%')
                    ->orWhere('c.lastname', 'like', '%'.$request->search.'%')
                    ->orWhere('c.middlename', 'like', '%'.$request->search.'%');
                }
            })
            ->orderBy('assistances.created_at', 'desc')
            ->get();
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
    public function store(Request $request, Assistances $assistances) {
        $user_id = $request->user_id;
        $state = false;

        if ($request->farmer && $request->assistance && $request->remarks) {
            $reference = $this->generateReferenceNo();

            $created = Assistances::create([
                'farmer_id' => $request->farmer,
                'assistance_id' => $request->assistance,
                'reference_no' => $reference,
                'purpose' => trim($request->remarks),
                'livelihood' => $request->livelihood,
                'created_by'=>$user_id,
                'uuid'=>Str::random(12)
            ]);

            if ($created) {
                $id = $created->id;
                $state = true;

                if ($request->file('attachments') !== null) {
                    $attachments = (object) $request->file('attachments');

                    foreach($attachments as $attachment) {
                        $_filename = $attachment->getClientOriginalName();
                        $_destinationPath = "uploads/assistances/assistance_".$id;

                        if(!file_exists(public_path($_destinationPath))){ 
                            File::makeDirectory(public_path($_destinationPath), 0777, true);
                        }

                        $_tempFilePath = $_destinationPath."/".$_filename;

                        if(file_exists(public_path($_tempFilePath))){
                            unlink(public_path($_tempFilePath));
                        }

                        if(!file_exists(public_path($_tempFilePath))){
                            $_fileMoved = $attachment->move($_destinationPath, $_filename);

                            if($_fileMoved) {
                                Attachments::create([
                                    'farmer_id' => $id,
                                    'filename' => $_filename,
                                    'filepath' => $_destinationPath,
                                    'uuid' => Str::random(12)
                                ]);
                            }
                        }
                    }
                }
            }
        }

        return redirect()->back()->with('response', [
            'state' => $state
        ]);
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
                    'livelihood' => $meta,
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

    function generateReferenceNo() {
        return DB::transaction(function () {

            $year = Carbon::now()->format('y');
            $lastRecord = DB::table('assistances')
                ->whereYear('created_at', Carbon::now()->year)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if ($lastRecord && preg_match('/RSS-\d{2}-(\d+)/', $lastRecord->reference_no, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }

            $sequence = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            return "RSS-$year-$sequence";
        });
    }
}
