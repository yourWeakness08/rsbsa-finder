<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Str;
use Inertia\Inertia;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) {
        $paginate = $request->paginate ? intval($request->paginate): 25;
        $users = User::where(function($query) use($request){
            if($request->search){
                $query->where('users.firstname', 'like', '%'.$request->search.'%')
                ->orWhere('users.lastname', 'like', '%'.$request->search.'%')
                ->orWhere('users.email', 'like', '%'.$request->search.'%');
            }
        })->paginate($paginate);
        $users->appends(['paginate' => $paginate]);
        
        if($request->paginate == 'All'){ 
            $users = User::where(function($query) use($request){
                if($request->search){
                    $query->where('users.firstname', 'like', '%'.$request->search.'%')
                ->orWhere('users.lastname', 'like', '%'.$request->search.'%')
                ->orWhere('users.email', 'like', '%'.$request->search.'%');
                }
            })->get();
            $users->all();
        }

        return Inertia::render(
            'Users/Index', [ 'users' => $users, 'filter' => $request ]
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
