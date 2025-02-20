<?php

namespace App\Traits;

trait ApiResponse
{
    public function successResponse($data, string $message, int $statusCode=201)
    {
        return response()->json(['data'=>$data,'Message'=>$message ,'status'=>true],$statusCode);
    }
    public function errorResponse($data,string $message, int $statusCode=400)
    {
        return response()->json(['data'=>$data, 'Message'=>$message],$statusCode);
    }

}
