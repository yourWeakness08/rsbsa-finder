<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class FarmerInformation extends Model
{
    use HasFactory;
    protected $table = "farmer_information";
    protected $fillable = ['ref_no', 'farmer_image', 'firstname', 'lastname', 'middlename', 'suffix', 'gender', 'lot_block_no', 'street', 'brgy', 'city', 'province', 'region', 'mobile_no', 'date_of_birth', 'place_of_birth', 'religion', 'civil_status', 'spouse_name_if_married', 'region', 'created_by', 'updated_by', 'is_archived', 'uuid'];

    public function tempUploadAttachment ($id, $request) {
        $resultset = array();
        $file = $request->file('image');

        if($file){
            $tempFileName = $file->getClientOriginalName();
            $tempMimeType = $file->getMimeType();

            if($tempMimeType == "text/csv" || $tempMimeType == "text/plain"){
                $destinationPath = "uploads/farmers/farmer_".$id;
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
                        

                        $resultset["response"] = true;
                    }else{
                        $resultset["response"] = false;
                    }
                }           
            }else{
                $resultset["response"] = false;
            }

        }else{
            $resultset["response"] = false;
        }

        return $resultset;
    }
}
