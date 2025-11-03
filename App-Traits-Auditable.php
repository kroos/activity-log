<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

trait Auditable
{
    /**
     * Global auditing switch.
     */
    protected static bool $auditingEnabled = true;

    /**
     * Temporarily disable auditing globally.
     */
    public static function disableAuditing(): void
    {
    	static::$auditingEnabled = false;
    }

    /**
     * Re-enable auditing globally.
     */
    public static function enableAuditing(): void
    {
    	static::$auditingEnabled = true;
    }

    /**
     * Check if auditing is enabled.
     */
    public static function isAuditingEnabled(): bool
    {
    	return static::$auditingEnabled;
    }

    /**
     * Boot the Auditable trait.
     */
    public static function bootAuditable(): void
    {
    	static::created(function (Model $model) {
    		if (static::isAuditingEnabled() && $model->isAuditable()) {
    			$model->recordActivity('created', null, $model->getAttributes());
    		}
    	});

    	static::updating(function (Model $model) {
    		$model->originalData = $model->getOriginal();
    	});

    	static::updated(function (Model $model) {
    		if (! static::isAuditingEnabled() || ! $model->isAuditable()) {
    			return;
    		}

    		$before = $model->originalData ?? $model->getOriginal();
    		$after  = $model->getChanges();

    		$beforeFiltered = $model->filterAuditableAttributes($before);
    		$afterFiltered  = $model->filterAuditableAttributes($after);

    		if (empty($afterFiltered)) {
    			return;
    		}

    		$model->recordActivity('updated', $beforeFiltered, $afterFiltered);
    	});

    	static::deleted(function (Model $model) {
    		if (static::isAuditingEnabled() && $model->isAuditable()) {
    			$model->recordActivity('deleted', $model->getOriginal(), null);
    		}
    	});
    }

    protected array $auditIgnore = ['updated_at', 'created_at', 'deleted_at'];

    protected bool $auditEnabled = true;

    public function recordActivity(string $event, ?array $before = null, ?array $after = null): void
    {
    	try {
    		ActivityLog::create([
    			'user_id'    => Auth::id(),
    			'event'      => $event,
    			'model'      => static::class,
    			'model_id'   => $this->getKey(),
    			'before'     => $before ?: null,
    			'after'      => $after ?: null,
    			'ip_address' => Request::ip(),
    			'user_agent' => Request::header('User-Agent'),
    			'url'        => Request::fullUrl(),
    		]);
    	} catch (\Throwable $e) {
    		Log::error("Audit log error in " . static::class . ": " . $e->getMessage());
    	}
    }

    protected function filterAuditableAttributes(array $attributes): array
    {
    	return Arr::except($attributes, $this->auditIgnore);
    }

    protected function isAuditable(): bool
    {
    	return $this->auditEnabled ?? true;
    }
  }
