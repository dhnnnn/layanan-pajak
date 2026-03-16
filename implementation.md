# 📋 Implementation Plan — Sistem Layanan Pajak

## 📖 Project Overview

Sistem ini adalah **aplikasi manajemen realisasi pajak daerah** berbasis Laravel yang memungkinkan:

* Admin mengelola **jenis pajak, target APBD, kecamatan, dan pegawai**
* Pegawai menginput **realisasi pajak per kecamatan**
* Import data menggunakan **Excel**
* Dashboard untuk melihat **pencapaian target pajak**

### Roles

| Role        | Akses                                                       |
| ----------- | ----------------------------------------------------------- |
| **Admin**   | Mengelola master data, target pajak, pegawai, dan dashboard |
| **Pegawai** | Menginput realisasi pajak sesuai kecamatan yang ditugaskan  |

---

# 🧱 Current State

Project menggunakan:

* **Laravel 12 (Fresh Install)**
* Default hanya memiliki:

  * `User` model
  * 3 default migrations
* Belum ada:

  * Role Permission
  * Excel Import
  * Controllers
  * Routes
  * Views
  * Action Classes

---

# 🚀 Phase 1 — Dependencies & Foundation

Install package yang dibutuhkan:

| Package                     | Kegunaan                  |
| --------------------------- | ------------------------- |
| `spatie/laravel-permission` | Role-based access control |
| `maatwebsite/excel`         | Import & export Excel     |

### Install Packages

```bash
composer require spatie/laravel-permission
composer require maatwebsite/excel
```

### Publish Config

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"
```

### Run Migration

```bash
php artisan migrate
```

Output:

* `composer.json` updated
* Migration & config dari kedua package terpublish

---

# 🗄 Phase 2 — Database Schema & Migrations

Total migration baru: **7 tabel**

## Tables

### users

(sudah ada, hanya extend relasi)

```
id
name
email
password
timestamps
```

---

### districts

```
id
name
code
timestamps
```

---

### employee_districts (pivot)

```
user_id
district_id
```

Relasi:

```
User <-> District
many to many
```

---

### tax_types

```
id
name
code
timestamps
```

---

### tax_targets

```
id
tax_type_id
year
target_amount
timestamps
```

---

### tax_realizations

```
id
tax_type_id
district_id
user_id
year

jan
feb
mar
apr
may
jun
jul
aug
sep
oct
nov
dec

