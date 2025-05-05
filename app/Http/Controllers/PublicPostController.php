<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FarmerInformation;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;
use Inertia\Inertia;

class PublicPostController extends Controller
{
    public function finder(Request $request){
        DB::enableQueryLog();
        $checkRecords = array();
        $state = false;

        if ($request->isMethod('post')) {
            $checkRecords = DB::table('farmer_information')
                ->select(DB::raw(
                    "CONCAT(
                        firstname, ' ',
                        IF(middlename IS NOT NULL AND middlename != '', CONCAT(LEFT(middlename, 1), '. '), ''),
                        lastname,
                        IF(suffix IS NOT NULL AND suffix != '', CONCAT(' ', suffix), '')
                    ) AS full_name, ref_no
                "))
                ->where('firstname', trim(strtolower($request->firstname)))
                ->where('middlename', $request->middlename ? trim(strtolower($request->middlename)) : null)
                ->where('lastname', trim(strtolower($request->lastname)))
                ->where('suffix', $request->suffix ? trim(strtolower($request->suffix)) : null)
                ->where('gender', trim(strtolower($request->gender)))
                ->where(DB::raw('DATE(date_of_birth)'), date('Y-m-d', strtotime($request->birth)))
                ->first();

            $state = $checkRecords ? true : false;

            return Inertia::render('Finder/Index', ['data' => $checkRecords, 'response' => $state]);
        }

        return Inertia::render('Finder/Index');
    }
}
