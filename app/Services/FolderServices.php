<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repository\FolderRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolderServices
{
    protected $folderRepository;
    public function __construct(FolderRepository $folderRepository)
    {
       return $this->folderRepository = $folderRepository;
    }
    public function addFolder(Request $request)
    {
        $user_id=Auth::id();
        $folder=$this->folderRepository->getRootFolder($user_id);
        $file=$request->file('files');
        $file1=time().'_'.$file->getClientOriginalExtension();
        if (!$folder)
        {
            $request->validate([
                'name'=>'required',
                'files'=>'required',
            ]);
            if ($request->has('parent_id'))
            {
                return $this->errorResponse('','You cant add parent here',HttpStatusCode::INTERNAL_SERVER_ERROR->value);
            }
            $root=$this->folderRepository->createFolder([
                'name'=>$request->name,
                'user_id'=>$user_id,
                'files'=>$file1,
            ]);
            return $this->successResponse($root,'success',HttpStatusCode::CREATED->value);
        }else{
            $request->validate([
                'name'=>'required',
                'files'=>'required',
                'parent_id'=>'required|integer|exists:folders,id'
            ]);
            $sub=$this->folderRepository->createFolder([
                'name'=>$request->name,
                'files'=>$file1,
                'user_id'=>$user_id,
                'parent_id'=>$request->parent_id,
            ]);
            return $this->successResponse($sub,'success',HttpStatusCode::CREATED->value);
        }
    }
    public function deleteFolder($id)
    {
        $delete=$this->folderRepository->deleteFolder($id);
        return $delete;

    }
    public function getById($id)
    {
        return $this->folderRepository->findById($id);
    }
    public function checkParent($id)
    {
        if ($this->getById($id)->parent_id == null)
        {
            return true;
        }
    }
    public function uploadFile(Request $request)
    {
        $file=$request->file('files');
        $file1=time().'_'.$file->getClientOriginalExtension();
        return $file1;
    }
    public function updateFolder($id,$data)
    {
        $folder = $this->getById($id);
        if (!$folder) {
            return ['message' => 'No Folder'];
        } else {
            return $this->folderRepository->updateFolder($id,$data);
        }
//        }if ($request->has('parent_id')) {
//            if ($folder->parent_id==Null)
//            {
//                return  $this->errorResponse('Not data','you cant update parent',HttpStatusCode::INTERNAL_SERVER_ERROR->value);
//            }
//        return $this->folderRepository->updateFolder([
//            'name'=>$folder->name,
//            'files'=>$folder->files,
//            'parent_id'=>$request->parent_id,
//        ]);
//        }
//        if ($request->has('name'))
//        {
//            return $this->folderRepository->updateFolder([
//                'name'=>$request->name,
//                'files'=>$folder->files,
//                'parent_id'=>$request->parent_id,
//            ]);
//        }
//    }
    }
}
