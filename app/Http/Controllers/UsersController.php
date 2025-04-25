<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FarmerInformation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Str;
use Inertia\Inertia;

class UsersController extends Controller
{
    // use PasswordValidationRules;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) {
        DB::enableQueryLog();

        $paginate = $request->paginate ? intval($request->paginate): 10;
        $users = User::where(function($query) use($request){
            if($request->search){
                $query->where('users.firstname', 'like', '%'.$request->search.'%')
                ->orWhere('users.lastname', 'like', '%'.$request->search.'%')
                ->orWhere('users.email', 'like', '%'.$request->search.'%');
            }
        })
        ->where('is_archived', 0)
        ->paginate($paginate);
        $users->appends(['paginate' => $paginate]);
        
        if($request->paginate == 'All'){ 
            $users = User::where(function($query) use($request){
                if($request->search){
                    $query->where('users.firstname', 'like', '%'.$request->search.'%')
                ->orWhere('users.lastname', 'like', '%'.$request->search.'%')
                ->orWhere('users.email', 'like', '%'.$request->search.'%');
                }
            })
            ->where('is_archived', 0)
            ->get();
            $users->all();
        }

        $_tempUsers = User::select('farmer_id')
            ->whereNot('farmer_id', 0)
            ->where('role', 0)
            ->where('is_archived', 0)->get();

        $ids = array();

        if (count($_tempUsers) > 0) {
            foreach($_tempUsers->all() as $rs) {
                array_push($ids, $rs->farmer_id);
            }
        }

        $_farmers = FarmerInformation::select(DB::raw('farmer_information.id, UPPER(CONCAT(farmer_information.firstname, " ", farmer_information.lastname)) as text, UPPER(farmer_information.firstname) as firstname, UPPER(farmer_information.lastname) as lastname'))
            ->where('farmer_information.archived_by', 0)
            ->where(function($query) use($ids){
                if (count($ids) > 0) {
                    $query->whereNotIn('farmer_information.id', $ids);
                }
            })
            ->get();

        return Inertia::render(
            'Users/Index', [ 'users' => $users, 'filter' => $request, 'farmers' => $_farmers ]
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
    public function store(Request $request, User $user) {
        $input = array(
            'role' => $request->role,
            'farmer_id' => $request->farmer_id,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => $request->password,
        );

        $rules = [
            'role' => ['required', 'integer'],
            'farmer_id' => [($request->role == 1 ? 'nullable' : 'required'), 'integer'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Rules\Password::min(8)]
        ];

        $rules['firstname'] = ['required', 'string', 'max:255'];
        $rules['lastname'] = ['required', 'string', 'max:255'];

        if ($request->role == 1) {
            $rules['firstname'][] = 'unique:users';
            $rules['lastname'][] = 'unique:users';
        }
        
        Validator::make($input, $rules)->validate();

        $image = null;
        if ((int) $request->role == 0 && (int) $request->farmer_id != 0) {
            $farmer = FarmerInformation::select('farmer_image')
                ->where('id', $request->farmer_id)
                ->first();

            $image = $farmer->farmer_image;
        }

        $created = User::create([
            'role' => $request->role,
            'farmer_id' => $request->farmer_id != 0 ? $request->farmer_id : 0,
            'firstname' => trim(strtolower($request->firstname)),
            'lastname' => trim(strtolower($request->lastname)), 
            'email' => trim(strtolower($request->email)),
            'profile_photo_path' => $image,
            'password' => Hash::make($request->password),
            'uuid'=>Str::random(12)
        ]);

        $state = $created ? true : false;

        return redirect()
            ->route('users.index')
            ->with([
                'response' => [
                    'state' => $state
                ]
            ]);
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
    public function update(Request $request, $id, User $user){
        $input = array(
            'role' => $request->role,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => $request->password,
        );

        $rules = [
            'role' => ['required', 'integer'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$id],
            'password' => ['nullable', 'string', Rules\Password::min(8)]
        ];

        $rules['firstname'] = ['required', 'string', 'max:255'];
        $rules['lastname'] = ['required', 'string', 'max:255'];

        if ($request->role == 1) {
            $rules['firstname'][] = 'unique:users,firstname,'.$id;
            $rules['lastname'][] = 'unique:users,lastname,'.$id;
        }
        
        $validated = Validator::make($input, $rules)->validate();

        $toUpdate = $user::find($id);
        $toUpdate->role = $request->role;
        $toUpdate->firstname = trim(strtolower($request->firstname));
        $toUpdate->lastname = trim(strtolower($request->lastname));
        $toUpdate->email = trim(strtolower($request->email));
        $toUpdate->password = $validated['password'] ? Hash::make($request->password) : $toUpdate->password;
        $toUpdate->save();

        $state = $toUpdate ? true : false;

        return redirect()
            ->route('users.index')
            ->with([
                'response' => [
                    'state' => $state
                ]
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function archive_user(Request $request, $id, User $user) {
        $resultset = array();

        if ($id) {
            $toArchive = $user::find($id);
            $toArchive->is_archived = 1;
            $toArchive->archived_by = $request->id;
            $toArchive->save();

            $users = User::paginate(25);
            $resultset["state"] = true;
            $resultset["updated"] = $toArchive;
            $resultset["users"] = $users;
            $resultset['message'] = 'User successfully deleted!';
        } else {
            $resultset["state"] = false;
            $resultset['message'] = 'Failed to delete user';
        }

        return response()->json($resultset);
    }
}
