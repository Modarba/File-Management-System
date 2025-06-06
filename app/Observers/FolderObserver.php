<?php
namespace App\Observers;
use App\Models\Folder;
class FolderObserver
{
    public function created(Folder $folder)
    {
        $this->updateFolderSize($folder->parent_id);
    }
    public function updated(Folder $folder)
    {
        $originalParentId = $folder->getOriginal('parent_id');
        $currentParentId = $folder->parent_id;
            $this->updateFolderSize($originalParentId);
            $this->updateFolderSize($currentParentId);
    }
    public function deleted(Folder $folder)
    {
        $parentId = $folder->getOriginal('parent_id');
        $this->updateFolderSize($parentId);
    }
    protected function updateFolderSize($folderId)
    {
        $folder = Folder::find($folderId);
        if (!$folder) {
            return;
        }
        $folderPath = $folder->path;
        if (!$folderPath) {
            return;
        }
        $folderIds = explode("/", $folderPath);
        foreach ($folderIds as $parentId)
        {
            $parentFolder = Folder::find($parentId);
            if (!$parentFolder) {
                continue;
            }
            $parentPath = $parentFolder->path;
            $size = Folder::query()
                ->where(function($query) use ($parentPath) {
                    $query->where('path', $parentPath)
                        ->orWhere('path', 'like', $parentPath . '/%');
                })
                ->where('type', 'file')
                ->sum('size');
            $parentFolder->update(['size' => $size]);
        }
    }
}
