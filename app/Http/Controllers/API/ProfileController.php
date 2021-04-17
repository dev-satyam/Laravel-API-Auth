<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    /**
     * Update user profile
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user): JsonResponse
    {

        $rules = array(
            'avatar' => 'mimes:jpeg,jpg,png,gif|required|max:10000' // max 10000kb
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        $avatar = $request->file('avatar');

        $avatar = $user->makeAvatar($avatar);
        $request->user()->update([
            'name' =>  $request->name,
            'email' => $request->email,
            'avatar' => $avatar
        ]);

        return response()->json([
            'message' => 'Your has been updated successfully!',
            'data' => $request->user()]);
    }
}
