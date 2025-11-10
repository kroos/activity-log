# 🧾 Auditable Trait for Laravel

A simple, flexible, and automatic auditing trait for **Laravel Eloquent models**.  
This trait automatically logs all key model events such as **create**, **update**, **delete**, and **restore** into an `ActivityLog` model.

---

## 🚀 Features

- Logs `created`, `updated`, `deleted`, `restored`, and `force_deleted` events.
- Captures **before/after attribute differences**.
- Records **user info**, **IP address**, **URL**, **HTTP method**, and **user agent**.
- Allows **sensitive fields exclusion** (e.g., password).
- Supports **critical event marking** (for alerts or priority logs).
- Optionally includes a **full model snapshot**.
- Minimal setup — just one trait.

---

## 📦 Installation

Copy the `Auditable.php` trait into your project under:

```
app/Traits/Auditable.php
```

Ensure you have an `ActivityLog` model and corresponding database table.

---

## ⚙️ Usage

Add the trait to any Eloquent model:

```php
use App\Traits\Auditable;

class User extends Model
{
    use Auditable;

    protected static $auditExclude = ['password'];
}
```

Now every create, update, delete, and restore action will automatically create an entry in your `activity_logs` table.

---

## 🧠 Configuration Options

### Exclude Sensitive Attributes
```php
protected static $auditExclude = ['password', 'remember_token'];
```

### Include Full Snapshot
```php
protected static $auditIncludeSnapshot = true;
```

### Define Critical Events
```php
protected static $auditCriticalEvents = ['deleted', 'force_deleted'];
```

---

## 🧩 How It Works

The trait hooks into Eloquent’s model events using the `bootAuditable()` method.

| Event | Action |
|-------|---------|
| `created` | Logs new record creation |
| `updated` | Records before & after changes |
| `deleted` | Logs deletion (soft or force) |
| `restored` | Logs restored model |

Each log entry is stored via the `ActivityLog` model with the following fields:

| Field | Description |
|--------|-------------|
| `user_id` | The authenticated user who made the change |
| `event` | created / updated / deleted / restored |
| `model_type` | The Eloquent model class |
| `model_id` | The primary key of the affected record |
| `route_name` | Current route name |
| `method` | HTTP method (GET/POST/etc.) |
| `url` | Full request URL |
| `ip_address` | Request IP |
| `user_agent` | Browser agent |
| `guard` | Laravel auth guard |
| `is_critical` | True for critical events |
| `description` | Human-readable summary |
| `changes` | JSON diff of attributes |
| `snapshot` | Full model data if enabled |

---

## 🧮 Example of Logged Data

```json
{
  "user_id": 1,
  "event": "updated",
  "model_type": "App\\Models\\User",
  "model_id": 15,
  "description": "User updated (15)",
  "changes": {
    "name": { "before": "John", "after": "Johnny" },
    "status": { "before": "inactive", "after": "active" }
  }
}
```

---

## 🛡️ Notes

- This trait uses `App\Models\ActivityLog` — ensure you have the corresponding model and table.
- Use `auth()->getDefaultDriver()` to detect which guard the user used (e.g., web/api).
- Works seamlessly with **Laravel’s soft deletes**.

---

## 📜 License

Released under the **MIT License**.  
Feel free to modify and use it in your Laravel applications.

---

**Author:** Noor Dhiauddin Karim  
**Framework:** Laravel  
**Trait:** Auditable  
**Category:** Eloquent Auditing / Logging
