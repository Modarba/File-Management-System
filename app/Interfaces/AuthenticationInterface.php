<?php

namespace App\Interfaces;

use App\Http\Requests\userLoginRequest;
use App\Http\Requests\userRegisterRequest;
use Illuminate\Support\Facades\Request;

interface AuthenticationInterface
{
    public function register(array $data);
    public function findByEmail(string $email);
}
