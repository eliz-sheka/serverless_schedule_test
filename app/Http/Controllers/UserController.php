<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController
{
    public function index(): JsonResponse
    {
        return new JsonResponse(User::all());
    }

    public function getMeetings(User $user): JsonResponse
    {
        $user->load('meetings');

        return new JsonResponse($user);
    }
}
