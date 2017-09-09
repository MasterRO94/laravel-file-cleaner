<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTestCollectionTable extends Migration
{
	/**
	 * Create files table
	 */
	public function up()
	{
		Schema::create('test_collection', function (Blueprint $table) {
			$table->increments('id');
			$table->string('name');
			$table->unsignedInteger('file_id')->nullable();
			$table->timestamps();
		});
	}


	/**
	 * Drop files table
	 */
	public function down()
	{
		Schema::dropIfExists('test_collection');
	}
}