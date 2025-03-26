<?php

namespace App\Console\Commands;

use App\Models\Folder;
use Illuminate\Console\Command;

class OrderBy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order-by {folder} {id?}';

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
     $folder=$this->argument('folder');
     $id=$this->argument('id');
        switch ($this->input->getArgument('folder')) {
            case '1':
                 $one=Folder::query()
                    ->orderBy('size')->get();
                 $this->info($one);
                break;
            case  '2':
                $tow=Folder::query()
                    ->orderBy('created_at')->get();
                $this->info($tow);
                break;
                case '3':
                    $three=Folder::query()
                        ->where('path','like',"%$id%")
                        ->orderBy('updated_at')->get();
                    $this->info($three);
                    break;
                    case '4':
                        $four=Folder::query()
                            ->withCount('child')
                            ->orderBy('child_count')->get();
                        $this->info($four);
                        break;
        }
    }
}
