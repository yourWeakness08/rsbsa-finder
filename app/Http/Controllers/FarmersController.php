<?php

namespace App\Http\Controllers;

use App\Models\Farmers;
use App\Models\FarmerInformation;
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
        $users = array();

        return Inertia::render(
            'Farmers/Index', [ 'users' => $users, 'filter' => $request ]
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

    public function get_select2_farmers(Request $request, Farmers $farmers){
        $result = array();

        $_farmers = FarmerInformation::select(DB::raw('id, CONCAT(farmer_information.firstname, " ", farmer_information.lastname) as text, farmer_information.firstname, farmer_information.lastname'))
            ->where('farmer_information.is_archived', 0)->get();
        
        if ($_farmers->count() > 0){
            $result = $_farmers->all();
        }

        return response()->json($result);
    }
}
