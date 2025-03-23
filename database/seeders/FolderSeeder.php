<?php

namespace Database\Seeders;

use App\Models\Folder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $data=
            [
            //id=1
            ['user_id'=>1,'parent_id'=>null,'files'=>'pdf1','name'=>'A','path'=>1],
            //id=2
            ['user_id'=>1,'parent_id'=>1,'files'=>'pdf2','name'=>'B','path'=>'1/2'],
            //id=3
            ['user_id'=>1,'parent_id'=>1,'files'=>'pdf3','name'=>'C','path'=>'1/3'],
            //id=4
            ['user_id'=>1,'parent_id'=>2,'files'=>'pdf5','name'=>'E','path'=>'1/2/4'],
            //id=5
            ['user_id'=>1,'parent_id'=>2,'files'=>'pdf5','name'=>'F','1/2/5'],
            //id=6
            ['user_id'=>1,'parent_id'=>3,'files'=>'pdf4','name'=>'D','path'=>'1/3/6'],
            //id=7
            ['user_id'=>1,'parent_id'=>4,'files'=>'pdf4','name'=>'X',]

            ];
        DB::table('folders')->insert($data);
    }
}
