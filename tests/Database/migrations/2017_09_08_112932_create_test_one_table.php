<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTestOneTable extends Migration
{
	/**
	 * Create files table
	 */
	public function up()
	{
		Schema::create('test_one', function (Blueprint $table) {
			$table->increments('id');
			$table->string('name');
			$table->timestamps();
		});
	}


	/**
	 * Drop files table
	 */
	public function down()
	{
		Schema::dropIfExists('test_one');
	}
}