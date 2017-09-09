<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilesTable extends Migration
{
	/**
	 * Create files table
	 */
	public function up()
	{
		Schema::create('files', function (Blueprint $table) {
			$table->increments('id');
			$table->string('name');
			$table->unsignedInteger('test_one_id')->nullable();
			$table->timestamps();
		});
	}


	/**
	 * Drop files table
	 */
	public function down()
	{
		Schema::dropIfExists('files');
	}
}