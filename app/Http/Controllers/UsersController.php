<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        return view('users.index')->with(compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'asana_user_id' => $request->asana_user_id ? $request->asana_user_id : '',
            'uis_id' => $request->uis_id ? $request->uis_id : '',
            'amo_user_id' => $request->amo_user_id ? $request->amo_user_id : '',
            'extension_phone_number' => $request->extension_phone_number ? $request->extension_phone_number : '',
            'password' => Hash::make(md5(rand(1,10000))),
            'role' => $request->is_admin ? User::ROLE_ADMIN : User::ROLE_USER,
        ]);

        $request->session()->flash('message', 'Успешно добавлен!');

        return redirect('users');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        // dd($user);

        return redirect('users/' . $user->id . '/edit');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        return view('users.edit')->with(compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email,'.$user->id
        ]);

        $user->role = $request->is_admin ? User::ROLE_ADMIN : User::ROLE_USER;
        
        $user->name = $request->name;
        $user->email = $request->email;
        $user->uis_id = $request->uis_id ? $request->uis_id : '';
        $user->asana_user_id = $request->asana_user_id ? $request->asana_user_id : '';
        $user->amo_user_id = $request->amo_user_id ? $request->amo_user_id : '';
        $user->extension_phone_number = $request->extension_phone_number ? $request->extension_phone_number : '';
        $user->save();

        // dd($user->role, $request->is_admin);

        $request->session()->flash('message', 'Успешно изменено!');

        return redirect('users');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, User $user)
    {
        $user->delete();
        $request->session()->flash('message', 'Успешно удалён!');
        return redirect('users');
    }
}