timestamps
```

---

### import_logs

```
id
user_id
file_name
status
total_rows
success_rows
failed_rows
notes
timestamps
```

---

# 🧩 Phase 3 — Models & Relationships

## User

```
belongsToMany(District)
hasMany(TaxRealization)
hasMany(ImportLog)
```

---

## District

```
belongsToMany(User)
hasMany(TaxRealization)
```

---

## TaxType

```
hasMany(TaxTarget)
hasMany(TaxRealization)
```

---

## TaxTarget

```
belongsTo(TaxType)
```

---

## TaxRealization

```
belongsTo(TaxType)
belongsTo(District)
belongsTo(User)
```

---

## ImportLog

```
belongsTo(User)
```

---

### Factory & Seeder

Setiap model memiliki:

```
database/factories/*
database/seeders/*
```

---

# ⚙️ Phase 4 — Action Classes

Struktur folder:

```
app/Actions
```

```
app/Actions
├── Tax
│
│   ImportTaxRealizationAction.php
│   StoreTaxRealizationAction.php
│   CalculateTaxRealizationAction.php
│   GenerateTaxDashboardAction.php
│   CalculateAchievementPercentageAction.php
│
└── Employee
    AssignEmployeeDistrictAction.php
```

### Fungsi Action

| Action                               | Deskripsi                   |
| ------------------------------------ | --------------------------- |
| ImportTaxRealizationAction           | Parsing Excel + validasi    |
| StoreTaxRealizationAction            | Simpan realisasi manual     |
| CalculateTaxRealizationAction        | Hitung total bulanan        |
| GenerateTaxDashboardAction           | Query data dashboard        |
| CalculateAchievementPercentageAction | Hitung % pencapaian         |
| AssignEmployeeDistrictAction         | Assign pegawai ke kecamatan |

---

# 📊 Phase 5 — Excel Import Class

File:

```
app/Imports/TaxRealizationImport.php
```

Interface yang digunakan:

```
ToCollection
WithHeadingRow
WithValidation
```

### Kolom Excel

| Column      | Description         |
| ----------- | ------------------- |
| Jenis Pajak | Nama pajak          |
| Kecamatan   | Nama kecamatan      |
| Tahun       | Tahun laporan       |
| Jan–Des     | Realisasi per bulan |

### Features

* Database transaction
* Preview sebelum save
* Validasi data
* Logging hasil import

Log disimpan di:

```
import_logs
```

---

# 🧭 Phase 6 — Controllers & Routes

## Admin Controllers

```
app/Http/Controllers/Admin
```

| Controller          | Fungsi            |
| ------------------- | ----------------- |
| DistrictController  | CRUD kecamatan    |
| TaxTypeController   | CRUD jenis pajak  |
| TaxTargetController | CRUD target pajak |
| EmployeeController  | CRUD pegawai      |
| ImportController    | Upload Excel      |
| DashboardController | Dashboard admin   |

---

## Employee Controllers

```
app/Http/Controllers/Employee
```

| Controller            | Fungsi                |
| --------------------- | --------------------- |
| RealizationController | Input realisasi pajak |
| ImportController      | Import Excel          |

Pegawai hanya bisa akses **district yang ditugaskan**.

---

# 🛣 Phase 7 — Routes

File:

```
routes/web.php
```

### Middleware

```
auth
role:admin
role:pegawai
```

Contoh:

```php
Route::middleware(['auth','role:admin'])->prefix('admin')->group(function(){
    Route::resource('districts', DistrictController::class);
});
```

---

# 📈 Phase 8 — Dashboard & Aggregation Logic

Data dashboard dihasilkan oleh:

```
GenerateTaxDashboardAction
```

### Dashboard Data

Untuk setiap:

```
TaxType × Year
```

Hitung:

| Data            | Rumus                      |
| --------------- | -------------------------- |
| Q1              | Jan + Feb + Mar            |
| Q2              | Apr + Mei + Jun            |
| Q3              | Jul + Agu + Sep            |
| Q4              | Okt + Nov + Des            |
| Total Realisasi | Sum Jan–Des                |
| Sisa Target     | Target - Realisasi         |
| Persentase      | (Realisasi / Target) × 100 |

---

# 🖥 Phase 9 — Views & Frontend

Halaman yang dibuat:

### Dashboard

Tabel:

```
Jenis Pajak
Target
Q1
Q2
Q3
Q4
Total
Sisa Target
Persentase
```

---

### Input Realisasi

Form:

```
Jenis Pajak
Kecamatan
Tahun
Jan–Des
```

---

### Excel Import

Flow:

```
Upload File
↓
Preview Data
↓
Confirm Import
```

---

### Management Pages

Admin:

* Manage districts
* Manage tax types
* Manage targets
* Manage employees

---

# 🌱 Phase 10 — Seeder & Roles Setup

Seeder yang dibuat:

```
RoleSeeder
UserSeeder
DistrictSeeder
TaxTypeSeeder
```

### Roles

```
admin
pegawai
```

### Default Admin

```
email: admin@mail.com
password: password
```

---

# 🧪 Phase 11 — Testing

Testing menggunakan:

```
Pest PHP
```

Feature tests:

```
Login
Role permission
Import Excel
Dashboard calculation
Tax realization input
```

---

# 📌 Execution Order

Urutan implementasi:

```
1. Install dependencies
2. Publish package configs
3. Run migrations
4. Create Models + Factories + Seeders
5. Create Action Classes
6. Create Excel Import class
7. Create Form Requests
8. Create Controllers
9. Define Routes
10. Build Views
11. Write Feature Tests
```

---

# 🏗 Suggested Folder Structure

```
app
├── Actions
├── Imports
├── Models
├── Http
│   ├── Controllers
│   │   ├── Admin
│   │   └── Employee
│   └── Requests
```

---

# 🎯 Final Goal

Sistem mampu:

* Mengelola target pajak daerah
* Menginput realisasi pajak
* Import data Excel
* Menampilkan dashboard pencapaian target pajak
* Mengontrol akses berdasarkan role

---
