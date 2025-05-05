<?php

namespace App\Http\Controllers;

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
        $farmer = FarmerInformation::LeftJoin('users', 'users.id', '=', 'farmer_information.created_by')
            ->select(DB::raw("CONCAT(farmer_information.firstname, ' ', farmer_information.lastname) as name, farmer_information.*, CONCAT(users.firstname, ' ', users.lastname) as created_name"))
            ->where('farmer_information.is_archived', 0)
            ->where(function($query) use($request){
            if($request->search){
                $query->where('farmer_information.firstname', 'like', '%'.$request->search.'%')
                ->orWhere('farmer_information.lastname', 'like', '%'.$request->search.'%')
                ->orWhere('farmer_information.middlename', 'like', '%'.$request->search.'%');
            }
        })
        ->paginate($paginate);
        $farmer->appends(['paginate' => $paginate]);
        
        if($request->paginate == 'All'){ 
            $farmer = FarmerInformation::LeftJoin('users', 'users.id', '=', 'farmer_information.created_by')
            ->select(DB::raw("CONCAT(farmer_information.firstname, ' ', farmer_information.lastname) as name, farmer_information.*, CONCAT(users.firstname, ' ', users.lastname) as created_name"))
            ->where('farmer_information.is_archived', 0)
            ->where(function($query) use($request){
                if($request->search){
                    $query->where('farmer_information.firstname', 'like', '%'.$request->search.'%')
                ->orWhere('farmer_information.lastname', 'like', '%'.$request->search.'%')
                ->orWhere('farmer_information.middlename', 'like', '%'.$request->search.'%');
                }
            })
            ->get();
            $farmer->all();
        }

        $farmer->transform(function ($farmers) {
            $farmers->farmer_image = asset('uploads/farmers/farmer_'.$farmers->id.'/'.$farmers->farmer_image);
            
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
                'spouse_name_if_married' => $request->spouse ? trim(strtolower($request->region)) : null, 
                'region' => trim(strtolower($request->region)),
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
                                    'main_livelihood' => 'farm_worker',
                                    'meta' => $fisherfolks,
                                    'value' => $worfisherfolksker == 'Others' ? $request->fisherfolks_others : $fisherfolks,
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
                                    'main_livelihood' => 'farm_worker',
                                    'meta' => $agri_youth,
                                    'value' => $agri_youth_others == 'Others' ? $request->fisherfolks_others : $agri_youth,
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
                                'document' => $document,
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
                    'state' => $state
                ]
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Farmers $farmers)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Farmers $farmers)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Farmers $farmers)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Farmers $farmers)
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

        $select = "a.*, b.mothers_maiden_name, b.is_household_head, b.name_if_not_head, b.is_not_head_relationship, b.no_of_living_members, b.no_of_male, b.no_of_female, b.highest_formal_education, b.is_pwd, b.is_4ps, b.is_ig_mem, b.is_mem_specify, b.has_gov_id, b.id_type, b.id_no, b.is_farmer_coop_mem, b.is_farmer_mem, b.contact_emergency, b.contact_no, c.id as farm_id, c.main_livelihood, c.farming_gross, c.no_farming_gross, c.farm_parcel_no, c.is_arb, d.paper_date, d.official, d.muni_city_official, d.cafc_chairman";
        $farmer = FarmerInformation::from('farmer_information as a')
            ->select(DB::raw($select))
            ->LeftJoin('others_farmer_information as b', 'b.farmer_id', '=', 'a.id')
            ->leftJoin('farm_profile as c', 'c.farmer_id', '=', 'a.id')
            ->leftJoin('corrected_and_verified as d', 'd.farmer_id', '=', 'a.id')
            ->where('a.id', $id)
            ->get();

        $collection = collect($farmer);

        $collection->map( function($item) {
            $item->farmer_image = asset('uploads/farmers/farmer_'.$item->id.'/'.$item->farmer_image);
            $item->main_livelihood = @unserialize($item->main_livelihood) ? @unserialize($item->main_livelihood) : array();

            $parcel = FarmParcel::where('farmer_profile_id', $item->farm_id)->get();
            $parcelCollection = collect($parcel);

            $parcelCollection->map( function($parcels) {
                $parcels->farm_parcel_informations = FarmParcelInformation::where('farm_parcels_id', $parcels->id)->get();

                return $parcels;
            });

            $item->farm_parcel = $parcelCollection;
            $item->attachments = Attachments::where('farmer_id', $item->id)->get();
            $item->main_livelihood_info = MainLivelihood::where('farmer_profile_id', $item->farm_id)->get();

            return $item;
        });

        $all = FarmingType::select(DB::raw('id, UPPER(name) as text, type'))
            ->where('is_archived', 0)
            ->orderBy('text', 'asc')
            ->get();

        // Define type labels
        $typeLabels = [
            1 => 'crops',
            2 => 'livestock',
            3 => 'poultry',
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
            'Farmers/View', ['farmer' => $farmer, 'types' => $grouped]
        );
    }

    public function search_farmer(Request $request,  FarmerInformation $farmerInformation) {
        dd($request);
    }
}
