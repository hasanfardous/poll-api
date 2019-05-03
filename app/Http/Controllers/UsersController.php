<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UsersController extends Controller
{
    public function authenticate(Request $request)
    {
        try {
            $this->validate($request, [
                'email'     => 'required|email',
                'password'  => 'required|min:6',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }

        // var_dump(app('auth')->attempt($request->only('email', 'password')));

        // Attempt login
        $credentials = $request->only("email", "password");

        if (!$token = Auth::attempt($credentials)) {
            throw ValidationException::withMessages(["login" => "Incorrect email or password."]);
        }

        return [
            "token" => [
                "access_token" => $token,
                "token_type"   => "Bearer",
                "expire"       => (int) Auth::guard()->factory()->getTTL()
            ]
        ];
    }


    public function index()
    {
        $users = app('db')->table('users')->get();
        return response()->json($users);
    }


    public function create(Request $request)
    {
        try {
            $this->validate($request, [
                'full_name' => 'required|min:3',
                'username'  => 'required|min:3',
                'email'     => 'required|email',
                'password'  => 'required|min:6',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }

        try {
            $id = app('db')->table('users')->insertGetId([
                'full_name' => trim($request->input('full_name')),
                'username'  => strtolower(trim($request->input('username'))),
                'email'     => strtolower(trim($request->input('email'))),
                'password'  => app('hash')->make($request->input('password')),
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);

            $user = app('db')->table('users')->select('full_name', 'username', 'email')->where('id', $id)->first();

            return response()->json([
                'id' => $id,
                'full_name' => $user->full_name,
                'username'  => $user->username,
                'email'     => $user->email
            ], 201);
        } catch (\PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
        
        return 'User created';
    }
}
