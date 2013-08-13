<?php

class ThingsTableSeeder extends Seeder {

	public function run()
	{
		// Uncomment the below to wipe the table clean before populating
		//DB::table('things')->truncate();
		DB::table('things')->delete();

		$things = array(
			array('name' => 'BIBSYS-dokument', 'created_at' => new DateTime, 'updated_at' => new DateTime),
			array('name' => 'PS3-kontroller', 'created_at' => new DateTime, 'updated_at' => new DateTime),
			array('name' => 'Skjøteledning', 'created_at' => new DateTime, 'updated_at' => new DateTime),
		);

		// Uncomment the below to run the seeder
		DB::table('things')->insert($things);
	}

}
