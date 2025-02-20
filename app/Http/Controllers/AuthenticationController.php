<?php

namespace App\Http\Controllers;

use App\Http\Requests\userLoginRequest;
use App\Http\Requests\userRegisterRequest;
use App\Services\authenticationServices;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AuthenticationController extends Controller
{
    use  ApiResponse;
    public function Register(authenticationServices $services, userRegisterRequest $request)
    {
        return $services->registerForUser($request);
    }
    public function Login(authenticationServices $services, userLoginRequest $request)
    {
        return $services->loginForUser($request);
    }
}
