<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        User::truncate();
        $csvFile = fopen(base_path("database/data/test-data.csv"), "r");
        $firstline = true;
        while (($data = fgetcsv($csvFile, 2000, ",")) !== FALSE) {
            if (!$firstline) {
                User::create([
                    "email" => $data['1'],
                    "name" => $data['2'],
                    "birthday" => $data['3'],
                    "phone" => $data['4'],
                    "ip" => $data['5'],
                    "country" => $data['6'],
                    "password" => Hash::make('123456')
                ]);
            }
            $firstline = false;
        }

        fclose($csvFile);
    }

}
