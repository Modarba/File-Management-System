<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Models\Folder;
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
    public function getRootForFolder()
    {
        return $this->folderRepository->getRootFolder();
    }
    public function createFolder($data)
    {
        return $this->folderRepository->createFolder($data);
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
    public function updateFolder($id,$data)
    {
        $folder = $this->getById($id);
        if (!$folder) {
            return ['message' => 'No Folder'];
        } else {
            return $this->folderRepository->updateFolder($id,$data);
        }
    }
    public function checkMoveParent($id)
    {
        return $this->folderRepository->checkMoveParent($id);
    }
}
