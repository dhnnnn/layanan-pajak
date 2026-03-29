You are a senior software architect tasked with designing a scalable Laravel-based system for managing regional tax realization and target monitoring.

The system will be used by a government tax office to track tax realization across multiple districts and compare it against annual APBD targets.

The architecture must follow clean code principles and use the Action Pattern to keep controllers thin and business logic modular.

Technology Stack

Backend:
Laravel 12

Database:
MySQL

Excel Processing:
Laravel Excel (maatwebsite/excel)

Authorization:
Spatie Laravel Permission

Architecture Principles

* Use Action Pattern for business logic
* Controllers must remain thin
* Use Service Layer when needed
* Maintain clear separation between HTTP layer and domain logic
* Use Eloquent relationships properly
* All imports should be transaction-safe

User Roles

The system must support two roles:

Admin
Employee (Pegawai)

Admin Capabilities

* Manage tax types (jenis pajak)
* Manage districts (kecamatan)
* Manage employees
* Assign employees to multiple districts
* Define annual tax targets (APBD)
* Import Excel files containing tax realization data
* View aggregated reports
* Monitor performance dashboards

Employee Capabilities

* View assigned districts
* Input tax realization data
* Upload Excel files
* View their own submitted realizations
* Track progress toward assigned tax targets

District Assignment

An employee may be responsible for multiple districts.

Therefore the system must support a many-to-many relationship between users and districts.

Database tables should include:

users
roles
districts
employee_districts
tax_types
tax_targets
tax_realizations
import_logs

Excel Import Requirements

Employees or admins can upload Excel files containing tax realization data.

The Excel file includes:

Tax Type
District
Monthly realization (January to December)
Year

The system must:

1. Upload Excel file
2. Parse Excel rows
3. Validate tax type and district
4. Preview data before saving
5. Store monthly realizations in the database
6. Record import logs

Aggregation Logic

The system should automatically compute:

Monthly realization

Quarter realization:

Q1 = Jan + Feb + Mar
Q2 = Apr + May + Jun
Q3 = Jul + Aug + Sep
Q4 = Oct + Nov + Dec

Annual realization.

Target Comparison

Each tax type has a yearly target defined by APBD.

The system must calculate:

Achievement Percentage = (Total Realization / Target) × 100

Dashboard Requirements

The dashboard must show:

Tax Type
Annual Target
Quarter Realization (Q1–Q4)
Total Realization
Remaining Target
Achievement Percentage

Action Pattern Requirement

All core business logic must be implemented using Action classes.

Controllers must only handle:

Request validation
Calling actions
Returning responses

Example Actions that should exist:

ImportTaxRealizationAction
StoreTaxRealizationAction
AssignEmployeeDistrictAction
CalculateTaxRealizationAction
GenerateTaxDashboardAction
CalculateAchievementPercentageAction

Folder Structure

app/
Actions/
Tax/
ImportTaxRealizationAction.php
StoreTaxRealizationAction.php
CalculateTaxRealizationAction.php
GenerateTaxDashboardAction.php
CalculateAchievementPercentageAction.php

```
Employee/
  AssignEmployeeDistrictAction.php
```

Imports/
TaxRealizationImport.php

Models/
TaxType.php
TaxTarget.php
TaxRealization.php
District.php
User.php

Http/Controllers/
Admin/
Employee/

Security Requirements

* Implement role-based access control
* Prevent duplicate imports
* Validate Excel structure
* Use database transactions for imports

Deliverables

Generate:

Database schema
Laravel migrations
Eloquent models and relationships
Action classes implementation
Excel Import class
Controller examples using actions
Dashboard query logic
Recommended folder structure

Ensure the system is scalable, cleanly structured, and production-ready.
