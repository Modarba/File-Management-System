<?php

namespace App\Http\Controllers;

use App\Enums\HttpStatusCode;
use App\Services\FolderServices;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    protected $folderService;
    public function __construct(FolderServices $services)
    {
        return $this->folderService=$services;
    }
    public function addFolder(Request $request)
    {
        return $this->folderService->addFolder($request);
    }
    public function deleteFolder($id)
    {
        return $this->successResponse($this->folderService->deleteFolder($id),'success',HttpStatusCode::SUCCESS->value);
    }
    public function updateFolder($id,Request $request)
    {
        if ($request->has('files')&&$request->has('name')&&$request->has('parent_id'))
        {
            if ($this->folderService->checkParent($id))
            {
                return  $this->errorResponse('No data','You cant Update Parent',HttpStatusCode::INTERNAL_SERVER_ERROR->value);
            }
            $data=[
                'name'=>$request->name,
                'files'=>$this->folderService->uploadFile($request),
                'parent_id'=>$request->parent_id,
            ];
            return  $this->successResponse($this->folderService->updateFolder($id,$data),'success',HttpStatusCode::SUCCESS->value);
        }
        if ($request->has('name')) {
            $data=[
                'name'=>$request->name,
                'files'=>$this->folderService->getById($id)->files,
                'parent_id'=>$this->folderService->getById($id)->parent_id,
            ];
            return $this->successResponse($this->folderService->updateFolder($id,$data),'success',HttpStatusCode::SUCCESS->value);
        }
        if ($request->has('files'))
        {
            $data=[
                'name'=>$this->folderService->getById($id)->name,
                'parent_id'=>$this->folderService->getById($id)->parent_id,
                'files'=>$this->folderService->uploadFile($request)
            ];
            return $this->successResponse($this->folderService->updateFolder($id,$data),'success',HttpStatusCode::SUCCESS->value) ;
        }
        if ($request->has('parent_id')) {
            if ($this->folderService->checkParent($id))
            {
                return  $this->errorResponse('No Data','You cant update parent',HttpStatusCode::INTERNAL_SERVER_ERROR->value);
            }
            $data=[
                'name'=>$this->folderService->getById($id)->name,
                'files'=>$this->folderService->getById($id)->files,
                'parent_id'=>$request->parent_id,
            ];
            return  $this->successResponse($this->folderService->updateFolder($id,$data),'success',HttpStatusCode::SUCCESS->value);
        }
    }
}
