<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GenerateZipArchive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $userID;
    protected $zipPath;
    protected $folderPath;
    /**
     * Create a new job instance.
     */
    public function __construct($folderPath,$userID)
    {
        $this->folderPath=$folderPath;
        $this->userID=$userID;
        $this->zipPath = "archives/user_{$userID}_" . time() . ".zip";

    }
    /**
     * Execute the job.
     */
    public function handle()
    {
        $zip=new ZipArchive();
        $zipFullPath = storage_path("app/{$this->zipPath}");
        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $this->addFilesToZip(Storage::path($this->folderPath), $zip);
            $zip->close();
        }
        return $this->zipPath;
    }
    private function addFilesToZip($folder, $zip, $relativePath = '')
    {
        if (!is_dir($folder)) return;
        $files = collect(scandir($folder))->filter(fn($file) => !in_array($file, ['.', '..']));
        foreach ($files as $file) {
            $filePath = "{$folder}/{$file}";
            $relativeFilePath = $relativePath ? "{$relativePath}/{$file}" : $file;

            if (is_dir($filePath)) {
                $this->addFilesToZip($filePath, $zip, $relativeFilePath);
            } else {
                $zip->addFile($filePath, $relativeFilePath);
            }
        }
    }
}
