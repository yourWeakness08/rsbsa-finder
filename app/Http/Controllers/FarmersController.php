<?php

namespace App\Http\Controllers;

use App\Models\Assistance;
use App\Models\Assistances;
use App\Models\FarmerInformation;
use App\Models\OthersFarmerInformation;
use App\Models\FarmParcel;
use App\Models\FarmParcelInformation;
use App\Models\User;
use App\Models\FarmProfile;
use App\Models\MainLivelihood;
use App\Models\FarmingType;
use App\Models\Attachments;
use App\Models\AssistancesAttachments;
use App\Models\CorrectedVerified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use App\Services\ActivityLogger;
use App\Services\AssistanceAutoApprovalService;

use Illuminate\Support\Str;
use Inertia\Inertia;

class FarmersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $assistanceService;

    public function __construct(AssistanceAutoApprovalService $assistanceService){
        $this->assistanceService = $assistanceService;
    }

    public function index(Request $request) {
        $paginate = $request->paginate ? intval($request->paginate): 10;

        $farmer = FarmerInformation::from('farmer_information as a')
            ->select(DB::raw("a.id, CONCAT(
                        a.firstname, ' ',
                        IF(a.middlename IS NOT NULL AND a.middlename != '', CONCAT(LEFT(a.middlename, 1), '. '), ''),
                        a.lastname,
                        IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ', a.suffix), '')
                    ) AS name, a.ref_no, a.mobile_no, a.farmer_image, a.created_at,  CONCAT(b.firstname, ' ', b.lastname) as created_name
                "))
            ->leftJoin('users as b', 'b.id', '=', 'a.created_by')
            ->where('a.is_archived', 0)
            ->where( function($query) use ($request) {
                if ($request->search) {
                    $query->where('a.firstname', 'like', '%'.$request->search.'%')
                    ->orWhere('a.lastname', 'like', '%'.$request->search.'%')
                    ->orWhere('a.middlename', 'like', '%'.$request->search.'%');
                }
            })
            ->orderBy('a.id', 'desc')
        ->paginate($paginate);
        $farmer->appends(['paginate' => $paginate]);

        if($request->paginate == 'All'){
            $farmer = FarmerInformation::from('farmer_information as a')
            ->select(DB::raw("a.id, CONCAT(
                        a.firstname, ' ',
                        IF(a.middlename IS NOT NULL AND a.middlename != '', CONCAT(LEFT(a.middlename, 1), '. '), ''),
                        a.lastname,
                        IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ', a.suffix), '')
                    ) AS name, a.ref_no, a.mobile_no, a.farmer_image, a.created_at,  CONCAT(b.firstname, ' ', b.lastname) as created_name
                "))
            ->leftJoin('users as b', 'b.id', '=', 'a.created_by')
            ->where('a.is_archived', 0)
            ->where( function($query) use ($request) {
                if ($request->search) {
                    $query->where('a.firstname', 'like', '%'.$request->search.'%')
                    ->orWhere('a.lastname', 'like', '%'.$request->search.'%')
                    ->orWhere('a.middlename', 'like', '%'.$request->search.'%');
                }
            })
            ->orderBy('a.id', 'desc')
            ->get();
            $farmer->all();
        }

        $farmer->transform(function ($farmers) {
            $farmers->farmer_image = $farmers->farmer_image && file_exists((public_path('uploads/farmers/farmer_'.$farmers->id.'/'.$farmers->farmer_image))) ? asset('uploads/farmers/farmer_'.$farmers->id.'/'.$farmers->farmer_image) : asset('images/male-farmer.png');
            
            return $farmers;   
        });

        return Inertia::render(
            'Farmers/Index', [ 'farmer' => $farmer, 'filter' => $request ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {
        
        $all = FarmingType::select(DB::raw('id, UPPER(name) as text, type'))
            ->where('is_archived', 0)
            ->orderBy('text', 'asc')
            ->get();

        // Define type labels
        $typeLabels = [
            1 => 'crops',
            2 => 'livestock',
            3 => 'poultry',
            4 => 'agri_fishery'
        ];

        // Group by numeric type and map to labels
        $grouped = collect($typeLabels)->mapWithKeys(function ($label, $type) use ($all) {
            return [
                $label => $all->where('type', $type)->values()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'text' => $item->text,
                    ];
                }),
            ];
        });

        return Inertia::render(
            'Farmers/Create', ['types' => $grouped]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, FarmerInformation $farmerInformation, ActivityLogger $activityLogger) {
        try {
            $user_id = $request->user_id;
            $contact = preg_replace('/\D/', '', $request->contact);
            $state = false;
            $id = 0;
    
            DB::transaction(function () use ($request, $user_id, $contact, $activityLogger, &$state, &$id) {
                // $checkRecords = DB::table('farmer_information')
                //     ->where('firstname', trim(strtolower($request->firstname)))
                //     ->where('middlename', $request->middlename ? trim(strtolower($request->middlename)) : null)
                //     ->where('lastname', trim(strtolower($request->lastname)))
                //     ->where('suffix', $request->suffix ? trim(strtolower($request->suffix)) : null)
                //     ->where('gender', trim(strtolower($request->gender)))
                //     ->where(DB::raw('DATE(date_of_birth)'), date('Y-m-d', strtotime($request->birth)))
                //     ->first();
                $checkRecords = false;
        
                if ($checkRecords) {
                    $state = false;
                } else {
                    $created = FarmerInformation::create([
                        'ref_no' => $request->ref_no,
                        'firstname' => trim(strtolower($request->firstname)),
                        'lastname' => trim(strtolower($request->lastname)),
                        'middlename' => $request->middlename ? trim(strtolower($request->middlename)) : null,
                        'suffix' => $request->suffix ? trim(strtolower($request->suffix)) : null, 
                        'gender' => trim(strtolower($request->gender)),
                        'lot_block_no' => trim(strtolower($request->lot)),
                        'street' => trim(strtolower($request->street)),
                        'brgy' => trim(strtolower($request->brgy)),
                        'city' => trim(strtolower($request->muni_city)),
                        'province' => trim(strtolower($request->province)),
                        'region' => trim(strtolower($request->region)),
                        'mobile_no' => $contact,
                        'date_of_birth' => date('Y-m-d', strtotime($request->birth)),
                        'place_of_birth' => trim(strtolower($request->birthplace)),
                        'religion' => $request->religion ? trim(strtolower($request->religion)) : null,
                        'civil_status' => trim(strtolower($request->civil_status)),
                        'spouse_name_if_married' => $request->spouse ? trim(strtolower($request->spouse)) : null, 
                        'created_by'=>$user_id,
                        'uuid'=> Str::random(12)
                    ]);
        
                    if ($created) {
                        $id = $created->id;
                        
                        $file = $request->file('image');
                        $filename = $file->getClientOriginalName();
        
                        $destinationPath = "uploads/farmers/farmer_".$id;
                        if(!file_exists(public_path($destinationPath))){ 
                            File::makeDirectory(public_path($destinationPath), 0777, true);
                        }
        
                        $tempFilePath = $destinationPath."/".$filename;
        
                        if(file_exists(public_path($tempFilePath))){
                            unlink(public_path($tempFilePath));
                        }
        
                        if(!file_exists(public_path($tempFilePath))){
                            $fileMoved = $file->move($destinationPath, $filename);
        
                            if($fileMoved) {
                                DB::table('farmer_information')
                                ->where('id', $id)
                                ->update(['farmer_image' => $filename, 'updated_at' => date("Y-m-d H:i:s")]);
                            }
                        }
        
        
                        $emer_contact = preg_replace('/\D/', '', $request->contact_emergency);
        
                        OthersFarmerInformation::create([
                            'farmer_id' => $id, 
                            'mothers_maiden_name' => trim(strtolower($request->mother_maiden_name)), 
                            'is_household_head' => $request->is_household_head, 
                            'name_if_not_head' => $request->household_head_name ? trim(strtolower($request->household_head_name)) : null, 
                            'is_not_head_relationship' => $request->head_relationship ? trim(strtolower($request->head_relationship)) : null, 
                            'no_of_living_members' => $request->members_no, 
                            'no_of_male' => $request->no_of_male, 
                            'no_of_female' => $request->no_of_female, 
                            'highest_formal_education' => $request->education, 
                            'is_pwd' => $request->is_pwd, 
                            'is_4ps' => $request->is_4ps, 
                            'has_gov_id' => $request->has_gov_id,
                            'id_no' => $request->gov_id_no ? trim(strtolower($request->gov_id_no)) : null, 
                            'is_farmer_coop_mem' => $request->is_farmer_member, 
                            'is_farmer_mem' => $request->asocc_name ? trim(strtolower($request->asocc_name)) : null, 
                            'contact_emergency' => trim(strtolower($request->person_emergency)), 
                            'contact_no' => $emer_contact, 
                            'uuid' => Str::random(12)
                        ]);
        
                        $farmProf = FarmProfile::create([
                            'farmer_id' => $id,
                            'main_livelihood' => serialize($request->main_livelihood), 
                            'farming_gross' => $request->farming_gross_income, 
                            'no_farming_gross' => $request->non_farming_gross_income, 
                            'farm_parcel_no' => in_array('farmer', $request->main_livelihood) || in_array('fisherfolks', $request->main_livelihood) ? $request->farm_parcel_no : 0, 
                            'is_arb' => $request->is_arb ? $request->is_arb : 0, 
                            'uuid'  => Str::random(12)
                        ]);
        
                        if ($farmProf) {
                            $farm_profile_id = $farmProf->id;
        
                            if ($request->farmer !== null && $request->farmer) {
                                if(count($request->farmer) > 0) {
                                    foreach($request->farmer as $farmer) {
                                        if ($farmer == 'rice' || $farmer == 'corn') {
                                            MainLivelihood::create([
                                                'farmer_profile_id' => $farm_profile_id,
                                                'main_livelihood' => 'farmer',
                                                'meta' => $farmer,
                                                'value' => $farmer,
                                                'uuid'  => Str::random(12)
                                            ]);
                                        }
                                    }
        
                                    if ($request->crops !== null && $request->crops) {
                                        if(count($request->crops) > 0) {
                                            foreach ($request->crops as $crops) {
                                                MainLivelihood::create([
                                                    'farmer_profile_id' => $farm_profile_id,
                                                    'main_livelihood' => 'farmer',
                                                    'meta' => 'crops',
                                                    'value' => $crops,
                                                    'uuid'  => Str::random(12)
                                                ]);
                                            }
                                        }
                                    }
                                    
                                    if ($request->livestock !== null && $request->livestock) {
                                        if(count($request->livestock) > 0) {
                                            foreach ($request->livestock as $livestock) {
                                                MainLivelihood::create([
                                                    'farmer_profile_id' => $farm_profile_id,
                                                    'main_livelihood' => 'farmer',
                                                    'meta' => 'livestock',
                                                    'value' => $livestock,
                                                    'uuid'  => Str::random(12)
                                                ]);
                                            }
                                        }
                                    }
                                    
                                    if ($request->poultry !== null && $request->poultry) {
                                        if(count($request->poultry) > 0) {
                                            foreach ($request->poultry as $poultry) {
                                                MainLivelihood::create([
                                                    'farmer_profile_id' => $farm_profile_id,
                                                    'main_livelihood' => 'farmer',
                                                    'meta' => 'poultry',
                                                    'value' => $poultry,
                                                    'uuid'  => Str::random(12)
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }
        
                            if($request->farm_worker !== null && $request->farm_worker) {
                                if(count($request->farm_worker) > 0) {
                                    foreach($request->farm_worker as $worker) {
                                        MainLivelihood::create([
                                            'farmer_profile_id' => $farm_profile_id,
                                            'main_livelihood' => 'farm_worker',
                                            'meta' => $worker,
                                            'value' => $worker == 'Others' ? $request->farm_worker_others : $worker,
                                            'uuid'  => Str::random(12)
                                        ]);
                                    }
                                }
                            }
        
                            if($request->fisherfolks !== null && $request->fisherfolks) {
                                if(count($request->fisherfolks) > 0) {
                                    foreach($request->fisherfolks as $fisherfolks) {
                                        MainLivelihood::create([
                                            'farmer_profile_id' => $farm_profile_id,
                                            'main_livelihood' => 'fisherfolks',
                                            'meta' => $fisherfolks,
                                            'value' => $fisherfolks == 'Others' ? $request->fisherfolks_others : $fisherfolks,
                                            'uuid'  => Str::random(12)
                                        ]);
                                    }
                                }
                            }
                            
                            if($request->agri_youth !== null && $request->agri_youth) {
                                if(count($request->agri_youth) > 0) {
                                    foreach($request->agri_youth as $agri_youth) {
                                        MainLivelihood::create([
                                            'farmer_profile_id' => $farm_profile_id,
                                            'main_livelihood' => 'agri_youth',
                                            'meta' => $agri_youth,
                                            'value' => $agri_youth == 'Others' ? $request->agri_youth_others  : $agri_youth,
                                            'uuid'  => Str::random(12)
                                        ]);
                                    }
                                }
                            }
        
                            if (in_array('farmer', $request->main_livelihood) || in_array('fisherfolks', $request->main_livelihood)) {
                                if (!empty($request->farm_parcel) && count($request->farm_parcel) > 0) {
                                    foreach($request->farm_parcel as $parcel) {
                                        // $_temp = $parce['document'];
                                        $document = $parcel['document'];
                                        $docFilename = $document->getClientOriginalName();
            
                                        $farmparcel = FarmParcel::create([
                                            'farmer_profile_id' => $farm_profile_id, 
                                            'brgy' => trim(strtolower($parcel['brgy'])), 
                                            'city'=> trim(strtolower($parcel['municipality'])),
                                            'document' => $docFilename,
                                            'total_farm_area' => $parcel['total_farm_area'], 
                                            'is_whithin_ancentral_domain' => $parcel['is_whithin_ancentral_domain'], 
                                            'is_agrarian_reform_beneficiary' => $parcel['is_agrarian_reform_beneficiary'], 
                                            'ownership_document_no' => trim(strtolower($parcel['owner_doc_no'])),
                                            'ownership_type' => $parcel['ownership_type'], 
                                            'landowner_name' => $parcel['land_owner_name'] ? trim(strtolower($parcel['land_owner_name'])) : null, 
                                            'is_other' => $parcel['is_other'] ? trim(strtolower($parcel['is_other'])) : null,
                                            'farmer_in_rotation_name' => trim(strtolower($parcel['farmer_in_rotation_name'])),
                                            'uuid' => Str::random(12)
                                        ]);
            
                                        if ($farmparcel) {
                                            if($parcel['document'] !== null) {
                                                $docDestinationPath = "uploads/farmers/farmer_".$id.'/farmParcelDocuments';
            
                                                if(!file_exists(public_path($docDestinationPath))){ 
                                                    File::makeDirectory(public_path($docDestinationPath), 0777, true);
                                                }
            
                                                $DoctempFilePath = $docDestinationPath."/".$docFilename;
            
                                                if(file_exists(public_path($DoctempFilePath))){
                                                    unlink(public_path($DoctempFilePath));
                                                }
            
                                                if(!file_exists(public_path($DoctempFilePath))){
                                                    $docFileMoved = $document->move($docDestinationPath, $docFilename);
                                                }
                                            }
            
                                            $parcel_id = $farmparcel->id;
            
                                            foreach($parcel['farm_parcel_info'] as $info) {
                                                FarmParcelInformation::create([
                                                    'farm_parcels_id' => $parcel_id, 
                                                    'farming_type' => $info['commodity'], 
                                                    'size' => $info['size'], 
                                                    'no_of_head' => $info['head_no'], 
                                                    'farm_type' => $info['farm_type'], 
                                                    'is_organic_practitioner' => $info['farm_type'] , 
                                                    'remarks' => $info['remarks'] ? trim(strtolower($info['remarks'])) : null, 
                                                    'uuid' => Str::random(12)
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
        
                        // attachments
                        if($request->file('attachments') !== null) {
                            $attachments = (object) $request->file('attachments');
                            foreach($attachments as $attachment) {
                                $_filename = $attachment->getClientOriginalName();
                                $_destinationPath = "uploads/farmers/farmer_".$id."/attachments";
        
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
        
                        CorrectedVerified::create([
                            'farmer_id' => $id,
                            'paper_date' => $request->paper_date,
                            'official' => trim(strtolower($request->official)),
                            'muni_city_official' => trim(strtolower($request->muni_city_official)),
                            'cafc_chairman' => trim(strtolower($request->cafc_chairman)),
                            'uuid'  => Str::random(12)
                        ]);
        
                        if ($request->main_livelihood && in_array('farmer', $request->main_livelihood)) {
                            $assistanceSeed = Assistance::where('name', 'like', '%inbred seed%')->where('is_archived', 0)->first();
                            $assistanceFertilizer = Assistance::where('name', 'like', '%fertilizer%')->where('is_archived', 0)->first();
                            
                            if ($assistanceSeed) {
                                $this->assistanceService->create($id, $assistanceSeed->id, 'seeds');
                            }
            
                            if ($assistanceFertilizer) {
                                $this->assistanceService->create($id, $assistanceFertilizer->id, 'fertilizer');
                            }
                        }
        
                        $activityLogger->log(
                            userId: auth()->id(),
                            table: 'Farmer',
                            message: $created ? "User Created a new farmer / fishermen `$request->firstname $request->lastname`" : "User Failed to create new farmer / fishermen `$request->firstname $request->lastname`.",
                            action: 'create',
                            status: $created ? 'success' : 'error'
                        );
                    }
        
                    $state = true;
                }
            });
    
            return redirect()
                ->route('farmers.create')
                ->with([
                    'response' => [
                        'state' => $state,
                    ]
                ]);
        }  catch (\Throwable $e) {
            $activityLogger->log(
                userId: auth()->id(),
                table: 'Farmer',
                message: "User Failed to create new farmer / fishermen `$request->firstname $request->lastname`. Error: {$e->getMessage()}",
                action: 'create',
                status: 'error'
            );

            return redirect()
                ->route('farmers.create')
                ->with([
                    'response' => [
                        'state' => false,
                        'message' => $e->getMessage()
                    ],
                    'id' => 0
                ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(FarmerInformation $farmers)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FarmerInformation $farmers)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id, FarmerInformation $farmers, ActivityLogger $activityLogger)
    {
        try {
            DB::beginTransaction();
            $state = false;
            if ($request->submit_type == 'personal') {
                $state = $this->updatePersonal($request, $id, $activityLogger);
            } else if ($request->submit_type == 'livelihood') {
                $state = $this->updateLivelihood($request, $id, $activityLogger);
            } else if ($request->submit_type == 'farm_parcel') {
                $state = $this->updateFarmParcel($request, $id, $activityLogger);
            } else if ($request->submit_type == 'signatory') {
                $state = $this->updateSignatory($request, $id, $activityLogger);
            } else if ($request->submit_type == 'profile') {
                $state = $this->updateProfile($request, $id, $activityLogger);
            }

            DB::commit();
            
            return redirect()
                ->route('farmers.view', $id)
                ->with([
                    'response' => [
                        'state' => $state
                    ]
                ]);
        }catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'state' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FarmerInformation $farmers)
    {
        //
    }

    public function archive_farmer(Request $request, $id, FarmerInformation $farmerInformation, ActivityLogger $activityLogger) {
        $resultset = array();

        if ($id) {
            $toArchive = $farmerInformation::find($id);
            $toArchive->is_archived = 1;
            $toArchive->archived_by = $request->id;
            $toArchive->archived_at = date('Y-m-d H:i:s');
            $update = $toArchive->save();

            $activityLogger->log(
                userId: auth()->id(),
                table: 'Farmer',
                message: $update ? "User archived farmer `$toArchive->firstname $toArchive->lastname` succesfully" : "User failed to archive farmer `$toArchive->firstname $toArchive->lastname`.",
                action: 'delete',
                status: $update ? 'success' : 'error'
            );

            $farmer = FarmerInformation::paginate(25);
            $resultset["state"] = true;
            $resultset["updated"] = $toArchive;
            $resultset["farmers"] = $farmer;
            $resultset['message'] = 'Farmer successfully deleted!';
        } else {
            $resultset["state"] = false;
            $resultset['message'] = 'Failed to delete farmer';
        }

        return response()->json($resultset);
    }

    public function view(Request $request, $id, FarmerInformation $farmerInformation) {
        $select = "a.*, IFNULL(farmer_image, 'images/male-farmer.png') AS farmer_image, CONCAT(
            a.firstname, ' ',
            IF(a.middlename IS NOT NULL AND a.middlename != '', CONCAT(LEFT(a.middlename, 1), '. '), ''),
            a.lastname,
            IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ', a.suffix), '')
        ) AS name, b.mothers_maiden_name, b.is_household_head, b.name_if_not_head, b.is_not_head_relationship, b.no_of_living_members, b.no_of_male, b.no_of_female, b.highest_formal_education, b.is_pwd, b.is_4ps, b.is_ig_mem, b.is_mem_specify, b.has_gov_id, b.id_type, b.id_no, b.is_farmer_coop_mem, b.is_farmer_mem, b.contact_emergency, b.contact_no, c.id as farm_id, c.main_livelihood, c.farming_gross, c.no_farming_gross, c.farm_parcel_no, c.is_arb, d.paper_date, d.official, d.muni_city_official, d.cafc_chairman";
        $farmer = FarmerInformation::from('farmer_information as a')
            ->select(DB::raw($select))
            ->LeftJoin('others_farmer_information as b', 'b.farmer_id', '=', 'a.id')
            ->leftJoin('farm_profile as c', 'c.farmer_id', '=', 'a.id')
            ->leftJoin('corrected_and_verified as d', 'd.farmer_id', '=', 'a.id')
            ->where('a.id', $id)
            ->first();
        
        $default = asset('images/male-farmer.png');
        if (isset($farmer->farmer_image) && $farmer?->farmer_image) {
            if (file_exists(public_path('uploads/farmers/farmer_'.$farmer->id.'/'.$farmer->farmer_image))) {
                $farmer->farmer_image = asset('uploads/farmers/farmer_'.$farmer->id.'/'.$farmer->farmer_image);
            } else {
                $farmer->farmer_image = $default;
            }
        } else {
            $farmer->farmer_image = $default;
        }

        $farmer->main_livelihood = @unserialize($farmer->main_livelihood) ? @unserialize($farmer->main_livelihood) : array();

        $parcel = FarmParcel::where('farmer_profile_id', $farmer->farm_id)->get();
        $parcelCollection = collect($parcel);

        foreach ($parcelCollection as $parcels) {
            $parcels->filename = $parcels->document;
            $parcels->document_path = file_exists(public_path('uploads/farmers/farmer_'.$farmer->id.'/farmParcelDocuments'.'/'.$parcels->document)) ? asset('uploads/farmers/farmer_'.$farmer->id.'/farmParcelDocuments'.'/'.$parcels->document) : 'Document not found.';
            $parcels->farm_parcel_informations = FarmParcelInformation::where('farm_parcels_id', $parcels->id)->get();
        }

        $farmer->farm_parcel = $parcelCollection;

        $attachments = Attachments::where('farmer_id', $farmer->id)
            ->where('is_archived', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(10, '*', 'attachments_page');

        $attachments->transform(function ($attachment) {
            $attachment->filepath = file_exists((public_path($attachment->filepath.'/'.$attachment->filename))) ? asset($attachment->filepath.'/'.$attachment->filename) : 'Document not found.';
            
            return $attachment;   
        });

        $farming = MainLivelihood::where('farmer_profile_id', $farmer->farm_id)->where('main_livelihood', 'farmer')->get();
        $farmworker = MainLivelihood::where('farmer_profile_id', $farmer->farm_id)->where('main_livelihood', 'farm_worker')->get();
        $fisherfolks = MainLivelihood::where('farmer_profile_id', $farmer->farm_id)->where('main_livelihood', 'fisherfolks')->get();
        $agriyouth = MainLivelihood::where('farmer_profile_id', $farmer->farm_id)->where('main_livelihood', 'agri_youth')->get();

        $farmer->main_livelihood_info = array('farmer' => $farming, 'farm_worker' => $farmworker, 'fisherfolks' => $fisherfolks, 'agri_youth' => $agriyouth);

        $all = FarmingType::select(DB::raw('id, UPPER(name) as text, type'))
            ->where('is_archived', 0)
            ->orderBy('text', 'asc')
            ->get();

        // Define type labels
        $typeLabels = [
            1 => 'crops',
            2 => 'livestock',
            3 => 'poultry',
            4 => 'agri_fishery'
        ];

        // Group by numeric type and map to labels
        $grouped = collect($typeLabels)->mapWithKeys(function ($label, $type) use ($all) {
            return [
                $label => $all->where('type', $type)->values()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'text' => $item->text,
                    ];
                }),
            ];
        });

        $paginate = $request->paginate ? intval($request->paginate): 10;
        $assistances = Assistances::leftJoin('users as b', 'b.id', '=', 'assistances.created_by')
            ->select(DB::raw("assistances.*, CONCAT(b.firstname, ' ', b.lastname) as created_name, CONCAT(
                    c.firstname, ' ',
                    IF(c.middlename IS NOT NULL AND c.middlename != '', CONCAT(LEFT(c.middlename, 1), '. '), ''),
                    c.lastname,
                    IF(c.suffix IS NOT NULL AND c.suffix != '', CONCAT(' ', c.suffix), '')
                ) AS name, d.name as assistance_name, CONCAT(
                    IF(e.firstname IS NOT NULL AND e.firstname != '', CONCAT(e.firstname, ' '), ''),
                    IF(e.middlename IS NOT NULL AND e.middlename != '', CONCAT(LEFT(e.middlename, 1), '. '), ''),
                    IF(e.lastname IS NOT NULL AND e.lastname != '', CONCAT(e.lastname, ' '), ''),
                    IF(e.suffix IS NOT NULL AND e.suffix != '', CONCAT(e.suffix, ' '), '')
                ) AS approved_name, CONCAT(
                    IF(f.firstname IS NOT NULL AND f.firstname != '', CONCAT(f.firstname, ' '), ''),
                    IF(f.middlename IS NOT NULL AND f.middlename != '', CONCAT(LEFT(f.middlename, 1), '. '), ''),
                    IF(f.lastname IS NOT NULL AND f.lastname != '', CONCAT(f.lastname, ' '), ''),
                    IF(f.suffix IS NOT NULL AND f.suffix != '', CONCAT(f.suffix, ' '), '')
                ) AS cancelled_name, CONCAT(
                    IF(g.firstname IS NOT NULL AND g.firstname != '', CONCAT(g.firstname, ' '), ''),
                    IF(g.middlename IS NOT NULL AND g.middlename != '', CONCAT(LEFT(g.middlename, 1), '. '), ''),
                    IF(g.lastname IS NOT NULL AND g.lastname != '', CONCAT(g.lastname, ' '), ''),
                    IF(g.suffix IS NOT NULL AND g.suffix != '', CONCAT(g.suffix, ' '), '')
                ) AS disapproved_name, CONCAT(
                    IF(h.firstname IS NOT NULL AND h.firstname != '', CONCAT(h.firstname, ' '), ''),
                    IF(h.middlename IS NOT NULL AND h.middlename != '', CONCAT(LEFT(h.middlename, 1), '. '), ''),
                    IF(h.lastname IS NOT NULL AND h.lastname != '', CONCAT(h.lastname, ' '), ''),
                    IF(h.suffix IS NOT NULL AND h.suffix != '', CONCAT(h.suffix, ' '), '')
                ) AS updated_name"))
            ->leftJoin('farmer_information as c', 'c.id', '=', 'assistances.farmer_id')
            ->leftJoin('assistance as d', 'd.id', '=', 'assistances.assistance_id')
            ->leftJoin('farmer_information as e', 'e.id', '=', 'assistances.approved_by')
            ->leftJoin('farmer_information as f', 'f.id', '=', 'assistances.cancelled_by')
            ->leftJoin('farmer_information as g', 'g.id', '=', 'assistances.disapproved_by')
            ->leftJoin('farmer_information as h', 'h.id', '=', 'assistances.updated_by')
            ->where('assistances.is_archived', 0)
            ->where('assistances.farmer_id', $id)
            ->orderBy('assistances.created_at', 'desc')
            ->where( function($query) use ($request) {
                if ($request->search) {
                    $query->where('assistances.livelihood', 'like', '%'.$request->search.'%')
                    ->orWhere('assistances.status', 'like', '%'.$request->search.'%');
                }
            })
            ->distinct()
            ->paginate($paginate);
        $assistances->appends(['paginate' => $paginate]);

        if($request->paginate == 'All'){
            $assistances = Assistances::LeftJoin('users as b', 'b.id', '=', 'assistances.created_by')
            ->select(DB::raw("assistances.*, CONCAT(b.firstname, ' ', b.lastname) as created_name, CONCAT(
                    c.firstname, ' ',
                    IF(c.middlename IS NOT NULL AND c.middlename != '', CONCAT(LEFT(c.middlename, 1), '. '), ''),
                    c.lastname,
                    IF(c.suffix IS NOT NULL AND c.suffix != '', CONCAT(' ', c.suffix), '')
                ) AS name, d.name as assistance_name, CONCAT(
                    IF(e.firstname IS NOT NULL AND e.firstname != '', CONCAT(e.firstname, ' '), ''),
                    IF(e.middlename IS NOT NULL AND e.middlename != '', CONCAT(LEFT(e.middlename, 1), '. '), ''),
                    IF(e.lastname IS NOT NULL AND e.lastname != '', CONCAT(e.lastname, ' '), ''),
                    IF(e.suffix IS NOT NULL AND e.suffix != '', CONCAT(e.suffix, ' '), '')
                ) AS approved_name, CONCAT(
                    IF(f.firstname IS NOT NULL AND f.firstname != '', CONCAT(f.firstname, ' '), ''),
                    IF(f.middlename IS NOT NULL AND f.middlename != '', CONCAT(LEFT(f.middlename, 1), '. '), ''),
                    IF(f.lastname IS NOT NULL AND f.lastname != '', CONCAT(f.lastname, ' '), ''),
                    IF(f.suffix IS NOT NULL AND f.suffix != '', CONCAT(f.suffix, ' '), '')
                ) AS cancelled_name, CONCAT(
                    IF(g.firstname IS NOT NULL AND g.firstname != '', CONCAT(g.firstname, ' '), ''),
                    IF(g.middlename IS NOT NULL AND g.middlename != '', CONCAT(LEFT(g.middlename, 1), '. '), ''),
                    IF(g.lastname IS NOT NULL AND g.lastname != '', CONCAT(g.lastname, ' '), ''),
                    IF(g.suffix IS NOT NULL AND g.suffix != '', CONCAT(g.suffix, ' '), '')
                ) AS disapproved_name, CONCAT(
                    IF(h.firstname IS NOT NULL AND e.firstname != '', CONCAT(h.firstname, ' '), ''),
                    IF(h.middlename IS NOT NULL AND e.middlename != '', CONCAT(LEFT(h.middlename, 1), '. '), ''),
                    IF(h.lastname IS NOT NULL AND e.lastname != '', CONCAT(h.lastname, ' '), ''),
                    IF(h.suffix IS NOT NULL AND e.suffix != '', CONCAT(h.suffix, ' '), '')
                ) AS updated_name"))
            ->leftJoin('farmer_information as c', 'c.id', '=', 'assistances.farmer_id')
            ->leftJoin('assistance as d', 'd.id', '=', 'assistances.assistance_id')
            ->leftJoin('farmer_information as e', 'e.id', '=', 'assistances.approved_by')
            ->leftJoin('farmer_information as f', 'f.id', '=', 'assistances.cancelled_by')
            ->leftJoin('farmer_information as g', 'g.id', '=', 'assistances.disapproved_by')
            ->leftJoin('farmer_information as h', 'h.id', '=', 'assistances.updated_by')
            ->where('assistances.is_archived', 0)
            ->where('assistances.farmer_id', $id)
            ->where(function($query) use($request){
                if ($request->search) {
                    $query->where('assistances.livelihood', 'like', '%'.$request->search.'%')
                    ->orWhere('assistances.status', 'like', '%'.$request->search.'%');
                }
            })
            ->groupBy('assistances.id')
            ->orderBy('assistances.created_at', 'desc')
            ->get();
            $assistances->all();
        }

        foreach($assistances as $key => $rs) {
            $_attachments = AssistancesAttachments::select('filename')
                ->where('assistance_id', $rs->id)
                ->get();

            $_attachments->transform(function ($attachment) use ($rs) {
                $attachment->filepath = public_path('uploads/assistances/assistance_'.$rs->id.'/'.$attachment->filename);
                $attachment->url = $attachment->filename && file_exists(public_path('uploads/assistances/assistance_'.$rs->id.'/'.$attachment->filename)) ? asset('uploads/assistances/assistance_'.$rs->id.'/'.$attachment->filename) : null;

                if ($attachment->url) {
                    $extension = pathinfo($attachment->url, PATHINFO_EXTENSION);
                    $attachment->extension = $extension;
                }

                return $attachment;
            });
            $assistances[$key]->attachments = $_attachments;
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
            'Farmers/View', ['farmer' => $farmer, 'types' => $grouped, 'history' => $assistances, 'attachments' => $attachments, 'assistance' => $assistanceCollection, 'allassistance' => $allassistanceCollection]
        );
    }

    public function search(Request $request, FarmerInformation $farmerInformation) {
        $q = $request->input('query');
        $farmer = FarmerInformation::from('farmer_information as a')
            ->select(DB::raw("a.farmer_image, a.id, CONCAT(
                a.firstname, ' ',
                IF(a.middlename IS NOT NULL AND a.middlename != '', CONCAT(LEFT(a.middlename, 1), '. '), ''),
                a.lastname,
                IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ', a.suffix), '')
            ) AS name"))
            ->where('a.is_archived', 0)
            ->where( function($query) use ($q) {
                if ($q) {
                    $query->where('a.firstname', 'like', '%'.$q.'%')
                    ->orWhere('a.lastname', 'like', '%'.$q.'%')
                    ->orWhere('a.middlename', 'like', '%'.$q.'%')
                    ->orWhere(DB::raw('CONCAT(a.firstname, "", a.lastname)'), 'like', '%'.$q.'%');
                }
            })
        ->get();
        $farmer->all();

        $farmer->transform(function ($farmers) {
            $farmers->farmer_image =  $farmers->farmer_image && file_exists(public_path('uploads/farmers/farmer_'.$farmers->id.'/'.$farmers->farmer_image)) ? asset('uploads/farmers/farmer_'.$farmers->id.'/'.$farmers->farmer_image) : asset('images/male-farmer.png');
            $farmers->name = strtoupper($farmers->name);
            
            return $farmers;   
        });

        return response()->json($farmer);
    }

    private function updatePersonal ($request, $id, ActivityLogger $activityLogger) {
        $contact = preg_replace('/\D/', '', $request->mobile_no);

        $farmer = FarmerInformation::where('id', $id)->first();

        $original = $farmer->getOriginal();

        $farmer->firstname = trim(strtolower($request->firstname));
        $farmer->lastname = trim(strtolower($request->lastname));
        $farmer->middlename = trim(strtolower($request->middlename));
        $farmer->suffix = trim(strtolower($request->suffix));
        $farmer->gender = trim(strtolower($request->gender));
        $farmer->lot_block_no = trim(strtolower($request->lot_block_no));
        $farmer->street = trim(strtolower($request->street));
        $farmer->brgy = trim(strtolower($request->brgy));
        $farmer->city = trim(strtolower($request->city));
        $farmer->province = trim(strtolower($request->province));
        $farmer->region = trim(strtolower($request->region));
        $farmer->mobile_no = trim($contact);
        $farmer->date_of_birth = trim(date('Y-m-d', strtotime($request->date_of_birth)));
        $farmer->place_of_birth = trim(strtolower($request->place_of_birth));
        $farmer->religion = trim(strtolower($request->religon));
        $farmer->civil_status = trim(strtolower($request->civil_status));
        $farmer->spouse_name_if_married = trim(strtolower($request->spouse_name_if_married));
        $farmer->region = trim(strtolower($request->region));
        $farmer->updated_by = $request->user_id;

        $state = false;

        $changes = $farmer->getDirty();
        $query = $farmer->save();
        $state = $query ? true : false;

        unset($changes['updated_by']);

        $changeMessages = [];

        foreach ($changes as $field => $newValue) {
            $oldValue = $original[$field] ?? null;

            $oldCompare = strtolower(trim((string) $oldValue));
            $newCompare = strtolower(trim((string) $newValue));

            if ($oldCompare === '' && $newCompare === '') {
                continue;
            }

            if ($oldCompare === $newCompare) {
                continue;
            }
            
            $changeMessages[] = str_replace('_', ' ', $field) . " changed from '{$oldValue}' to '{$newValue}'";
        }

        if ($state) {
            $emer_contact = preg_replace('/\D/', '', $request->contact_no);
            $other_info = OthersFarmerInformation::where('farmer_id', $id)->first();

            $original = $other_info->getOriginal();

            if ($other_info) {
                $other_info->mothers_maiden_name = trim(strtolower($request->mothers_maiden_name));
                $other_info->is_household_head = $request->is_household_head;
                $other_info->name_if_not_head = trim(strtolower($request->name_if_not_head));
                $other_info->is_not_head_relationship = trim(strtolower($request->is_not_head_relationship));
                $other_info->no_of_living_members = $request->no_of_living_members;
                $other_info->no_of_male = $request->no_of_male;
                $other_info->no_of_female = $request->no_of_female;
                $other_info->highest_formal_education = $request->highest_formal_education;
                $other_info->is_pwd = $request->is_pwd;
                $other_info->is_4ps = $request->is_4ps;
                $other_info->has_gov_id = $request->has_gov_id;
                $other_info->id_no = trim(strtolower($request->id_no));
                $other_info->is_farmer_coop_mem = $request->is_farmer_coop_mem;
                $other_info->is_farmer_mem = trim(strtolower($request->is_farmer_mem));
                $other_info->contact_emergency = trim(strtolower($request->contact_emergency));
                $other_info->contact_no = $emer_contact;

                if ($other_info->isDirty()) {
                    $_changes = $other_info->getDirty();
                    $other_info->save();

                    foreach ($_changes as $field => $newValue) {
                        $oldValue = $original[$field] ?? null;

                        $oldCompare = strtolower(trim((string) $oldValue));
                        $newCompare = strtolower(trim((string) $newValue));

                        if ($oldCompare === '' && $newCompare === '') {
                            continue;
                        }

                        if ($oldCompare === $newCompare) {
                            continue;
                        }

                        $changeMessages[] = str_replace('_', ' ', $field) . " changed from '{$oldValue}' to '{$newValue}'";
                    }
                }
            } else {
                $query = OthersFarmerInformation::create([
                    'farmer_id' => $id,
                    "mothers_maiden_name" => trim(strtolower($request->mothers_maiden_name)),
                    "is_household_head" => $request->is_household_head,
                    "name_if_not_head" => trim(strtolower($request->name_if_not_head)),
                    "is_not_head_relationship" => trim(strtolower($request->is_not_head_relationship)),
                    "no_of_living_members" => $request->no_of_living_members,
                    "no_of_male" => $request->no_of_male,
                    "no_of_female" => $request->no_of_female,
                    "highest_formal_education" => $request->highest_formal_education,
                    "is_pwd" => $request->is_pwd,
                    "is_4ps" => $request->is_4ps,
                    "has_gov_id" => $request->has_gov_id,
                    "id_no" => trim(strtolower($request->id_no)),
                    "is_farmer_coop_mem" => $request->is_farmer_coop_mem,
                    "is_farmer_mem" => trim(strtolower($request->is_farmer_mem)),
                    "contact_emergency" => trim(strtolower($request->contact_emergency)),
                    "contact_no" => $emer_contact,
                    'uuid' => Str::random(12)
                ]);
            }

            $message = "User updated farmer information successfully. Changes: " . implode('; ', $changeMessages);
        } else {
            $message = "User failed to update farmer information.";
        }

        $activityLogger->log(
            userId: auth()->id(),
            table: 'Farmer Information',
            message: $state ? $message : 'User failed to update farmer information.',
            action: 'update',
            status: $state ? 'success' : 'error'
        );

        return $state ? true : false;
    }

    private function updateLivelihood($request, $id, ActivityLogger $activityLogger) {
        $profile = FarmProfile::where('farmer_id', $id)->first();
        
        $changeMessages = [];
        if($profile) {
            $original = $profile->getOriginal();
            $profile->main_livelihood = serialize($request->main_livelihood);
            $profile->farming_gross = $request->farming_gross;
            $profile->no_farming_gross = $request->no_farming_gross;

            $dirty = $profile->getDirty();
            $query = $profile->save();
            $exclude = ['updated_by', 'updated_at'];
            $dirty = array_diff_key($dirty, array_flip($exclude));

            foreach ($dirty as $field => $newValue) {

                $oldValue = $original[$field] ?? null;
                if ($field === 'main_livelihood') {

                    $oldArray = $oldValue ? unserialize($oldValue) : [];
                    $newArray = $request->main_livelihood ?? [];
                    sort($oldArray);
                    sort($newArray);

                    if ($oldArray === $newArray) {
                        continue;
                    }

                    $oldDisplay = empty($oldArray) ? '(empty)' : implode(', ', $oldArray);
                    $newDisplay = empty($newArray) ? '(empty)' : implode(', ', $newArray);

                    $changeMessages[] = "Main livelihood changed from '{$oldDisplay}' to '{$newDisplay}'";
                    continue;
                }

                $oldCompare = strtolower(trim((string) $oldValue));
                $newCompare = strtolower(trim((string) $newValue));

                if ($oldCompare === '' && $newCompare === '') {
                    continue;
                }

                if ($oldCompare === $newCompare) {
                    continue;
                }

                $oldDisplay = $oldValue === null || $oldValue === '' ? '(empty)' : $oldValue;
                $newDisplay = $newValue === null || $newValue === '' ? '(empty)' : $newValue;

                $label = ucfirst(str_replace('_', ' ', $field));

                $changeMessages[] = "{$label} changed from '{$oldDisplay}' to '{$newDisplay}'";
            }
        } else {
            $query = FarmProfile::create([
                'farmer_id' => $id,
                'main_livelihood' => serialize($request->main_livelihood),
                'farming_gross' => $request->farming_gross,
                'no_farming_gross' => $request->no_farming_gross,
                'uuid' => Str::random(12)
            ]);
        }

        if ($query) {
            $farm_profile_id = isset($profile->id) && $profile->id ? $profile->id : $query->id;

            $before = MainLivelihood::where('farmer_profile_id', $farm_profile_id)
                ->get()
                ->groupBy('main_livelihood')
                ->map(fn($items) => $items->pluck('value')->sort()->values()->toArray())
                ->toArray();

            $checkMain = MainLivelihood::where('farmer_profile_id', $farm_profile_id)->where('main_livelihood', 'farmer')->get();
            if (count($checkMain->toArray()) > 0 || !in_array('farmer', $request->main_livelihood)) {
                MainLivelihood::where('farmer_profile_id', $farm_profile_id)->where('main_livelihood', 'farmer')->delete();
            }
            
            if ($request->farmer !== null && $request->farmer) {
                if(count($request->farmer) > 0) {
                    if (in_array('farmer', $request->main_livelihood)) {
                        foreach($request->farmer as $farmer) {
                            if ($farmer == 'rice' || $farmer == 'corn') {
                                MainLivelihood::create([
                                    'farmer_profile_id' => $farm_profile_id,
                                    'main_livelihood' => 'farmer',
                                    'meta' => $farmer,
                                    'value' => $farmer,
                                    'uuid'  => Str::random(12)
                                ]);
                            }
                        }
    
                        if ($request->crops !== null && $request->crops) {
                            if(count($request->crops) > 0) {
                                foreach ($request->crops as $crops) {
                                    MainLivelihood::create([
                                        'farmer_profile_id' => $farm_profile_id,
                                        'main_livelihood' => 'farmer',
                                        'meta' => 'crops',
                                        'value' => $crops,
                                        'uuid'  => Str::random(12)
                                    ]);
                                }
                            }
                        }
                        
                        if ($request->livestock !== null && $request->livestock) {
                            if(count($request->livestock) > 0) {
                                foreach ($request->livestock as $livestock) {
                                    MainLivelihood::create([
                                        'farmer_profile_id' => $farm_profile_id,
                                        'main_livelihood' => 'farmer',
                                        'meta' => 'livestock',
                                        'value' => $livestock,
                                        'uuid'  => Str::random(12)
                                    ]);
                                }
                            }
                        }
                        
                        if ($request->poultry !== null && $request->poultry) {
                            if(count($request->poultry) > 0) {
                                foreach ($request->poultry as $poultry) {
                                    MainLivelihood::create([
                                        'farmer_profile_id' => $farm_profile_id,
                                        'main_livelihood' => 'farmer',
                                        'meta' => 'poultry',
                                        'value' => $poultry,
                                        'uuid'  => Str::random(12)
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            $checkFarmWorker = MainLivelihood::where('farmer_profile_id', $farm_profile_id)->where('main_livelihood', 'farmer')->get();
            if (count($checkFarmWorker->toArray()) > 0 || !in_array('farm_worker', $request->main_livelihood)) {
                MainLivelihood::where('farmer_profile_id', $farm_profile_id)->where('main_livelihood', 'farm_worker')->delete();
            }

            if($request->farm_worker !== null && $request->farm_worker) {
                if(count($request->farm_worker) > 0) {
                    if (in_array('farm_worker', $request->main_livelihood)) {
                        foreach($request->farm_worker as $worker) {
                            MainLivelihood::create([
                                'farmer_profile_id' => $farm_profile_id,
                                'main_livelihood' => 'farm_worker',
                                'meta' => $worker,
                                'value' => $worker == 'Others' ? $request->farm_worker_others : $worker,
                                'uuid'  => Str::random(12)
                            ]);
                        }
                    }
                }
            }

            $checkFisherfolks = MainLivelihood::where('farmer_profile_id', $farm_profile_id)->where('main_livelihood', 'fisherfolks')->get();
            if (count($checkFisherfolks->toArray()) > 0 || !in_array('fisherfolks', $request->main_livelihood)) {
                MainLivelihood::where('farmer_profile_id', $farm_profile_id)->where('main_livelihood', 'fisherfolks')->delete();
            }

            if($request->fisherfolks !== null && $request->fisherfolks) {
                if(count($request->fisherfolks) > 0) {

                    if (in_array('fisherfolks', $request->main_livelihood)) {
                        foreach($request->fisherfolks as $fisherfolks) {
                            MainLivelihood::create([
                                'farmer_profile_id' => $farm_profile_id,
                                'main_livelihood' => 'fisherfolks',
                                'meta' => $fisherfolks,
                                'value' => $fisherfolks == 'Others' ? $request->fisherfolks_others : $fisherfolks,
                                'uuid'  => Str::random(12)
                            ]);
                        }
                    }
                }
            }
            
            $checkAgri = MainLivelihood::where('farmer_profile_id', $farm_profile_id)->where('main_livelihood', 'agri_youth')->get();
            if (count($checkAgri->toArray()) > 0 || !in_array('agri_youth', $request->main_livelihood)) {
                MainLivelihood::where('farmer_profile_id', $farm_profile_id)->where('main_livelihood', 'agri_youth')->delete();
            }
            if($request->agri_youth !== null && $request->agri_youth) {
                if(count($request->agri_youth) > 0) {

                    if (in_array('agri_youth', $request->main_livelihood)) {
                        foreach($request->agri_youth as $agri_youth) {
                            MainLivelihood::create([
                                'farmer_profile_id' => $farm_profile_id,
                                'main_livelihood' => 'agri_youth',
                                'meta' => $agri_youth,
                                'value' => $agri_youth == 'Others' ? $request->agri_youth_others : $agri_youth,
                                'uuid'  => Str::random(12)
                            ]);
                        }
                    }
                }
            }

            $after = MainLivelihood::where('farmer_profile_id', $farm_profile_id)
                ->get()
                ->groupBy('main_livelihood')
                ->map(fn($items) => $items->pluck('value')->sort()->values()->toArray())
                ->toArray();

            $allIds = collect(array_merge(
                    ...array_values($before),
                    ...array_values($after)
                ))
                ->filter(fn($v) => is_numeric($v))
                ->unique()
                ->values()
                ->toArray();
            
            $typeMap = FarmingType::whereIn('id', $allIds)
                ->pluck('name', 'id')
                ->toArray();

            $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));

            foreach ($allKeys as $key) {

                $oldValues = $before[$key] ?? [];
                $newValues = $after[$key] ?? [];

                sort($oldValues);
                sort($newValues);

                if ($oldValues === $newValues) {
                    continue; // no real change
                }

                $oldDisplay = empty($oldValues)
                    ? '(empty)'
                    : $this->convertIdsToNames($oldValues, $typeMap);

                $newDisplay = empty($newValues)
                    ? '(empty)'
                    : $this->convertIdsToNames($newValues, $typeMap);

                $label = ucfirst(str_replace('_', ' ', $key));

                $changeMessages[] = "{$label} changed from '{$oldDisplay}' to '{$newDisplay}'";
            }
        }

        if (!empty($changeMessages)) {
            $message = "User updated farmer profile successfully. Changes: " . implode('; ', $changeMessages);

            $activityLogger->log(
                userId: auth()->id(),
                table: 'Farmer Profile',
                message: $query ? $message : 'User failed to update farmer profile.',
                action: 'update',
                status: $query ? 'success' : 'error'
            );
        }

        return $query ? true : false;
    }

    public function uploadv1(Request $request, $id, FarmerInformation $farmerInformation) {
        $file = $request->file('attachment');
        $resultset = array();

        if($file){
            $tempFileName = time() . '_' . $file->getClientOriginalName();
            $tempMimeType = $file->getMimeType();

            if($tempMimeType == "text/csv" || $tempMimeType == "text/plain"){
                $destinationPath = "uploads/temp";
                
                if(!file_exists(public_path($destinationPath))){ 
                    File::makeDirectory(public_path($destinationPath), 0777, true);
                }

                $tempFilePath = $destinationPath."/".$tempFileName;
    
                if(file_exists(public_path($tempFilePath))){
                    unlink(public_path($tempFilePath));
                }

                if(!file_exists(public_path($tempFilePath))){
                    $fileMoved = $file->move($destinationPath, $tempFileName);
                    if($fileMoved){
                        $ctrRow = 0;
                        $noRefNumber = 0;

                        $nFilePath = public_path($tempFilePath);
                        $handle = fopen($nFilePath, "r");
                        if($handle){
                            while ($data = fgetcsv($handle)) {
                                $value = $data[0];
                                $numericOnly = str_replace('-', '', $value);

                                if(is_array($data) && $numericOnly && preg_match('/^\d{2}-\d{2}-\d{2}-\d{3}-\d{6}$/', $value)){
                                    if($data[0]){
                                        $nData = array_map('trim', $data);
                                        $ref_no = trim($data[0]);

                                        if ($ref_no) {
                                            $farmer = DB::table('farmer_information')->where('ref_no', $data[0])->get();

                                            $collection = collect($farmer);
                                            if (count($collection) == 0) {
                                                $created = FarmerInformation::create([
                                                    'ref_no' => $ref_no,
                                                    'firstname' => trim(strtolower($data[1])),
                                                    'lastname' => trim(strtolower($data[2])),
                                                    'middlename' => $data[3] ? trim(strtolower($data[3])) : null,
                                                    'suffix' => $data[4] ? trim(strtolower($data[4])) : null, 
                                                    'gender' => trim(strtolower($data[5])),
                                                    'lot_block_no' => trim(strtolower($data[6])),
                                                    'street' => trim(strtolower($data[7])),
                                                    'brgy' => trim(strtolower($data[8])),
                                                    'city' => 'Hinigaran',
                                                    'province' => 'Negros Occidental',
                                                    'region' => 'Region vi',
                                                    'mobile_no' => '0'.$data[9],
                                                    'date_of_birth' => date('Y-m-d', strtotime($data[10])),
                                                    'place_of_birth' => trim(strtolower($data[11])),
                                                    'religion' => $data[15] ? trim(strtolower($data[12])) : null,
                                                    'civil_status' => trim(strtolower($data[13])),
                                                    'spouse_name_if_married' => $data[17] ? trim(strtolower($data[14])) : null, 
                                                    'created_by'=>$id,
                                                    'uuid'=> Str::random(12)
                                                ]);

                                                if ($created) {
                                                    $ctrRow++;
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $noRefNumber++;
                                }
                            }
                        }
                        fclose($handle);
                        if(file_exists($nFilePath)){ unlink($nFilePath); }

                        $resultset["response"] = true;
                        $resultset["no_id_number_count"] = $noRefNumber;
                        $resultset["uploaded"] = $ctrRow;
                    }else{
                        $resultset["response"] = false;
                        $resultset["message"] = "No file found, nothing to import!";
                    }
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json($resultset);
        }
    }

    public function upload(Request $request, $id, FarmerInformation $farmerInformation)
        {
            $resultset = [];

            if (!$request->hasFile('attachment')) {
                return response()->json([
                    "response" => false,
                    "message" => "No file uploaded!"
                ]);
            }

            $file = $request->file('attachment');

            // Validate MIME
            $mime = $file->getMimeType();
            if (!in_array($mime, ['text/csv', 'text/plain', 'application/vnd.ms-excel'])) {
                return response()->json([
                    "response" => false,
                    "message" => "Invalid file type!"
                ]);
            }

            $destinationPath = public_path('uploads/temp');

            // Ensure directory exists
            if (!file_exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }

            // Use unique filename to avoid overwrite issues
            $fileName = time() . '_' . $file->getClientOriginalName();

            // ✅ FIX: move using absolute path
            $file->move($destinationPath, $fileName);

            $filePath = $destinationPath . '/' . $fileName;

            if (!file_exists($filePath)) {
                return response()->json([
                    "response" => false,
                    "message" => "File upload failed!"
                ]);
            }

            $ctrRow = 0;
            $noRefNumber = 0;
            $duplicateCount = 0;

            if (($handle = fopen($filePath, "r")) !== false) {

                $rowIndex = 0;

                while (($data = fgetcsv($handle, 1000, ",")) !== false) {

                    if ($rowIndex === 0) {
                        $rowIndex++;
                        continue;
                    }

                    $rowIndex++;

                    if (!is_array($data) || empty($data[0])) {
                        continue;
                    }

                    $ref_no = trim($data[0]);

                    // Validate format
                    if (!preg_match('/^\d{2}-\d{2}-\d{2}-\d{3}-\d{6}$/', $ref_no)) {
                        $noRefNumber++;
                        continue;
                    }

                    // Check if exists
                    $exists = DB::table('farmer_information')
                        ->where('ref_no', $ref_no)
                        ->exists();

                    if ($exists) {
                        $duplicateCount++;
                        continue;
                    }

                    // Safe access helper
                    $get = fn($i) => isset($data[$i]) ? trim(strtolower($data[$i])) : null;

                    $created = FarmerInformation::create([
                        'ref_no' => $ref_no,
                        'firstname' => $get(1),
                        'lastname' => $get(2),
                        'middlename' => $get(3),
                        'suffix' => $get(4),
                        'gender' => $get(5),
                        'lot_block_no' => $get(6),
                        'street' => $get(7),
                        'brgy' => $get(8),
                        'city' => 'Hinigaran',
                        'province' => 'Negros Occidental',
                        'region' => 'Region 6',
                        'mobile_no' => isset($data[9]) ? '0' . preg_replace('/\D/', '', $data[9]) : null,
                        'date_of_birth' => isset($data[10]) ? date('Y-m-d', strtotime($data[10])) : null,
                        'place_of_birth' => $get(11),
                        'religion' => $get(12),
                        'civil_status' => $get(13),
                        'spouse_name_if_married' => $get(14),
                        'created_by' => $id,
                        'uuid' => Str::random(12)
                    ]);

                    if ($created) {
                        $ctrRow++;
                    }
                }

                fclose($handle);
            }

            // Cleanup
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $resultset = [
                "response" => true,
                "no_id_number_count" => $noRefNumber,
                "uploaded" => $ctrRow
            ];

            return redirect()->back()->with([
                'response' => [
                    'state' => true,
                    'uploaded' => $ctrRow,
                    'no_id_number_count' => $noRefNumber,
                    'duplicate_ref_no_count' => $duplicateCount
                ]
            ]);
        }

    public function save_attachments(Request $request, $id, FarmerInformation $farmerInformation, ActivityLogger $activityLogger) {
        $state = false;

        if ($request->file('attachments')) {
            $attachment = $request->file('attachments');

            $attachments = (object) $request->file('attachments');
            foreach($attachments as $attachment) {
                $_filename = $attachment->getClientOriginalName();
                $_destinationPath = "uploads/farmers/farmer_".$id."/attachments";

                if(!file_exists(public_path($_destinationPath))){ 
                    File::makeDirectory(public_path($_destinationPath), 0777, true);
                }

                $originalName = $attachment->getClientOriginalName();
                $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
                $ext = $attachment->getClientOriginalExtension();

                $_tempFilePath = $_destinationPath."/".$_filename;

                $finalName = $originalName;
                $counter = 1;

                while (File::exists($_tempFilePath)) {
                    $counter++;
                    $finalName = "{$nameOnly}-{$counter}." . $ext;
                    $_tempFilePath = $_destinationPath."/".$finalName;
                }

                $_fileMoved = $attachment->move($_destinationPath, $finalName);

                if($_fileMoved) {
                    Attachments::create([
                        'farmer_id' => $id,
                        'filename' => $finalName,
                        'filepath' => $_destinationPath,
                        'uuid' => Str::random(12)
                    ]);

                    $state = true;
                }
            }
        }

        $name = $this->getFullname($id);

        $activityLogger->log(
            userId: auth()->id(),
            table: 'Farmer Attachments',
            message: $state ? "User uploaded attachments successfully for farmer `$name`." : "User failed to upload attachment for farmer `$name`.",
            action: 'create',
            status: $state ? 'success' : 'error'
        );

        return redirect()
            ->route('farmers.view', $id)
            ->with([
                'response' => [
                    'state' => $state
                ]
            ]);
    }

    public function archive_attachment(Request $request, $id, FarmerInformation $farmerInformation, ActivityLogger $activityLogger) {
        if ($id) {
            $toArchive = Attachments::where('id', $id)->first();
            $toArchive->is_archived = 1;
            $toArchive->archived_by = $request->user_id;
            $toArchive->archived_at = date('Y-m-d H:i:s');
            $state = $toArchive->save();

            $name = $this->getFullname($toArchive->farmer_id);

            $activityLogger->log(
                userId: auth()->id(),
                table: 'Farmer Attachments',
                message: $state ? "User archived attachment `{$toArchive->filename}` for `$name`." : "User failed to archive attachment `{$toArchive->filename}` for `$name`.",
                action: 'delete',
                status: $state ? 'success' : 'error'
            );

            $resultset["state"] = $state;
            $resultset["updated"] = $toArchive;
            $resultset['message'] = 'Attachment successfully archived!';
        } else {
            $resultset["state"] = false;
            $resultset['message'] = 'Failed to archive attachment';
        }

        return response()->json($resultset);
    }

    public function updateFarmParcel ($request, $id, ActivityLogger $activityLogger) {
        $state = false;
        $changeMessages = [];

        if ($id) {
            $farmParcelId = 0;
            if (count($request->farm_parcel) > 0) {
                $farm_profile = FarmProfile::where('farmer_id', $id)->first();

                $before = FarmParcel::where('farmer_profile_id', $farm_profile->id)
                    ->with('farmParcelInformations')
                    ->orderBy('id')
                    ->get();

                $farmParcel = FarmParcel::where('farmer_profile_id', $farm_profile->id)->first();
                FarmParcel::where('farmer_profile_id', $farm_profile->id)->delete();

                if ($farmParcel) {
                    FarmParcelInformation::where('farm_parcels_id', $farmParcel->id)->delete();
                }

                foreach($request->farm_parcel as $parcel) {
                    $document = $parcel['document'];

                    $docFilename = $document->getClientOriginalName();

                    $state = FarmParcel::create([
                        'farmer_profile_id' => $farm_profile->id, 
                        'brgy' => trim(strtolower($parcel['brgy'])), 
                        'city'=> trim(strtolower($parcel['city'])),
                        'document' => $docFilename,
                        'total_farm_area' => $parcel['total_farm_area'], 
                        'is_whithin_ancentral_domain' => $parcel['is_whithin_ancentral_domain'], 
                        'is_agrarian_reform_beneficiary' => $parcel['is_agrarian_reform_beneficiary'], 
                        'ownership_document_no' => trim(strtolower($parcel['ownership_document_no'])),
                        'ownership_type' => $parcel['ownership_type'], 
                        'landowner_name' => $parcel['landowner_name'] ? trim(strtolower($parcel['landowner_name'])) : null, 
                        'is_other' => $parcel['is_other'] ? trim(strtolower($parcel['is_other'])) : null,
                        'farmer_in_rotation_name' => trim(strtolower($parcel['farmer_in_rotation_name'])),
                        'uuid' => Str::random(12)
                    ]);

                    $farmParcelId = $state->id;

                    if ($state) {
                        if($parcel['document'] !== null) {
                            $docDestinationPath = "uploads/farmers/farmer_".$id.'/farmParcelDocuments';

                            if(!file_exists(public_path($docDestinationPath))){ 
                                File::makeDirectory(public_path($docDestinationPath), 0777, true);
                            }

                            $DoctempFilePath = $docDestinationPath."/".$docFilename;

                            if(file_exists(public_path($DoctempFilePath))){
                                unlink(public_path($DoctempFilePath));
                            }

                            if(!file_exists(public_path($DoctempFilePath))){
                                $docFileMoved = $document->move($docDestinationPath, $docFilename);
                            }
                        }

                        if (isset($parcel['farm_parcel_informations']) && count($parcel['farm_parcel_informations']) > 0) {
                            foreach ($parcel['farm_parcel_informations'] as $info) {
                                FarmParcelInformation::create([
                                    'farm_parcels_id' => $farmParcelId, 
                                    'farming_type' => $info['farming_type'], 
                                    'farming_type_name' => $info['farming_type_name'],
                                    'size' => $info['size'], 
                                    'no_of_head' => $info['no_of_head'], 
                                    'farm_type' => $info['farm_type'], 
                                    'is_organic_practitioner' => $info['is_organic_practitioner'] , 
                                    'remarks' => $info['remarks'] ? trim(strtolower($info['remarks'])) : null, 
                                    'uuid' => Str::random(12)
                                ]);
                            }
                        }
                    }

                    $farm_profile->farm_parcel_no = $request->farm_parcel_no;
                    $farm_profile->is_arb = $request->is_arb;
                    $farm_profile->save();
                }

                $after = FarmParcel::where('farmer_profile_id', $farm_profile->id)
                    ->with('farmParcelInformations')
                    ->orderBy('id')
                    ->get();
                
                $norm = fn($v) => strtolower(trim((string)($v ?? '')));
                $empty = fn($v) => $norm($v) === '';

                $parcelToArray = function ($p) use ($norm) {
                    // Summarize infos into stable strings (no IDs)
                    $infos = $p->farmParcelInformations
                        ->map(function ($i) use ($norm) {
                            // Use farming_type_name if you have it; fallback to farming_type
                            $type = $norm($i->farming_type_name ?: $i->farming_type);
                            $farmType = $norm($i->farm_type);
                            $size = (string)($i->size ?? 0);
                            $head = (string)($i->no_of_head ?? 0);

                            return "{$type}|{$farmType}|{$size}|{$head}";
                        })
                        ->sort()
                        ->values()
                        ->toArray();

                    return [
                        'city' => $norm($p->city),
                        'brgy' => $norm($p->brgy),
                        'total_farm_area' => (string)($p->total_farm_area ?? ''),
                        'ownership_document_no' => $norm($p->ownership_document_no),
                        'ownership_type' => (string)($p->ownership_type ?? ''),
                        'landowner_name' => $norm($p->landowner_name),
                        'is_other' => $norm($p->is_other),
                        'farmer_in_rotation_name' => $norm($p->farmer_in_rotation_name),
                        'is_whithin_ancentral_domain' => (string)($p->is_whithin_ancentral_domain ?? ''),
                        'is_agrarian_reform_beneficiary' => (string)($p->is_agrarian_reform_beneficiary ?? ''),
                        'document' => $norm($p->document),
                        'infos' => $infos,
                    ];
                };

                $beforeArr = $before->map($parcelToArray)->values()->toArray();
                $afterArr  = $after->map($parcelToArray)->values()->toArray();


                if (count($beforeArr) !== count($afterArr)) {
                    $changeMessages[] = "Farm parcel count changed from '".count($beforeArr)."' to '".count($afterArr)."'";
                }

                $max = max(count($beforeArr), count($afterArr));

                for ($i = 0; $i < $max; $i++) {

                    $old = $beforeArr[$i] ?? null;
                    $new = $afterArr[$i] ?? null;

                    $label = "Farm Parcel #".($i + 1);

                    if ($old === null && $new !== null) {
                        $changeMessages[] = "{$label} was added";
                        continue;
                    }
                    if ($old !== null && $new === null) {
                        $changeMessages[] = "{$label} was removed";
                        continue;
                    }

                    foreach ($old as $field => $oldVal) {
                        if ($field === 'infos') continue;

                        $newVal = $new[$field] ?? null;

                        $oldCompare = $norm($oldVal);
                        $newCompare = $norm($newVal);

                        if ($oldCompare === '' && $newCompare === '') continue; // empty -> empty
                        if ($oldCompare === $newCompare) continue;              // no real change

                        $prettyField = ucfirst(str_replace('_', ' ', $field));
                        $oldDisp = $empty($oldVal) ? '(empty)' : $oldVal;
                        $newDisp = $empty($newVal) ? '(empty)' : $newVal;

                        $changeMessages[] = "{$label}: {$prettyField} changed from '{$oldDisp}' to '{$newDisp}'";
                    }

                    $oldInfos = $old['infos'] ?? [];
                    $newInfos = $new['infos'] ?? [];

                    if ($oldInfos !== $newInfos) {
                        $formatInfos = function (array $infos) {
                            if (empty($infos)) return '(empty)';

                            return collect($infos)->map(function ($s) {
                                [$type, $farmType, $size, $head] = explode('|', $s);
                                $type = $type ?: 'unknown';
                                $farmType = $farmType ?: 'n/a';

                                $headText = ((float)$head > 0) ? ", heads: {$head}" : '';

                                return "{$type} size: {$size}{$headText}";
                            })->implode(', ');
                        };

                        $changeMessages[] = "{$label}: Parcel informations changed from '{$formatInfos($oldInfos)}' to '{$formatInfos($newInfos)}'";
                    }
                }
            }
        }

        if (!empty($changeMessages)) {

            $message = "User updated farm profile successfully. Changes: " . implode('; ', $changeMessages);

            $activityLogger->log(
                userId: auth()->id(),
                table: 'Farm Profile',
                message: $message,
                action: 'update',
                status: 'success'
            );
        }

        return $state ? true : false;
    }

    function convertIdsToNames (array $values, array $typeMap) {
        return collect($values)
            ->map(function ($v) use ($typeMap) {

                // If numeric and exists in farming_types, convert to name
                if (is_numeric($v) && isset($typeMap[$v])) {
                    return $typeMap[$v];
                }

                return $v; // keep text like "corn"
            })
            ->sort()
            ->values()
            ->implode(', ');
    }

    function getFullname ($value) {
        if (is_numeric($value)) {
            $type = FarmerInformation::select(DB::raw("UPPER(CONCAT(firstname, ' ', IF(middlename IS NOT NULL AND middlename != '', CONCAT(LEFT(middlename, 1), '. '), ''), lastname, IF(suffix IS NOT NULL AND suffix != '', CONCAT(' ', suffix), ''))) AS name"))->where('id', $value)->first();
            return $type ? $type->name : $value;
        }
        return $value;
    }

    function updateSignatory($request, $id, ActivityLogger $activityLogger) {
        $state = false;
        $changeMessages = [];

        if ($id) {
            $signatory = CorrectedVerified::where('farmer_id', $id)->first();

            if ($signatory) {
                $original = $signatory->getOriginal();
                $signatory->paper_date = $request->paper_date;
                $signatory->official = strtolower(trim($request->official));
                $signatory->muni_city_official = strtolower(trim($request->muni_city_official));
                $signatory->cafc_chairman = strtolower(trim($request->cafc_chairman));

                $dirty = $signatory->getDirty();
                $query = $signatory->save();

                foreach ($dirty as $field => $newValue) {
                    $oldValue = $original[$field] ?? null;

                    $changeMessages[] = $field . " changed from '{$oldValue}' to '{$newValue}'";
                }
            } else {
                $query = CorrectedVerified::create([
                    'farmer_id' => $id,
                    'paper_date' => $request->paper_date,
                    'official' => strtolower(trim($request->official)),
                    'muni_city_official' => strtolower(trim($request->muni_city_official)),
                    'cafc_chairman' => strtolower(trim($request->cafc_chairman)),
                    'uuid' => Str::random(12)
                ]);
            }

            $state = $query ? true : false;

            $message = "User updated Signatory successfully. Changes: " . implode('; ', $changeMessages);

            $activityLogger->log(
                userId: auth()->id(),
                table: 'Farming Type',
                message: $state ? $message : "User failed to update Signatory.",
                action: 'update',
                status: $state ? 'success' : 'error'
            );
        }

        return $state;
    }

    function updateProfile($request, $id, ActivityLogger $activityLogger) {
        $state = false;
        $file = $request->file('image');
        $filename = $file->getClientOriginalName();
        $farmer = FarmerInformation::select(DB::raw("CONCAT(
                        firstname, ' ',
                        IF(middlename IS NOT NULL AND middlename != '', CONCAT(LEFT(middlename, 1), '. '), ''),
                        lastname,
                        IF(suffix IS NOT NULL AND suffix != '', CONCAT(' ', suffix), '')
                    ) AS name"))
        ->where("id", $id)->first();

        $destinationPath = "uploads/farmers/farmer_".$id;
        if(!file_exists(public_path($destinationPath))){ 
            File::makeDirectory(public_path($destinationPath), 0777, true);
        }

        $tempFilePath = $destinationPath."/".$filename;

        if(file_exists(public_path($tempFilePath))){
            unlink(public_path($tempFilePath));
        }

        if(!file_exists(public_path($tempFilePath))){
            $fileMoved = $file->move($destinationPath, $filename);

            if($fileMoved) {
                DB::table('farmer_information')
                ->where('id', $id)
                ->update(['farmer_image' => $filename, 'updated_at' => date("Y-m-d H:i:s")]);

                $state = true;
            }
        }

        $message = "User updated profile of `$farmer->name` successfully.";
        $activityLogger->log(
            userId: auth()->id(),
            table: 'Farming Type',
            message: $state ? $message : "User failed to update `$farmer->name` profile.",
            action: 'update',
            status: $state ? 'success' : 'error'
        );

        return $state;
    }

    public function print($id) {
        $select = "a.*, IFNULL(farmer_image, 'images/male-farmer.png') AS farmer_image, CONCAT(
            a.firstname, ' ',
            IF(a.middlename IS NOT NULL AND a.middlename != '', CONCAT(LEFT(a.middlename, 1), '. '), ''),
            a.lastname,
            IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ', a.suffix), '')
        ) AS name, b.mothers_maiden_name, b.is_household_head, b.name_if_not_head, b.is_not_head_relationship, b.no_of_living_members, b.no_of_male, b.no_of_female, b.highest_formal_education, b.is_pwd, b.is_4ps, b.is_ig_mem, b.is_mem_specify, b.has_gov_id, b.id_type, b.id_no, b.is_farmer_coop_mem, b.is_farmer_mem, b.contact_emergency, b.contact_no, c.id as farm_id, c.main_livelihood, c.farming_gross, c.no_farming_gross, c.farm_parcel_no, c.is_arb, d.paper_date, d.official, d.muni_city_official, d.cafc_chairman";
        $farmer = FarmerInformation::from('farmer_information as a')
            ->select(DB::raw($select))
            ->LeftJoin('others_farmer_information as b', 'b.farmer_id', '=', 'a.id')
            ->leftJoin('farm_profile as c', 'c.farmer_id', '=', 'a.id')
            ->leftJoin('corrected_and_verified as d', 'd.farmer_id', '=', 'a.id')
            ->where('a.id', $id)
            ->first();
        
        $default = asset('images/male-farmer.png');
        if (isset($farmer->farmer_image) && $farmer?->farmer_image) {
            if (file_exists(public_path('uploads/farmers/farmer_'.$farmer->id.'/'.$farmer->farmer_image))) {
                $farmer->farmer_image = asset('uploads/farmers/farmer_'.$farmer->id.'/'.$farmer->farmer_image);
            } else {
                $farmer->farmer_image = $default;
            }
        } else {
            $farmer->farmer_image = $default;
        }

        $farmer->main_livelihood = @unserialize($farmer->main_livelihood) ? @unserialize($farmer->main_livelihood) : array();

        $parcel = FarmParcel::where('farmer_profile_id', $farmer->farm_id)->get();
        $parcelCollection = collect($parcel);

        foreach ($parcelCollection as $parcels) {
            $parcels->filename = $parcels->document;
            $parcels->document_path = file_exists(public_path('uploads/farmers/farmer_'.$farmer->id.'/farmParcelDocuments'.'/'.$parcels->document)) ? asset('uploads/farmers/farmer_'.$farmer->id.'/farmParcelDocuments'.'/'.$parcels->document) : 'Document not found.';
            $parcels->farm_parcel_informations = FarmParcelInformation::where('farm_parcels_id', $parcels->id)->get();
        }

        $farmer->farm_parcel = $parcelCollection;

        $farming = MainLivelihood::where('farmer_profile_id', $farmer->farm_id)
            ->where('main_livelihood', 'farmer')
            ->get();

        foreach ($farming as $farm) {
            $farm->value = is_numeric($farm->value) ? FarmingType::where('id', $farm->value)->value('name') : $farm->value;
        }
        $farmworker = MainLivelihood::where('farmer_profile_id', $farmer->farm_id)->where('main_livelihood', 'farm_worker')->get();
        $fisherfolks = MainLivelihood::where('farmer_profile_id', $farmer->farm_id)->where('main_livelihood', 'fisherfolks')->get();
        $agriyouth = MainLivelihood::where('farmer_profile_id', $farmer->farm_id)->where('main_livelihood', 'agri_youth')->get();

        $farmer->main_livelihood_info = array('farmer' => $farming, 'farm_worker' => $farmworker, 'fisherfolks' => $fisherfolks, 'agri_youth' => $agriyouth);

        $all = FarmingType::select(DB::raw('id, UPPER(name) as text, type'))
            ->where('is_archived', 0)
            ->orderBy('text', 'asc')
            ->get();

        // Define type labels
        $typeLabels = [
            1 => 'crops',
            2 => 'livestock',
            3 => 'poultry',
            4 => 'agri_fishery'
        ];

        // Group by numeric type and map to labels
        $grouped = collect($typeLabels)->mapWithKeys(function ($label, $type) use ($all) {
            return [
                $label => $all->where('type', $type)->values()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'text' => $item->text,
                    ];
                }),
            ];
        });

        return Inertia::render(
            'Farmers/Print', ['farmer' => $farmer, 'types' => $grouped]
        );
    }

    public function autoApprove(Request $request){
        //to add in web.php Route::post('/assistances/auto-approve', [AssistanceController::class, 'autoApprove']);
        $request->validate([
            'farmer_id' => 'required',
            'assistance_id' => 'required',
            'type' => 'required|in:seeds,fertilizer'
        ]);

        $service = new AssistanceAutoApprovalService();

        $assistance = $service->create(
            $request->farmer_id,
            $request->assistance_id,
            $request->type
        );

        return response()->json([
            'state' => true,
            'message' => 'Assistance automatically approved.',
            'data' => $assistance
        ]);
    }

    public function test() {
        $assistanceSeed = Assistance::where('name', 'like', '%seed%')->where('is_archived', 0)->first();
        $assistanceFertilizer = Assistance::where('name', 'like', '%fertilizer%')->where('is_archived', 0)->first();
        
        if ($assistanceSeed) {
            $this->assistanceService->create(2, $assistanceSeed->id, 'seeds');
        }

        if ($assistanceFertilizer) {
            $this->assistanceService->create(2, $assistanceFertilizer->id, 'fertilizer');
        }
        
        return response()->json([
            'state' => true,
            'message' => 'Test assistance auto-approval executed.'
        ]);
    }
}
