<?php

namespace App\Interface1;

use App\Http\Requests\userLoginRequest;
use App\Http\Requests\userRegisterRequest;
use Illuminate\Support\Facades\Request;

interface authenticationInterface
{
    public function register(userRegisterRequest $request);
    public function login(userLoginRequest $request);
}
