<?php

namespace App\Repository;

use App\Enums\HttpStatusCode;
use App\Http\Requests\userLoginRequest;
use App\Http\Requests\userRegisterRequest;
use App\Interfaces\AuthenticationInterface;
use App\Models\Folder;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticationRepository implements AuthenticationInterface
{
    use ApiResponse;
    public function register($data)
    {
        // TODO: Implement register() method.
        return User::create($data);
    }
    public function findByEmail(string $email)
    {
        // TODO: Implement findByEmail() method.
        return  User::where('email',$email)->first();
    }

}
