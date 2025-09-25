<?php

namespace App\Console\Commands;

use App\Models\Folder;
use App\Models\FolderPermission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class OrderBy extends Command
{
    protected $signature = 'folder:order-by {key : 1 for size, 2 for created_at, 3 for updated_at by path, 4 for children count} {id? : Folder ID for key=3}';
    protected $description = 'Order folders by size, creation date, update date, or children count';

    public function handle()
    {
        $key = $this->argument('key');
        $id = $this->argument('id');

        if (!in_array($key, ['1', '2', '3', '4'])) {
            $this->error('Invalid key. Use 1 for size, 2 for created_at, 3 for updated_at, or 4 for children count.');
            return 1;
        }
        if ($key === '3' && !$id) {
            $this->error('Folder ID is required for key=3');
            return 1;
        }
        $cacheKey = $key === '3' ? "order_by_{$key}_{$id}" : "order_by_{$key}";
        $results = Redis::get($cacheKey);
        if ($results === null) {
            $query = Folder::query()
                ->where('user_id', auth()->id())
                ->select(['id', 'name', 'path', 'type', 'size', 'created_at', 'updated_at']);
            if ($key === '1') {
                $folders = $query->orderBy('size')->get();
            } elseif ($key === '2') {
                $folders = $query->orderBy('created_at')->get();
            } elseif ($key === '3') {
                $folder = Folder::where('id', $id)
                    ->where('user_id', auth()->id())
                    ->first(['id', 'path']);
                if (!$folder) {
                    $this->error('Folder not found or unauthorized');
                    return 1;
                }
                if (!$this->hasPermission(auth()->id(), $id, 'read')) {
                    $this->error('No permission to view this folder');
                    return 1;
                }
                $folders = Folder::where('path', 'like', $folder->path . '/%')
                    ->orWhere('id', $id)
                    ->orderBy('updated_at')
                    ->select(['id', 'name', 'path', 'type', 'size', 'created_at', 'updated_at'])
                    ->get();
            } else {
                $folders = $query->withCount('children')->orderBy('children_count')->get();
            }
            $results = $folders->map(fn($folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
                'path' => $folder->path,
                'type' => $folder->type,
                'size' => $folder->size,
                'created_at' => $folder->created_at,
                'updated_at' => $folder->updated_at,
                'children_count' => $folder->children_count ?? null,
            ])->toArray();
            Redis::setex($cacheKey, 3600, json_encode($results));
        } else {
            $results = json_decode($results, true);
        }
        if (empty($results)) {
            $this->info('No folders found.');
            return 0;
        }
        $headers = ['ID', 'Name', 'Path', 'Type', 'Size', 'Created At', 'Updated At'];
        if ($key === '4') {
            $headers[] = 'Children Count';
        }
        $this->table($headers, $results);
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
