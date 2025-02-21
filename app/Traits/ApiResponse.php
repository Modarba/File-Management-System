<?php

namespace App\Traits;

use App\Enums\HttpStatusCode;
use Illuminate\Validation\Rules\Enum;

trait ApiResponse
{
    public function successResponse($data, string $message, int $statusCode)
    {
        return response()->json(['data'=>$data,'Message'=>$message ,'status'=>true],$statusCode);
    }
    public function errorResponse($data,string $message, int $statusCode)
    {
        return response()->json(['data'=>$data, 'Message'=>$message],$statusCode);
    }

}
