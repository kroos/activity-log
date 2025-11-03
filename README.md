# 🧾 Laravel Auditable Trait

A clean, efficient, and production-ready **Eloquent model auditing system** for Laravel 10/11/12.
Automatically logs `create`, `update`, and `delete` events for any model —
capturing **who** did it, **what** changed, **when**, and **where**.

---

## 🚀 Features

- 🔄 Automatic model event logging (`created`, `updated`, `deleted`)
- 👤 Tracks authenticated users via `Auth::id()`
- 🌐 Captures request context (IP, User-Agent, URL)
- ⚙️ Configurable per-model ignored attributes
- 🔒 Disable auditing per model or globally
- 🧵 Optional **queued** logging for performance
- 💪 Fully decoupled and safe (logs errors without breaking your app)

---

## 🧱 Folder Structure

```
app/
 ├── Jobs/
 │   └── RecordActivityLog.php
 ├── Models/
 │   ├── Model.php
 │   ├── ActivityLog.php
 │   └── Post.php
 └── Traits/
      └── Auditable.php
```

---

## ⚙️ Installation

1. **Create the trait**

   Place the following file in `app/Traits/Auditable.php`.

2. **Add a base model**

   Create a reusable base class at `app/Models/Model.php` that includes the trait.

3. **Add an `ActivityLog` model**

   Create `app/Models/ActivityLog.php` for your log storage.

4. **Create a migration**

   Generate and edit the migration file:
   ```bash
   php artisan make:migration create_activity_logs_table
   ```

   Example:
   ```php
   Schema::create('activity_logs', function (Blueprint $table) {
       $table->id();
       $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
       $table->string('event');
       $table->string('model');
       $table->unsignedBigInteger('model_id')->nullable();
       $table->json('before')->nullable();
       $table->json('after')->nullable();
       $table->string('ip_address')->nullable();
       $table->text('user_agent')->nullable();
       $table->text('url')->nullable();
       $table->timestamps();
   });
   ```

   Then run:
   ```bash
   php artisan migrate
   ```

---

## 📄 Example Files

### `app/Models/Model.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use App\Traits\Auditable;

class Model extends Eloquent
{
    use Auditable;

    protected $guarded = [];
}
```

### `app/Models/ActivityLog.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id', 'event', 'model', 'model_id',
        'before', 'after', 'ip_address', 'user_agent', 'url',
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
    ];

    // Disable auditing for this model (to prevent recursive logging)
    protected bool $auditEnabled = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### `app/Traits/Auditable.php`
```php
<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Jobs\RecordActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

trait Auditable
{
    protected static bool $auditingEnabled = true;
    protected bool $auditEnabled = true;
    protected array $auditIgnore = ['updated_at', 'created_at', 'deleted_at'];

    public static function disableAuditing(): void
    {
        static::$auditingEnabled = false;
    }

    public static function enableAuditing(): void
    {
        static::$auditingEnabled = true;
    }

    public static function isAuditingEnabled(): bool
    {
        return static::$auditingEnabled;
    }

    public static function withoutAuditing(callable $callback): mixed
    {
        static::disableAuditing();
        try {
            return $callback();
        } finally {
            static::enableAuditing();
        }
    }

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
            $after = $model->getChanges();

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

    public function recordActivity(string $event, ?array $before = null, ?array $after = null): void
    {
        if (! static::isAuditingEnabled() || ! $this->isAuditable()) {
            return;
        }

        try {
            $data = [
                'user_id'    => Auth::id(),
                'event'      => $event,
                'model'      => static::class,
                'model_id'   => $this->getKey(),
                'before'     => $before ?: null,
                'after'      => $after ?: null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::header('User-Agent'),
                'url'        => Request::fullUrl(),
            ];

            RecordActivityLog::dispatch($data)->onQueue('audit');
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
```

### `app/Jobs/RecordActivityLog.php`
```php
<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordActivityLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        ActivityLog::create($this->data);
    }
}
```

---

## 🧠 Usage

### Basic Usage
```php
use App\Models\Post;

$post = Post::create(['title' => 'Hello', 'body' => 'World']);
$post->update(['body' => 'Universe']);
$post->delete();
```

Automatically logs all actions in the `activity_logs` table.

---

### Disable Auditing Temporarily
```php
use App\Models\Model;

Model::withoutAuditing(function () {
    User::factory()->count(100)->create();
});
```

### Disable Auditing Permanently (Per Model)
```php
protected bool $auditEnabled = false;
```

---

## 🧵 Queued Logging Setup

### 1️⃣ Configure Queue in `.env`
```env
QUEUE_CONNECTION=database
```

### 2️⃣ Create Queue Tables
```bash
php artisan queue:table
php artisan migrate
```

### 3️⃣ Run the Worker
```bash
php artisan queue:work --queue=audit
```

Audit logs will now be written asynchronously.

---

## 📋 Example Log Record

| Field | Description |
|-------|--------------|
| `user_id` | Authenticated user ID |
| `event` | Action (`created`, `updated`, `deleted`) |
| `model` | Model class (e.g., `App\Models\Post`) |
| `model_id` | Primary key of the model |
| `before` | JSON snapshot before change |
| `after` | JSON snapshot after change |
| `ip_address` | Request IP |
| `user_agent` | Browser or client info |
| `url` | Full request URL |

---

## 🧩 License

Licensed under the **MIT License**.

---

## 💬 Credits

Developed with ❤️ by
**kroos**
