<?php

namespace App\Console\Commands;

use App\Models\Folder;
use App\Models\FolderPermission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ChildCount extends Command
{
    protected $signature = 'child:count {key : 1 for all folders, 2 for specific folder} {id? : Folder ID for key=2}';
    protected $description = 'Count child folders/files for all folders or a specific folder';

    public function handle()
    {
        $key = $this->argument('key');
        $id = $this->argument('id');

        if (!in_array($key, ['1', '2'])) {
            $this->error('Invalid key. Use 1 for all folders or 2 for a specific folder.');
            return 1;
        }

        if ($key === '2' && !$id) {
            $this->error('Folder ID is required for key=2.');
            return 1;
        }
        $cacheKey = $key === '1' ? 'child_count_all' : "child_count_{$id}";
        $results = Redis::get($cacheKey);
        if ($results === null) {
            if ($key === '1') {
                $folders = Folder::withCount('children')
                    ->where('user_id', auth()->id())
                    ->select(['id', 'name', 'path', 'type'])
                    ->get()
                    ->map(fn($folder) => [
                        'id' => $folder->id,
                        'name' => $folder->name,
                        'path' => $folder->path,
                        'type' => $folder->type,
                        'children_count' => $folder->children_count,
                    ])
                    ->toArray();
            } else {
                $folder = Folder::where('id', $id)
                    ->where('user_id', auth()->id())
                    ->first(['id', 'name', 'path', 'type']);
                if (!$folder) {
                    $this->error('Folder not found or unauthorized');
                    return 1;
                }
                if (!$this->hasPermission(auth()->id(), $id, 'read')) {
                    $this->error('No permission to view this folder');
                    return 1;
                }
                $folders = Folder::withCount('children')
                    ->where('id', $id)
                    ->select(['id', 'name', 'path', 'type'])
                    ->get()
                    ->map(fn($folder) => [
                        'id' => $folder->id,
                        'name' => $folder->name,
                        'path' => $folder->path,
                        'type' => $folder->type,
                        'children_count' => $folder->children_count,
                    ])
                    ->toArray();
            }

            $results = $folders;
            Redis::setex($cacheKey, 3600, json_encode($results));
        } else {
            $results = json_decode($results, true);
        }

        if (empty($results)) {
            $this->info('No folders found');
            return 0;
        }

        $this->table(['ID', 'Name', 'Path', 'Type', 'Children Count'], $results);
        return 0;
    }
    protected function hasPermission($userId, $folderId, $permission)
    {
        $cacheKey = "permissions_{$userId}_{$folderId}_{$permission}";
        $cachedPermission = Redis::get($cacheKey);

        if ($cachedPermission !== null) {
            return (bool) $cachedPermission;
        }

        $folder = Folder::find($folderId, ['user_id']);
        if ($folder && $folder->user_id == $userId) {
            Redis::setex($cacheKey, 3600, true);
            return true;
        }

        $hasPermission = FolderPermission::where('user_id', $userId)
            ->where('folder_id', $folderId)
            ->where('permission', $permission)
            ->exists();

        Redis::setex($cacheKey, 3600, $hasPermission);
        return $hasPermission;
    }
}
