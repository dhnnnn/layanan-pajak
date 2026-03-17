## Refactored Professional Prompt

**Role:**
Act as a **Lead Backend Architect, Laravel Specialist, and Code Refactoring Expert** responsible for executing a large-scale architectural refactor across an entire Laravel codebase.

Your mission is to analyze the **entire Laravel project directory** and perform a **system-wide architectural improvement** while maintaining application stability, clean architecture, and strict adherence to **SOLID principles, Laravel best practices, and maintainable code structure**.

Work carefully and **execute the following tasks sequentially**.

---

# Task 1 — Implement Action Pattern (Thin Controllers)

Perform a full audit of all **Controller classes** within the application.

### Objective

Transform all Controllers into **Thin Controllers** by extracting business logic into dedicated **Action classes**.

### Rules

1. Controllers must only be responsible for:

   * Authorization
   * Request validation (via **FormRequest** classes)
   * Calling an **Action class**
   * Returning **HTTP Response / JSON / View**

2. All business logic must be moved to **Action classes**.

3. If the project already contains an `app/Actions` directory:

   * Reuse existing Actions whenever possible
   * Refactor them if necessary

4. If an Action does not exist:

   * Create a new Action inside `app/Actions/<Module>/`

### Business Logic That MUST Be Extracted

Move the following out of Controllers:

* Complex database queries
* Business rules
* Data transformation
* CUD operations (Create, Update, Delete)
* External API calls
* File processing
* Transaction handling

### Example Structure

**Before (Bad Controller)**

```php
class UserController extends Controller
{
    public function store(Request $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email
        ]);

        Mail::to($user->email)->send(new WelcomeMail($user));

        return redirect()->route('users.index');
    }
}
```

**After (Thin Controller)**

Controller:

```php
class UserController extends Controller
{
    public function store(StoreUserRequest $request, CreateUserAction $action)
    {
        $action->execute($request->validated());

        return redirect()->route('users.index');
    }
}
```

Action:

```php
class CreateUserAction
{
    public function execute(array $data): User
    {
        $user = User::create($data);

        Mail::to($user->email)->send(new WelcomeMail($user));

        return $user;
    }
}
```

---

# Task 2 — Global UUID Migration

Refactor the entire database architecture to replace **auto-increment integer IDs** with **UUIDs**.

This must be applied **consistently across all modules**.

---

## Model Changes

For all Eloquent Models:

1. Use the `HasUuids` trait

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;
```

2. Configure the model:

```php
class User extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
}
```

3. Ensure all model relationships continue to function correctly.

---

## Migration Changes

Update all migrations:

### Primary Keys

Replace:

```php
$table->id();
```

With:

```php
$table->uuid('id')->primary();
```

---

### Foreign Keys

Replace:

```php
$table->foreignId('user_id')->constrained();
```

With:

```php
$table->uuid('user_id');

$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->cascadeOnDelete();
```

---

### Pivot Tables

Ensure pivot tables also use UUID references.

Example:

```php
$table->uuid('user_id');
$table->uuid('role_id');
```

---

### Data Integrity

Ensure:

* Foreign keys match the **UUID type**
* Indexes remain optimized
* Cascade rules remain intact

---

# Task 3 — Global N+1 Query Resolution

Audit all database interactions across the application.

Focus particularly on:

* Action classes
* Models
* Service layers
* API Resources
* Controllers (if any logic remains)

---

## Objective

Identify and eliminate **N+1 Query Problems**.

---

## Required Fixes

Implement **Eager Loading** where necessary:

### Using `with()`

```php
Post::with('author')->get();
```

---

### Using `load()`

```php
$posts->load('comments');
```

---

### Using `loadMissing()`

```php
$post->loadMissing('author');
```

---

### Nested Relationships

```php
Post::with([
    'author',
    'comments.user'
])->get();
```

---

### Performance Considerations

Avoid:

```php
foreach ($posts as $post) {
    $post->comments;
}
```

Replace with eager loading.

---

# Code Quality Requirements

During refactoring, ensure:

### SOLID Principles

* Single Responsibility
* Dependency Injection
* Decoupled Architecture

### Laravel Best Practices

* FormRequests for validation
* Resource classes for API responses
* Proper Service Container usage
* Clean folder structure

### Clean Code

* Meaningful naming
* No duplicated logic
* Readable and maintainable code
* Small, focused classes

---

# Expected Output (Important)

After completing the refactor, produce a **structured report** containing the following sections:

---

## 1. Controllers Refactored

List every Controller that was simplified.

Example:

```
UserController
 → Business logic moved to:
   - CreateUserAction
   - UpdateUserAction
   - DeleteUserAction
```

---

## 2. Action Classes Created or Updated

Provide a list such as:

```
app/Actions/User/CreateUserAction.php
app/Actions/User/UpdateUserAction.php
app/Actions/Post/PublishPostAction.php
```

Explain briefly what each Action does.

---

## 3. UUID Migration Changes

Provide a list of:

### Updated Models

```
User
Post
Order
Invoice
```

### Updated Migrations

```
create_users_table
create_posts_table
create_orders_table
```

Include notes about foreign key conversions.

---

## 4. N+1 Query Issues Fixed

Provide specific examples.

Example:

```
Problem:
User::all() then accessing $user->posts in loop.

Fix:
User::with('posts')->get()
```

List all similar fixes across the project.

---

# Execution Strategy

Perform the refactor **incrementally**:

1️⃣ Analyze project structure
2️⃣ Refactor Controllers → Actions
3️⃣ Implement UUID changes
4️⃣ Resolve N+1 queries
5️⃣ Generate final report

Ensure the application **remains functional at every stage**.

---
