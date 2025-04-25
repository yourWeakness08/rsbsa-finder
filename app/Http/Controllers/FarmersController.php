<?php

namespace App\Http\Controllers;

use App\Models\FarmerInformation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;
use Inertia\Inertia;

class FarmersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) {
        $paginate = $request->paginate ? intval($request->paginate): 10;
        $farmer = FarmerInformation::where(function($query) use($request){
            if($request->search){
                $query->where('farmer_information.firstname', 'like', '%'.$request->search.'%')
                ->orWhere('farmer_information.lastname', 'like', '%'.$request->search.'%')
                ->orWhere('farmer_information.middlename', 'like', '%'.$request->search.'%');
            }
        })
        ->where('is_archived', 0)
        ->paginate($paginate);
        $farmer->appends(['paginate' => $paginate]);
        
        if($request->paginate == 'All'){ 
            $farmer = FarmerInformation::where(function($query) use($request){
                if($request->search){
                    $query->where('farmer_information.firstname', 'like', '%'.$request->search.'%')
                ->orWhere('farmer_information.lastname', 'like', '%'.$request->search.'%')
                ->orWhere('farmer_information.middlename', 'like', '%'.$request->search.'%');
                }
            })
            ->where('is_archived', 0)
            ->get();
            $farmer->all();
        }

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
    public function store(Request $request)
    {
        //
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
}
