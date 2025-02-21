<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('register', [AuthenticationController::class, 'Register']);
Route::post('login', [AuthenticationController::class, 'Login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('folder')->group(function () {
        Route::get('getAll', [UserController::class, 'getAllFileForUser']);
        Route::get('sub', [UserController::class, 'rootBelongsToFolder']);
        Route::delete('delete/{id}', [\App\Http\Controllers\FolderController::class, 'deleteFolder']);
        Route::post('add',[\App\Http\Controllers\FolderController::class,'addFolder']);
        Route::post('update/{id}',[\App\Http\Controllers\FolderController::class,'updateFolder']);
    });
    Route::prefix('child')->group(function () {
        Route::get('/', [UserController::class, 'childHasManyFolder']);
        Route::get('recursive', [UserController::class, 'getChildRecursive']);
    });
});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {

    return $request->user();
});
