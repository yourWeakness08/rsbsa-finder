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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use Illuminate\Support\Str;
use Inertia\Inertia;

class FarmersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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
    public function store(Request $request, FarmerInformation $farmerInformation) {
        $user_id = $request->user_id;
        $contact = preg_replace('/\D/', '', $request->contact);

        $checkRecords = DB::table('farmer_information')
            ->where('firstname', trim(strtolower($request->firstname)))
            ->where('middlename', $request->middlename ? trim(strtolower($request->middlename)) : null)
            ->where('lastname', trim(strtolower($request->lastname)))
            ->where('suffix', $request->suffix ? trim(strtolower($request->suffix)) : null)
            ->where('gender', trim(strtolower($request->gender)))
            ->where(DB::raw('DATE(date_of_birth)'), date('Y-m-d', strtotime($request->birth)))
            ->first();

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
                'brgy' => trim(strtolower($request->firstname)),
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
                    'farm_parcel_no' => $request->farm_parcel_no, 
                    'is_arb' => $request->is_arb, 
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
                                    'value' => $agri_youth == 'Others' ? $request->fisherfolks_others : $agri_youth,
                                    'uuid'  => Str::random(12)
                                ]);
                            }
                        }
                    }

                    if(count($request->farm_parcel) > 0) {
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
            }

            $state = true;
        }

        return redirect()
            ->route('farmers.create')
            ->with([
                'response' => [
                    'state' => $state,
                ],
                'id' => $id
            ]);
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
    public function update(Request $request, $id, FarmerInformation $farmers)
    {
        $state = false;
        if ($request->submit_type == 'personal') {
            $state = $this->updatePersonal($request, $id);
        } else if ($request->submit_type == 'livelihood') {
            $state = $this->updateLivelihood($request, $id);
        }

        return redirect()
            ->route('farmers.view', $id)
            ->with([
                'response' => [
                    'state' => $state
                ]
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FarmerInformation $farmers)
    {
        //
    }

    public function archive_farmer(Request $request, $id, FarmerInformation $farmerInformation) {
        $resultset = array();

        if ($id) {
            $toArchive = $farmerInformation::find($id);
            $toArchive->is_archived = 1;
            $toArchive->archived_by = $request->id;
            $toArchive->archived_at = date('Y-m-d H:i:s');
            $toArchive->save();

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

    public function view($id, FarmerInformation $farmerInformation) {
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
            $parcels->document_path = file_exists(public_path('uploads/farmers/farmer_'.$farmer->id.'/farmParcelDocuments'.'/'.$parcels->document)) ? asset('uploads/farmers/farmer_'.$farmer->id.'/farmParcelDocuments'.'/'.$parcels->document) : 'Document not found.';
            $parcels->farm_parcel_informations = FarmParcelInformation::where('farm_parcels_id', $parcels->id)->get();
        }

        $farmer->farm_parcel = $parcelCollection;

        //here
        $attachments = Attachments::where('farmer_id', $farmer->id)->orderBy('created_at', 'desc')
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

        $assistanceHistory = AssistanceHistory::from('assistance_history as a')
            ->select(DB::raw('a.*, CONCAT(b.firstname, " ", b.lastname) as created_name'))
            ->leftJoin('users as b', 'b.id', '=', 'a.created_by')
            ->where('a.farmer_id', $farmer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(3, '*', 'history_page');

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
            'Farmers/View', ['farmer' => $farmer, 'types' => $grouped, 'history' => $assistanceHistory, 'attachments' => $attachments, 'assistance' => $assistanceCollection, 'allassistance' => $allassistanceCollection]
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
            $farmers->farmer_image = asset('uploads/farmers/farmer_'.$farmers->id.'/'.$farmers->farmer_image);
            $farmers->name = strtoupper($farmers->name);
            
            return $farmers;   
        });

        return response()->json($farmer);
    }

    private function updatePersonal ($request, $id) {
        $contact = preg_replace('/\D/', '', $request->mobile_no);

        $farmer = FarmerInformation::find($id);
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
        $farmer->civil_status = trim($request->civil_status);
        $farmer->spouse_name_if_married = trim(strtolower($request->spouse_name_if_married));
        $farmer->region = trim(strtolower($request->region));
        $farmer->updated_by = $request->user_id;
        $query = $farmer->save();

        if ($query) {
            $emer_contact = preg_replace('/\D/', '', $request->contact_no);

            $other_info = OthersFarmerInformation::where('farmer_id', $id)->first();

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
                $other_info->save();
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
        }

        return $query ? true : false;
    }

    private function updateLivelihood($request, $id) {
        $profile = FarmProfile::where('farmer_id', $id)->first();

        if($profile) {
            $profile->main_livelihood = serialize($request->main_livelihood);
            $profile->farming_gross = $request->farming_gross;
            $profile->no_farming_gross = $request->no_farming_gross;
            $query = $profile->save();
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
                                'value' => $agri_youth == 'Others' ? $request->fisherfolks_others : $agri_youth,
                                'uuid'  => Str::random(12)
                            ]);
                        }
                    }
                }
            }
        }

        return $query ? true : false;
    }

    public function upload(Request $request, $id, FarmerInformation $farmerInformation) {
        $file = $request->file('attachment');
        $resultset = array();

        if($file){
            $tempFileName = $file->getClientOriginalName();
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
                                if(is_array($data) && is_numeric(($numericOnly)) && preg_match('/^\d{2}-\d{2}-\d{2}-\d{3}-\d{6}$/', $value)){
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
                                                    'city' => trim(strtolower($data[9])),
                                                    'province' => trim(strtolower($data[10])),
                                                    'region' => trim(strtolower($data[11])),
                                                    'mobile_no' => '0'.$data[12],
                                                    'date_of_birth' => date('Y-m-d', strtotime($data[13])),
                                                    'place_of_birth' => trim(strtolower($data[14])),
                                                    'religion' => $data[15] ? trim(strtolower($data[15])) : null,
                                                    'civil_status' => trim(strtolower($data[16])),
                                                    'spouse_name_if_married' => $data[17] ? trim(strtolower($data[17])) : null, 
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
}
