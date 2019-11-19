<?php

namespace Stylemix\Translations\Models;

use Stylemix\Translations\Contracts\TranslationString as TranslationStringContract;
use Illuminate\Database\Eloquent\Model;

/**
 * Translation model
 *
 * @property integer $id
 * @property string  $namespace
 * @property string  $group
 * @property string  $key
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TranslationString extends Model implements TranslationStringContract
{

	protected $fillable = [
		'namespace',
		'group',
		'key',
	];
}
