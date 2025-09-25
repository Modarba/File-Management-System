<?php
namespace App\Http\Controllers;
use App\Enums\HttpStatusCode;
use App\Jobs\GenerateZipArchive;
use App\Models\Folder;
use App\Models\FolderPermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class QueryController extends Controller
{
    public function nameOfFolder(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $cacheKey = 'duplicate_folders_' . md5($request->path);
        $folders = Redis::get($cacheKey);
        if ($folders === null) {
            $folders = Folder::where('path', 'like', $request->path . '%')
                ->where('user_id', Auth::id())
                ->groupBy('name')
                ->select('name', DB::raw('count(*) as count'))
                ->havingRaw('count(*) > 1')
                ->get()
                ->toArray();
            Redis::setex($cacheKey, 3600, json_encode($folders));
        } else {
            $folders = json_decode($folders, true);
        }
        return $this->successResponse($folders, 'Duplicate folder names retrieved successfully', HttpStatusCode::SUCCESS->value);
    }
    public function deleteOfFolder(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $folders = Folder::where('path', 'like', $request->path . '%')
            ->where('user_id', Auth::id())
            ->get(['id', 'name', 'path']);
        $folderIdsToDelete = [];
        $nameCounts = [];
        foreach ($folders as $folder) {
            if (!$this->hasPermission(Auth::id(), $folder->id, 'delete')) {
                continue;
            }
            $nameCounts[$folder->name][] = $folder->id;
        }
        foreach ($nameCounts as $name => $ids) {
            if (count($ids) > 1) {
                $keepId = min($ids);
                $folderIdsToDelete = array_merge($folderIdsToDelete, array_diff($ids, [$keepId]));
            }
        }
        if (!empty($folderIdsToDelete)) {
            Folder::whereIn('id', $folderIdsToDelete)->delete();
            foreach ($folderIdsToDelete as $id) {
                Redis::del("folder_path_{$id}");
                Redis::del("folder_size_{$id}");
            }
        }

        return $this->successResponse(null, 'Duplicate folders deleted successfully', HttpStatusCode::SUCCESS->value);
    }
    public function nameNotFound(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $cacheKey = 'unique_folders_' . md5($request->path);
        $folders = Redis::get($cacheKey);
        if ($folders === null) {
            $folders = Folder::where('path', 'like', $request->path . '%')
                ->where('user_id', Auth::id())
                ->groupBy('name')
                ->select('name', DB::raw('count(*) as count'))
                ->havingRaw('count(*) = 1')
                ->get()
                ->toArray();
            Redis::setex($cacheKey, 3600, json_encode($folders));
        } else {
            $folders = json_decode($folders, true);
        }
        return $this->successResponse($folders, 'Unique folder names retrieved successfully', HttpStatusCode::SUCCESS->value);
    }
    public function downloadQueue(Request $request)
    {
        $request->validate(['folderId' => 'required|exists:folders,id']);
        $folderId = $request->input('folderId');
        $userId = Auth::id();
        if (!$this->hasPermission($userId, $folderId, 'read')) {
            return $this->errorResponse('No permission to download', 'error', HttpStatusCode::FORBIDDEN->value);
        }
        $folder = Folder::findOrFail($folderId, ['path']);
        $jobId = uniqid('zip_', true);
        GenerateZipArchive::dispatch($folder->path, $userId, $jobId);
        Redis::setex("download_job_{$jobId}", 3600, 'queued');

        return $this->successResponse(['job_id' => $jobId], 'Download job queued successfully', HttpStatusCode::SUCCESS->value);
    }
    public function userNoFolder()
    {
        $cacheKey = 'users_no_folder';
        $users = Redis::get($cacheKey);

        if ($users === null) {
            $users = User::whereDoesntHave('folders')
                ->select(['id', 'name', 'email'])
                ->get()
                ->toArray();
            Redis::setex($cacheKey, 3600, json_encode($users));
        } else {
            $users = json_decode($users, true);
        }
        return $this->successResponse($users, 'Users without folders retrieved successfully', HttpStatusCode::SUCCESS->value);
    }
    public function userHasAtLeastOneFolder()
    {
        $cacheKey = 'users_with_folder';
        $users = Redis::get($cacheKey);

        if ($users === null) {
            $users = User::whereHas('folders')
                ->select(['id', 'name'])
                ->get()
                ->toArray();
            Redis::setex($cacheKey, 3600, json_encode($users));
        } else {
            $users = json_decode($users, true);
        }

        return $this->successResponse($users, 'Users with folders retrieved successfully', HttpStatusCode::SUCCESS->value);
    }
    public function userNoFile()
    {
        $cacheKey = 'users_no_file';
        $users = Redis::get($cacheKey);

        if ($users === null) {
            $users = User::whereDoesntHave('files')
                ->select(['id', 'name', 'email'])
                ->get()
                ->toArray();
            Redis::setex($cacheKey, 3600, json_encode($users));
        } else {
            $users = json_decode($users, true);
        }

        return $this->successResponse($users, 'Users without files retrieved successfully', HttpStatusCode::SUCCESS->value);
    }
    public function userAtLeastFile()
    {
        $cacheKey = 'users_with_file';
        $users = Redis::get($cacheKey);

        if ($users === null) {
            $users = User::whereHas('files')
                ->select(['id', 'name'])
                ->get()
                ->toArray();
            Redis::setex($cacheKey, 3600, json_encode($users));
        } else {
            $users = json_decode($users, true);
        }

        return $this->successResponse($users, 'Users with files retrieved successfully', HttpStatusCode::SUCCESS->value);
    }
    public function folderNoFile()
    {
        $cacheKey = 'folders_no_file';
        $folders = Redis::get($cacheKey);

        if ($folders === null) {
            $folders = Folder::whereDoesntHave('files')
                ->where('type', 'folder')
                ->where('user_id', Auth::id())
                ->select(['id', 'name', 'path', 'type'])
                ->get()
                ->toArray();
            Redis::setex($cacheKey, 3600, json_encode($folders));
        } else {
            $folders = json_decode($folders, true);
        }
        return $this->successResponse($folders, 'Folders without files retrieved successfully', HttpStatusCode::SUCCESS->value);
    }
    public function betweenSize(Request $request)
    {
        $request->validate([
            'from' => 'required|integer|min:0',
            'to' => 'required|integer|gte:from',
        ]);
        $cacheKey = 'folders_size_' . md5($request->from . '_' . $request->to);
        $folders = Redis::get($cacheKey);
        if ($folders === null) {
            $folders = Folder::whereBetween('size', [$request->from, $request->to])
                ->where('user_id', Auth::id())
                ->select(['id', 'name', 'path', 'type', 'size'])
                ->get()
                ->toArray();
            Redis::setex($cacheKey, 3600, json_encode($folders));
        } else {
            $folders = json_decode($folders, true);
        }
        return $this->successResponse($folders, 'Folders by size retrieved successfully', HttpStatusCode::SUCCESS->value);
    }
    protected function hasPermission($userId, $folderId, $permission)
    {
        if (is_null($folderId)) {
            return Auth::id() == $userId;
        }
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

