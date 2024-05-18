<?php

namespace App\Http\Controllers;

use App\Models\User; // Import the User class

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Import the Hash class

class UserController extends Controller
{
    public function create(Request $request)
    {
        
        $request->validate([
            "name"=> "required|max:255",
            "account_type"=> "required|in:Individual,Business",
            "balance"=>"required",
            "email"=> "required|email|max:255|unique:users",
            "password"=> "required|min:8",
        ]);
        $user = User::create([
            "name"=> $request->name,
            "account_type"=> $request->account_type,
            "balance"=>$request->balance,
            "email"=> $request->email,
            "password"=> Hash::make($request->password),
           
        ]);
        return response()->json($user,201);

    }

    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user && Hash::check($request->password, $user->password)) {
            return response()->json($user, 200);
        }
        return response()->json(['message' => 'Unauthorized'], 401);
    }

}
