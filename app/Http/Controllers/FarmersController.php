<?php

namespace App\Http\Controllers;

use App\Models\FarmerInformation;
use App\Models\OthersFarmerInformation;
use App\Models\FarmParcel;
use App\Models\FarmParcelInformation;
use App\Models\User;
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
    public function create()
    {
        return Inertia::render(
            'Farmers/Create'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, FarmerInformation $farmerInformation) {
        $user_id = $request->user_id;
        $contact = preg_replace('/\D/', '', $request->contact);

        $created = FarmerInformation::create([
            'ref_no' => $request->ref_no,
            'firstname' => trim(strtolower($request->firstname)),
            'lastname' => trim(strtolower($request->lastname)),
            'middlename' => $request->middlename ? trim(strtolower($request->middlename)) : NULL,
            'suffix' => $request->suffix ? trim(strtolower($request->suffix)) : NULL, 
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
            'religion' => $request->religion ? trim(strtolower($request->religion)) : NULL,
            'civil_status' => trim(strtolower($request->civil_status)),
            'spouse_name_if_married' => $request->spouse ? trim(strtolower($request->region)) : NULL, 
            'region' => trim(strtolower($request->region)),
            'created_by'=>$user_id,
            'uuid'=> Str::random(12)
        ]);

        if ($created) {
            $id = $created->id;

            $file = $request->file('image');
            $filename = $file->getClientOriginalName();

            $destinationPath = "uploads/farmers/farmer_".$id;
            // $filepath = $file->move(public_path('uploads/farmers/farmer_'.$id), $filename);
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
                'name_if_not_head' => $request->household_head_name ? trim(strtolower($request->household_head_name)) : NULL, 
                'is_not_head_relationship' => $request->head_relationship ? trim(strtolower($request->head_relationship)) : NULL, 
                'no_of_living_members' => $request->members_no, 
                'no_of_male' => $request->no_of_male, 
                'no_of_female' => $request->no_of_female, 
                'highest_formal_education' => $request->education, 
                'is_pwd' => $request->is_pwd, 
                'is_4ps' => $request->is_4ps, 
                'has_gov_id' => $request->has_gov_id, 
                'id_no' => $request->gov_id_no ? trim(strtolower($request->gov_id_no)) : NULL, 
                'is_farmer_coop_mem' => $request->is_farmer_member, 
                'is_farmer_mem' => $request->asocc_name ? trim(strtolower($request->asocc_name)) : NULL, 
                'contact_emergency' => trim(strtolower($request->person_emergency)), 
                'contact_no' => $emer_contact, 
                'uuid' => Str::random(12)
            ]);

            // farm profile here
            // not done as still figuring out what to do in main livelihood
            if(count($request->farm_parcel) > 0) {
                foreach($request->farm_parcel as $parcel) {
                    $farmparcel = FarmParcel::create([
                        'farm_profile_id' => 1, 
                        'brgy' => trim(strtolower($parcel['brgy'])), 
                        'city'=> trim(strtolower($parcel['municipality'])), 
                        'total_farm_area' => $parcel['total_farm_area'], 
                        'is_whithin_ancentral_domain' => $parcel['is_whithin_ancentral_domain'], 
                        'is_agrarian_reform_beneficiary' => $parcel['is_agrarian_reform_beneficiary'], 
                        'ownership_document_no' => trim(strtolower($parcel['owner_doc_no'])),
                        'ownership_type' => $parcel['ownership_type'], 
                        'landowner_name' => $parcel['land_owner_name'] ? trim(strtolower($parcel['land_owner_name'])) : NULL, 
                        'is_other' => $parcel['is_other'] ? trim(strtolower($parcel['is_other'])) : NULL,
                        'uuid' => Str::random(12)
                    ]);

                    if ($farmparcel) {
                        $parcel_id = $farmparcel->id;

                        foreach($parcel->farm_parcel_info as $info) {
                            FarmParcelInformation::create([
                                'farm_parcels_id' => $parcel_id, 
                                'farming_type' => $info['commodity'], 
                                'size' => $info['size'], 
                                'no_of_head' => $info['head_no'], 
                                'farm_type' => $info['farm_type'], 
                                'is_organic_practitioner' => $info['farm_type'] , 
                                'remarks' => $info['remarks'] ? trim(strtolower($info['remarks'])) : NULL, 
                                'uuid' => Str::random(12)
                            ]);
                        }
                    }
                }
            }
        }
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
}
