<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
	protected $table = 'activity_logs';

	protected $fillable = [
		'user_id',
		'event',
		'model',
		'model_id',
		'before',
		'after',
		'ip_address',
		'user_agent',
		'url',
	];

	// Automatically cast JSON fields back into arrays
	protected $casts = [
		'before' => 'array',
		'after' => 'array',
	];

    // Disable auditing for this model to prevent recursion
	protected bool $auditEnabled = false;

	// Optional: link to the user who performed the action
	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
