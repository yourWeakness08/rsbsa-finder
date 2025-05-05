<?php

namespace App\Http\Controllers;

use App\Models\FarmingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;
use Inertia\Inertia;

class FarmingTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) {
        DB::enableQueryLog();
        $paginate = $request->paginate ? intval($request->paginate): 10;
        $farming_types = FarmingType::LeftJoin('users', 'users.id', '=', 'farming_types.created_by')
        ->select(DB::raw('CONCAT(users.firstname, " ", users.lastname) as created_name, farming_types.id, farming_types.type, farming_types.name, farming_types.created_at'))
        ->where('farming_types.is_archived', 0)
        ->where(function($query) use($request){
            if($request->search){
                $query->where('farming_types.type', 'like', '%'.$request->search.'%')
                ->orWhere('farming_types.name', 'like', '%'.$request->search.'%');
            }
        })->paginate($paginate);
        $farming_types->appends(['paginate' => $paginate]);
        
        if($request->paginate == 'All'){
            $farming_types = FarmingType::LeftJoin('users', 'users.id', '=', 'farming_types.created_by')
            ->select(DB::raw('CONCAT(users.firstname, " ", users.lastname) as created_name, farming_types.id, farming_types.type, farming_types.name, farming_types.created_at'))
            ->where('farming_types.is_archived', 0)
            ->where(function($query) use($request){
                if($request->search){
                    $query->where('farming_types.type', 'like', '%'.$request->search.'%')
                    ->orWhere('farming_types.name', 'like', '%'.$request->search.'%');
                }
            })->get();
            $farming_types->all();
        }

        return Inertia::render(
            'Types/Index', [ 'farming_type' => $farming_types, 'filter' => $request ]
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
    public function store(Request $request) {
        $resultSet = array();

        if ($request->type && $request->name) {
            $created = FarmingType::create([
                'type' => trim(strtolower($request->type)),
                'name' => trim(strtolower($request->name)),
                'created_by'=>$request->user_id,
                'uuid'=>Str::random(12)
            ]);

            $farming_type = FarmingType::paginate(25);
            $resultSet["state"] = true;
            $resultSet["added"] = $created;
            $resultSet["farming_type"] = $farming_type;
        } else {
            $resultSet["state"] = false;
            $resultSet["message"] = "This is a required field.";
        }

        return response()->json($resultSet);
    }

    /**
     * Display the specified resource.
     */
    public function show(FarmingType $farmingType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FarmingType $farmingType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id, FarmingType $farmingType) {
        $resultset = array();

        if ($id) {
            $toUpdate = $farmingType::find($id);
            $toUpdate->type = $request->type;
            $toUpdate->name = trim(strtolower($request->name));
            $toUpdate->updated_by = $request->user_id;
            $toUpdate->save();

            $farming_type = FarmingType::paginate(25);
            $resultset["state"] = true;
            $resultset["updated"] = $toUpdate;
            $resultset["farming_type"] = $farming_type;
        } else {
            $resultset["state"] = false;
            $resultset["message"] = "This is a required field.";
        }
        
        return response()->json($resultset);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id, FarmingType $farmingType) {
    }
    
    public function archive_type(Request $request, $id, FarmingType $farmingType) {
        $resultset = array();

        if ($id) {
            $toArchive = $farmingType::find($id);
            $toArchive->is_archived = 1;
            $toArchive->archived_by = $request->id;
            $toArchive->archived_at = date('Y-m-d H:i:s');
            $toArchive->save();

            $farming_type = FarmingType::paginate(25);
            $resultset["state"] = true;
            $resultset["updated"] = $toArchive;
            $resultset["farming_type"] = $farming_type;
            $resultset['message'] = 'Farming type successfully deleted!';
        } else {
            $resultset["state"] = false;
            $resultset['message'] = 'Failed to delete farming type';
        }

        return response()->json($resultset);
    }
}
