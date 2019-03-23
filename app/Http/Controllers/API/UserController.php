<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        //$this->authorize('isAdmin');
        if (\Gate::allows('isAdmin') || \Gate::allows('isAuthor')) {
            return User::latest()->paginate(5);
        }
    }


    public function store(Request $request)
    {
        $this->validate($request, [
            'name'      => 'required|string|max:191',
            'email'     => 'required|string|email|max:191|unique:users',
            'password'  => 'required|min:6'
        ]);

        return User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
            'bio' => $request['bio'],
            'type' => $request['type'],
            'photo' => $request['photo']
        ]);
    }


    public function show($id)
    {
        //
    }

    public function profile()
    {
        return auth('api')->user();
    }

    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();

        $this->validate($request, [
            'name'      => 'required|string|max:191',
            'email'     => 'required|string|email|max:191|unique:users,email,'.$user->id,
            'password' => 'sometimes|required|min:6'
        ]);

        $currentPhoto = $user->photo;

        if($request->photo != $currentPhoto){
            $name = time().'.'.explode('/', explode(':', 
            substr($request->photo, 0, strpos($request->photo, ';')))[1])[1];

            \Image::make($request->photo)->save(public_path('img/profile/').$name);

            $request->merge(['photo' => $name]);

            $userPhoto = public_path('img/profile/').$currentPhoto;
            if(file_exists($userPhoto)){
                @unlink($userPhoto);
            }
        }

        if(!empty($request->password)){
            $request->merge(['password' => Hash::make($request->password)]);
        }

        $user->update($request->all());
        return ['message' => "Success"];
    }


    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->validate($request, [
            'name'      => 'required|string|max:191',
            'email'     => 'required|string|email|max:191|unique:users,email,'.$user->id,
            'password'  => 'sometimes|min:6'
        ]);
        $user->update($request->all());
        return ['message', 'User Info Updated.'];
    }

    public function destroy($id)
    {
        $this->authorize('isAdmin');

        $user = User::findOrFail($id);
        $user->delete();
        return ['message' => 'User Deleted.'];
    }

    public function search() 
    {
        if($search = \Request::get('q')){
            $users = User::where(function($query) use ($search){
                $query->where('name', 'LIKE', "%$search%")
                      ->orWhere('email', 'LIKE', "%$search%")
                      ->orWhere('type', 'LIKE', "%$search%");
            })->paginate(10);
        }else{
            $users = User::latest()->paginate(5);
        }

        return $users;
    }
}
