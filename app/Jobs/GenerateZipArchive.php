<?php
namespace App\Jobs;
use App\Models\Folder;
use App\Models\FolderPermission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
class GenerateZipArchive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $userId;
    protected $zipPath;
    protected $folderPath;
    protected $jobId;
    public function __construct($folderPath, $userId, $jobId = null)
    {
        $this->folderPath = $folderPath;
        $this->userId = $userId;
        $this->jobId = $jobId ?? uniqid('zip_', true);
        $this->zipPath = "archives/user_{$userId}_" . time() . ".zip";
    }
    public function handle()
    {
        Redis::setex("download_job_{$this->jobId}", 3600, 'processing');
        try {
            $folder = Folder::where('path', $this->folderPath)
                ->where('user_id', $this->userId)
                ->first(['id', 'name', 'path', 'type']);
            if (!$folder) {
                Redis::setex("download_job_{$this->jobId}", 3600, 'failed');
                throw new \Exception('Folder not found or unauthorized');
            }
            if (!$this->hasPermission($this->userId, $folder->id, 'read')) {
                Redis::setex("download_job_{$this->jobId}", 3600, 'failed');
                throw new \Exception('No permission to download this folder');
            }
            $zipFullPath = storage_path("app/{$this->zipPath}");
            Storage::makeDirectory(dirname($this->zipPath));
            $zip = new ZipArchive();
            if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                Redis::setex("download_job_{$this->jobId}", 3600, 'failed');
                throw new \Exception('Failed to create ZIP archive');
            }
            $this->addFilesToZip($folder, $zip);
            $zip->close();
            Redis::setex("zip_path_{$this->jobId}", 3600, $this->zipPath);
            Redis::setex("download_job_{$this->jobId}", 3600, 'completed');
            return $this->zipPath;
        } catch (\Exception $e) {
            Redis::setex("download_job_{$this->jobId}", 3600, 'failed');
            throw $e;
        }
    }
    private function addFilesToZip(Folder $folder, ZipArchive $zip, $relativePath = '')
    {
        $cacheKey = "folder_contents_{$folder->id}";
        $items = Redis::get($cacheKey);
        if ($items === null) {
            $items = Folder::where('path', 'like', $folder->path . '/%')
                ->orWhere('id', $folder->id)
                ->where('user_id', $this->userId)
                ->select(['id', 'name', 'path', 'type'])
                ->get()
                ->toArray();
            Redis::setex($cacheKey, 3600, json_encode($items));
        } else {
            $items = json_decode($items, true);
        }
        if ($folder->type === 'folder') {
            $zip->addEmptyDir($folder->name);
        } elseif ($folder->type === 'file') {
            $filePath = storage_path('app/public/uploads/' . $folder->name);
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $relativePath ? $relativePath . '/' . $folder->name : $folder->name);
            }
        }

        foreach ($items as $item) {
            if ($item['id'] === $folder->id) {
                continue;
            }

            $relativeItemPath = str_replace($folder->path . '/', '', $item['path']);
            $zipPath = $folder->name . ($relativeItemPath ? '/' . $relativeItemPath : '');

            if ($item['type'] === 'folder') {
                $zip->addEmptyDir($zipPath);
            } else {
                $filePath = storage_path('app/public/uploads/' . $item['name']);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $zipPath . '/' . $item['name']);
                }
            }
        }
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
    public function getJobId()
    {
        return $this->jobId;
    }
}
