<?php

namespace App\Console\Commands;

use App\Models\Folder;
use Illuminate\Console\Command;

class ChildCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'child-count {key} {id?}';

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
        $one=$this->argument('key');
        $id=$this->argument('id');
        switch ($one)
        {
            case '1':
                $folder=Folder::query()
                    ->withCount('child')
                    ->get();
                $this->info($folder);
                break;
            case '2':
                $folder=Folder::query()
                    ->where('id',$id)
                    ->withCount('child')
                    ->get();
                $this->info($folder);
        }
    }
}
