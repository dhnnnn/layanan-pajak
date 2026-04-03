# Laravel Massive Refactoring & Security Hardening Guide

## 🧠 Overview

Dokumen ini menjelaskan langkah-langkah refactoring arsitektur Laravel secara menyeluruh dengan fokus pada:

* Clean Architecture (SOLID)
* Performance Optimization
* Security Hardening (XSS, SQL Injection, dll)

---

# ✅ TASK 1 — ACTION PATTERN (THIN CONTROLLERS)

## 🎯 Goal

Controller hanya bertugas:

* Authorization
* Validation
* Call Action
* Return Response

---

## ❌ Before (Fat Controller)

```php
public function store(Request $request)
{
    $user = User::create($request->all());

    if ($user->role == 'admin') {
        Mail::to($user)->send(new WelcomeMail());
    }

    return response()->json($user);
}
```

---

## ✅ After (Clean Controller)

```php
public function store(StoreUserRequest $request, CreateUserAction $action)
{
    $user = $action->execute($request->validated());

    return response()->json($user);
}
```

---

## 📦 Action Class

```php
class CreateUserAction
{
    public function execute(array $data): User
    {
        $user = User::create($data);

        if ($user->isAdmin()) {
            Mail::to($user)->send(new WelcomeMail());
        }

        return $user;
    }
}
```

---

## 📁 Structure

```
app/
 ├── Actions/
 │    └── User/
 │         └── CreateUserAction.php
```

---

# ✅ TASK 2 — GLOBAL UUID MIGRATION

## 🔧 Model

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
}
```

---

## 🔧 Migration

```php
$table->uuid('id')->primary();
```

---

## 🔗 Foreign Key

```php
$table->uuid('user_id');
$table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
```

---

## ⚠️ Notes

* Update route model binding
* Update factories & seeders
* Gunakan `Str::uuid()`

---

# ✅ TASK 3 — N+1 QUERY RESOLUTION

## ❌ Before

```php
$users = User::all();

foreach ($users as $user) {
    echo $user->posts;
}
```

---

## ✅ After

```php
$users = User::with('posts')->get();
```

---

## 🔥 Advanced

```php
User::with([
    'posts' => fn($query) => $query->latest()
])->get();
```

---

## 🔍 Debugging

```bash
composer require barryvdh/laravel-debugbar --dev
```

---

# ✅ TASK 4 — DECLARATIVE (COLLECTIONS)

## ❌ Imperative

```php
$total = 0;

foreach ($orders as $order) {
    if ($order->status == 'paid') {
        $total += $order->amount;
    }
}
```

---

## ✅ Declarative

```php
$totalPaidAmount = collect($orders)
    ->where('status', 'paid')
    ->sum('amount');
```

---

## ❌ Imperative

```php
$result = [];

foreach ($users as $user) {
    if ($user->active) {
        $result[] = $user->email;
    }
}
```

---

## ✅ Declarative

```php
$activeUserEmails = collect($users)
    ->filter->active
    ->pluck('email');
```

---

## 🧠 Naming Rules

* ❌ `$r1`, `$tmp`
* ✅ `$monthlyRevenueTotals`, `$activeUserEmails`

---

# 🔐 TASK 5 — SECURITY HARDENING

---

## 🛡️ 1. XSS PREVENTION

### Blade Escaping

```blade
{{ $data }}   // SAFE
{!! $data !!} // DANGEROUS
```

---

### Sanitization

```bash
composer require mews/purifier
```

```php
clean($input);
```

---

### CSP Header

```php
return response($content)
    ->header('Content-Security-Policy', "default-src 'self'");
```

---

## 🛡️ 2. SQL INJECTION PREVENTION

### ❌ Dangerous

```php
DB::select("SELECT * FROM users WHERE email = '$email'");
```

---

### ✅ Safe

```php
User::where('email', $email)->first();
```

---

### ✅ Raw Safe

```php
DB::select("SELECT * FROM users WHERE email = ?", [$email]);
```

---

## 🛡️ 3. Validation (FormRequest)

```php
class StoreUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email',
            'name'  => 'required|string|max:255'
        ];
    }
}
```

---

## 🛡️ 4. Mass Assignment

```php
protected $fillable = ['name', 'email'];
```

---

## 🛡️ 5. Rate Limiting

```php
Route::middleware('throttle:60,1')->group(function () {
    //
});
```

---

## 🛡️ 6. Authorization (Policy)

```bash
php artisan make:policy UserPolicy
```

---

## 🛡️ 7. Hide Sensitive Data

```php
protected $hidden = ['password', 'remember_token'];
```

---

## 🛡️ 8. Password Hashing

```php
Hash::make($password);
```

---

# 📊 FINAL SUMMARY

## ✔ Controllers

* Thin controllers
* No business logic

## ✔ Actions

* Reusable & testable
* Business logic centralized

## ✔ UUID Migration

* All PK & FK → UUID

## ✔ Performance

* N+1 eliminated
* Eager loading applied

## ✔ Code Quality

* Declarative collections
* Clean variable naming

## ✔ Security

* XSS protected
* SQL Injection prevented
* Validation enforced
* CSP enabled

---


## 🧠 Final Note

Refactoring ini bukan sekali jalan. Lakukan bertahap per module untuk menjaga stabilitas sistem production.

---
