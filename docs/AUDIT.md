Act as a Lead Backend Architect and Clean Code Enforcer.

Please scan the entire Laravel project directory. We are executing a massive, system-wide architectural refactoring. Proceed carefully and ensure strict SOLID principles and Modern PHP/Laravel standards are applied.

Execute the following major architectural changes globally:

Task 1: Implement Action Pattern (Thin Controllers)

Extract all core business logic out of the controller methods. Move this logic into dedicated Action classes (e.g., within app/Actions/).

Controllers must only handle request authorization, validation (using FormRequests), calling the Action class, and returning the response.

Task 2: Global UUID Migration

Refactor the database architecture to replace sequential integer IDs with UUIDs across all modules. Update Models (HasUuids, $incrementing = false, $keyType = 'string') and Migrations ($table->uuid('id')->primary()). Ensure all foreign keys are also converted to uuid.

Task 3: Global N+1 Query Resolution

Inspect all database interactions globally. Fix any N+1 query performance vulnerabilities by implementing appropriate eager loading.

Task 4: Modernize Imperative Logic to Declarative (Laravel Collections)

CRITICAL: Do not write repetitive, junior-level imperative loops or use poor variable naming (e.g., $r1, $r2, $m).

Refactor all array/data manipulation logic to be highly readable for a Senior Developer.

Heavily utilize Laravel Collections (map(), filter(), reduce(), sum(), chunk()) instead of traditional for or foreach loops with accumulator variables.

Ensure variable names are descriptive and contextual (e.g., $cumulativeQuarterTotals instead of $r1, $r2).

Output Requirement: Provide a structured summary detailing:

Controllers simplified and the corresponding Action classes updated.

Models and Migrations modified for UUID.

N+1 bottlenecks fixed.

Examples of imperative loops successfully refactored into elegant Laravel Collections.

Please proceed step-by-step with this extensive refactoring.