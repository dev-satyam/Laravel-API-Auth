<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\User;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetController extends Controller
{
    /**
     * Create token password reset
     *
     * @param  [string] email
     * @return [string] message
     */
    public function create(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'status' => 'failed',
                'message' => $error
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user)
            return response()->json([
                'status' => 'failed',
                'message' => 'We can\'t find a user with this e-mail address.'
            ], Response::HTTP_NOT_FOUND);
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => rand(1000, 9999)
            ]
        );
        if ($user && $passwordReset)
            $user->notify(
                new PasswordResetRequest($passwordReset->token)
            );
        return response()->json([
            'status' => 'success',
            'message' => 'OTP has been sent successfully to your email address.',
        ]);
    }

     /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function find(Request $request):JsonResponse
    {
        $passwordReset = PasswordReset::where('token', $request->token)
            ->first();
        if (!$passwordReset)
            return response()->json([
                 'status'=>'failed',
                'message' => 'This password reset OTP is invalid.'
            ], Response::HTTP_BAD_REQUEST);
        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                 'status'=>'failed',
                'message' => 'This password reset OTP is invalid.'
            ], Response::HTTP_BAD_REQUEST);
        }
        return response()->json([
            "status"=>"success",
            "password_reset"=>$passwordReset
            ]);
    }

    /**
     * Reset password
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [string] message
     * @return [json] user object
     */
    public function reset(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'token' => 'required|string'
        ],['password.regex' => 'Your password must be a minimum of 8 characters long, should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric and 1 special character.']);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'status' => 'failed',
                'message' => $error
            ], Response::HTTP_BAD_REQUEST);
        }

        $passwordReset = PasswordReset::where([
            ['token', $request->token],
            ['email', $request->email]
        ])->first();
        if (!$passwordReset)
            return response()->json([
                'status' => 'failed',
                'message' => 'This OTP is invalid.'
            ], Response::HTTP_BAD_REQUEST);
        $user = User::where('email', $passwordReset->email)->first();
        if (!$user)
            return response()->json([
                'status' => 'failed',
                'message' => 'We can\'t find a user with this e-mail address.'
            ], Response::HTTP_NOT_FOUND);
        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();
        $user->notify(new PasswordResetSuccess($passwordReset));
        return response()->json([
            "status" => "success",
            "message" => "Your password has been successfully changed."
        ]);
    }
}
