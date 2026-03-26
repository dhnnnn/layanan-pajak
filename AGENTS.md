# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.
- php - 8.2.30
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation
This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.
- `pest-testing` — Tests applications using the Pest 3 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, architecture testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

---

# Laravel Boost Rules

Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. Example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. Example: use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
1. **Simple Word Searches** with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. **Multiple Words (AND Logic)** - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. **Quoted Phrases (Exact Position)** - query="infinite scroll" - words must be adjacent and in that order.
4. **Mixed Queries** - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. **Multiple Queries** - queries=["authentication", "middleware"] - ANY of these terms.

---

# PHP Rules

- Always use curly braces for control structures, even for single-line bodies.

## Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
- Example: `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments & PHPDoc
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.
- Add useful array shape type definitions when appropriate.

---

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files. You can list available Artisan commands using the `list-artisan-commands` tool.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input.

## Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Avoid `DB::`; prefer `Model::query()`. 
- Generate code that prevents N+1 query problems by using eager loading.

## APIs, Controllers & Validation
- For APIs, default to using Eloquent API Resources and API versioning.
- Always create Form Request classes for validation rather than inline validation in controllers. Include validation rules and custom error messages.

## URL Generation & Queues
- Prefer named routes and the `route()` function.
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration
- Use environment variables only in configuration files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing
- When creating models for tests, use the factories for the models.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. 
- Create tests using: `php artisan make:test [options] {name}`.

---

# Laravel 12 Specifics

- **CRITICAL:** ALWAYS use `search-docs` tool for version-specific Laravel documentation.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application-specific service providers.
- Console commands in `app/Console/Commands/` are automatically available.
- Laravel 12 allows limiting eagerly loaded records natively: `$query->latest()->limit(10);`.
- Casts should be set in a `casts()` method on a model rather than the `$casts` property.

---

# Tooling Rules

## Laravel Pint
- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`.

## Pest
- Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- **CRITICAL:** Activate `pest-testing` every time you're working with a Pest or testing-related task.

## Tailwind CSS
- Always use existing Tailwind conventions; check project patterns before adding new ones.
- **CRITICAL:** Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task. Always use `search-docs` tool for documentation.


# Agent Action Pattern (Workflow)

When executing any task or fulfilling a user request, you MUST systematically follow this step-by-step action pattern:

## Step 1: Analyze & Contextualize
- **Understand the Goal:** Clarify the user's request and identify the expected outcome.
- **Check Conventions:** Inspect sibling files and existing architecture to understand current coding standards, naming conventions, and file structures.
- **Activate Skills:** Immediately activate `pest-testing` or `tailwindcss-development` if the task involves testing or UI styling.

## Step 2: Research & Consult
- **Use `search-docs`:** CRITICAL. Before proposing a solution or writing code, use the `search-docs` tool to query version-specific documentation (Laravel 12, Pest 3, Tailwind v4). Do not guess or rely solely on training data.
- **Check Artisan:** Use `list-artisan-commands` if you need to generate files to ensure you use the correct flags and options.

## Step 3: Scaffold & Plan
- **Generate Files:** Use `php artisan make:` commands with `--no-interaction` to scaffold Controllers, Models (with factories/seeders), Form Requests, or Tests.
- **Architect:** Plan out the database relationships, ensure Form Requests are used for validation, and decide on API resources if applicable.

## Step 4: Execute & Implement
- **Write Code:** Implement the logic using strict type hints, PHP 8 constructor property promotion, and explicit return types.
- **Optimize Database:** Use Eloquent relationships, avoid raw `DB::` queries, and prevent N+1 issues by eagerly loading data.
- **Style:** Apply Tailwind CSS v4 utility classes following existing UI patterns.

## Step 5: Test & Debug
- **Write Tests:** Create Pest feature or unit tests for the new functionality.
- **Run Tests:** Execute `php artisan test --compact` to verify the logic.
- **Debug (If Needed):** Use the `tinker` tool, `database-query`, or `browser-logs` to investigate failing tests or unexpected behavior.

## Step 6: Format & Finalize
- **Code Style:** Run `vendor/bin/pint --dirty` to automatically fix any PHP formatting issues before finalizing the response.
- **Frontend Build Check:** If frontend changes were made, evaluate if the user needs to run `npm run build` or `npm run dev`, and remind them concisely.
- **Concise Reply:** Deliver the final response to the user, focusing only on what is important without over-explaining obvious details.