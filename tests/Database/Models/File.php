<?php

namespace Tests\Database\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
	protected $guarded = ['id'];


	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function testOne()
	{
		return $this->belongsTo(TestOne::class);
	}


	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function testCollection()
	{
		return $this->hasMany(TestCollection::class);
	}
}
