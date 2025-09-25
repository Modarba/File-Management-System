<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
Route::get('getUser',[UserController::class,'index']);
Route::prefix('user')->name('auth.')->group(function () {
    Route::post('register', [AuthenticationController::class, 'Register'])->name('register');
    Route::post('login', [AuthenticationController::class, 'Login'])->name('login');
});
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('folder')->name('folder.')->group(function () {
        Route::post('between',[\App\Http\Controllers\QueryController::class,'betweenSize']);
        Route::get('/NoFile',[\App\Http\Controllers\QueryController::class,'folderNoFile']);
        Route::get('order/{id}',[\App\Http\Controllers\QueryController::class,'orderFolder']);
        Route::get('noFolder',[\App\Http\Controllers\QueryController::class,'userNoFolder']);
        Route::get('oneFolder',[\App\Http\Controllers\QueryController::class,'userHasAtLeastOneFolder']);
        Route::post('nameNot',[\App\Http\Controllers\QueryController::class,'nameNotFound']);
        Route::post('downloadQue',[\App\Http\Controllers\QueryController::class,'downloadQueue']);
        Route::post('name',[\App\Http\Controllers\QueryController::class,'nameOfFolder']);
        Route::post('deleteOf',[\App\Http\Controllers\QueryController::class,'deleteOfFolder']);
        Route::get('getAll', [UserController::class, 'getAllFileForUser'])->name('getAll');
        Route::post('get/{id}',[\App\Http\Controllers\FolderController::class,'update']);
        Route::get('sub', [UserController::class, 'rootBelongsToFolder'])->name('sub');
        Route::delete('delete/{id}', [\App\Http\Controllers\FolderController::class, 'deleteFolder'])->name('delete');
        Route::post('add',[\App\Http\Controllers\FolderController::class,'addFolder'])->name('add');
        Route::post('update/{id}',[\App\Http\Controllers\FolderController::class,'updateFolder'])->name('update');
        Route::post('Ansc',[\App\Http\Controllers\FolderController::class,'search']);
        Route::get('path',[\App\Http\Controllers\FolderController::class,'getPath']);
        Route::get('download/{id}/download',[\App\Http\Controllers\FolderController::class,'downloadFolder']);
        Route::post('extract',[\App\Http\Controllers\FolderController::class,'unzipFolder']);
        Route::get('addPermission/{userID}/{folder_id}/{permission}',[\App\Http\Controllers\FolderController::class,'givePermission'])->name('addPermission');
        Route::delete('deletePermission/{id}/{user_id}/{permission}',[\App\Http\Controllers\FolderController::class,'deletePermission'])->name('deletePermission');
        Route::get('updatePermission/{id}/{user_id}/{permission}',[\App\Http\Controllers\FolderController::class,'updatePermission'])->name('deletePermission');
    });

    Route::prefix('child')->name('child.')->group(function () {
        Route::get('/', [UserController::class, 'childHasManyFolder'])->name('child');
        Route::get('recursive', [UserController::class, 'getChildRecursive'])->name('recursive');
    });
});












Route::middleware('auth:sanctum')->get('/user', function (Request $request) {

    return $request->user();
});
