<?php

namespace App\Console\Commands;

use App\Models\Folder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;

class SizeFolder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:size {folder}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $folderId=$this->argument('folder');
        $get =Folder::query()
            ->where('id','=',$folderId)
            ->where('type','file')
            ->update(['size'=>2]);
        $this->info('success');
    }
}
