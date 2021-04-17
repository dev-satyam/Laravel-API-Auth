<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{

    /**
     * User login
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'error' => 'Email or password is invalid',
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var User $user */
        $userInfo = User::where('email', $request->email)->first();

        $tokenResult = $userInfo->createToken('authToken')->plainTextToken;

        return response()->json([
            'access_token' => explode('|', $tokenResult)[1],
            'token_type' => 'Bearer'
        ]);
    }


    /**
     * User Register
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
        ], ['password.regex' => 'Your password must be a minimum of 8 characters long, should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric and 1 special character.']);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        DB::beginTransaction();
        try {
            return response()->json([
                'message' => 'Registered successfully.',
                'user' => User::create([
                    'name' => $request->name,
                    'email' =>  $request->email,
                    'password' => bcrypt($request->password)
                ])
            ]);
        } catch (\Exception | \TypeError $exception) {
            DB::rollBack();

            return response()->json([
                'error' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'messgae' => 'Successfully logged out'
        ]);
    }
}
