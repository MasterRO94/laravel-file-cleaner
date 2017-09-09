<?php

namespace Tests\Database\Models;

use Illuminate\Database\Eloquent\Model;

class TestCollection extends Model
{
	protected $table = 'test_collection';

	protected $guarded = ['id'];


	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function file()
	{
		return $this->belongsTo(File::class);
	}
}