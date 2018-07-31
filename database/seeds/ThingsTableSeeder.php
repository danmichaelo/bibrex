<?php

use Illuminate\Database\Seeder;

class ThingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('things')->insert([
            [
                'name' => 'Skjøteledning',
                'created_at' => new DateTime,
                'updated_at' => new DateTime,
                'properties' => json_encode([
                    'loan_time' => 1,
                    'name_indefinite' => [
                        'nob' => 'en skjøteledning',
                        'nno' => 'ein skøyteleidning',
                        'eng' => 'an extension coord',
                    ],
                    'name_definite' => [
                        'nob' => 'skjøteledningen',
                        'nno' => 'skøyteleidningen',
                        'eng' => 'the extension coord',
                    ],
                ]),
            ],
        ]);
    }
}
