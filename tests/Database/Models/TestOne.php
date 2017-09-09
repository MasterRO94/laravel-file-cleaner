<?php

namespace Tests\Database\Models;

use Illuminate\Database\Eloquent\Model;

class TestOne extends Model
{
	protected $table = 'test_one';

	protected $guarded = ['id'];


	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function files()
	{
		return $this->hasMany(File::class);
	}
}