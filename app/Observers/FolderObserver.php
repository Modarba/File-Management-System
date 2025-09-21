<?php
namespace App\Observers;
use App\Models\Folder;
use Illuminate\Support\Facades\Redis;

class FolderObserver
{
    public function creating(Folder $folder)
    {
        $folder->path = $folder->generatePath();
    }
    public function created(Folder $folder)
    {
        if ($folder->type === 'file' && $folder->parent_id) {
            $this->updateParentSizes($folder->parent_id);
        }
    }
    public function updated(Folder $folder)
    {
        $originalParentId = $folder->getOriginal('parent_id');
        if ($originalParentId !== $folder->parent_id || $folder->isDirty('size')) {
            $this->updateParentSizes($originalParentId);
            $this->updateParentSizes($folder->parent_id);
            if ($originalParentId !== $folder->parent_id) {
                $folder->path = $folder->generatePath();
                $folder->saveQuietly();
                $this->updateDescendantPaths($folder);
            }
        }
    }
    public function deleted(Folder $folder)
    {
        if ($folder->parent_id) {
            $this->updateParentSizes($folder->parent_id);
        }
        Redis::del("folder_path_{$folder->id}");
    }
    protected function updateDescendantPaths(Folder $folder)
    {
        $descendants = $folder->children()->get(['id', 'path']);
        foreach ($descendants as $descendant) {
            $descendant->path = $descendant->generatePath();
            $descendant->saveQuietly();
            Redis::setex("folder_path_{$descendant->id}", 3600, $descendant->path); // Cache new path
            $this->updateDescendantPaths($descendant); // Recursive for nested descendants
        }
    }
    protected function updateParentSizes($parentId)
    {
        if (!$parentId) {
            return;
        }
        $cacheKey = "folder_size_{$parentId}";
        $parent = Folder::find($parentId, ['id', 'path']);
        if (!$parent) {
            Redis::del($cacheKey);
            return;
        }
        $cachedSize = Redis::get($cacheKey);
        if ($cachedSize !== null) {
            $parent->update(['size' => $cachedSize]);
            return;
        }
        $size = Folder::where('type', 'file')
            ->whereRaw('path LIKE CONCAT(?, "/%") OR path = ?', [$parent->path, $parent->path])
            ->sum('size');
        $parent->update(['size' => $size]);
        Redis::setex($cacheKey, 3600, $size);
        if ($parent->parent_id) {
            $this->updateParentSizes($parent->parent_id);
        }
    }

}
