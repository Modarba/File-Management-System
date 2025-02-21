<?php

namespace App\Http\Controllers;

use App\Enums\HttpStatusCode;
use App\Http\Requests\userLoginRequest;
use App\Http\Requests\userRegisterRequest;
use App\Services\AuthenticationServices;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticationController extends Controller
{
    use  ApiResponse;
    protected $service;
    public function __construct(AuthenticationServices $services)
    {
        return $this->service = $services;
    }
    public function Register(userRegisterRequest $request)
    {
        $data=[
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make('password'),
        ];
        $register=$this->service->registerForUser($data);
        return $this->successResponse($register,'welcome',HttpStatusCode::CREATED->value);
    }
    public function Login(userLoginRequest $request)
    {
        $data=$request->only('email','password');
        $login=$this->service->loginForUser($data);
        return $this->successResponse($login,'welcome',HttpStatusCode::CREATED->value);
    }
}
