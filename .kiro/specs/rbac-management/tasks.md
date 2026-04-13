# Implementation Plan: RBAC Management

## Overview

Implementasi modul RBAC Management di atas Spatie Permission yang sudah ada. Mengikuti pola Controller → Action → View (Blade) dengan UUID, Form Request, dan Pest v3 untuk testing.

## Tasks

- [x] 1. Migration dan Model Updates
  - [x] 1.1 Buat migration untuk menambahkan kolom `group` pada tabel `permissions`
    - Jalankan `docker-compose exec app php artisan make:migration add_group_to_permissions_table --table=permissions`
    - Tambahkan `$table->string('group')->default('general')->after('guard_name')->index();`
    - _Requirements: 2.1, 2.2_

  - [x] 1.2 Update model `Permission` dengan fillable, scope `byGroup`, dan cast
    - Tambahkan `'group'` ke `$fillable`
    - Tambahkan method `scopeByGroup(Builder $query, string $group): Builder`
    - _Requirements: 2.1_

  - [x] 1.3 Update model `Role` dengan konstanta `SYSTEM_ROLES` dan method `isSystemRole()`
    - Tambahkan `public const SYSTEM_ROLES = ['admin', 'kepala_upt', 'pegawai', 'pemimpin'];`
    - Tambahkan method `isSystemRole(): bool`
    - _Requirements: 1.5, 1.6, 1.7_

  - [x] 1.4 Jalankan migration dan verifikasi kolom `group` tersedia
    - `docker-compose exec app php artisan migrate`
    - Jalankan `vendor/bin/pint --dirty`
    - _Requirements: 2.1_

- [x] 2. PermissionSeeder
  - [x] 2.1 Buat `PermissionSeeder` yang mendefinisikan semua permission dengan group
    - Jalankan `docker-compose exec app php artisan make:seeder PermissionSeeder`
    - Seed semua permission dari tabel di design.md: group `master-data`, `monitoring`, `field-officer`, `rbac`
    - Gunakan `Permission::firstOrCreate(['name' => ..., 'guard_name' => 'web'], ['group' => ...])`
    - Setelah seed permission, assign default permission ke masing-masing System_Role sesuai tabel di design.md
    - Daftarkan `PermissionSeeder` di `DatabaseSeeder`
    - _Requirements: 2.4_

  - [ ]* 2.2 Tulis property test untuk PermissionSeeder completeness
    - Buat `tests/Feature/Admin/PermissionManagementTest.php` dengan `php artisan make:test --pest Admin/PermissionManagementTest`
    - **Property 8: Permission seeder completeness**
    - **Validates: Requirements 2.4**
    - Verifikasi semua permission dari seeder ada di database setelah `PermissionSeeder::run()`
    - _Requirements: 2.4_

- [x] 3. Action Classes — Role Management
  - [x] 3.1 Buat `CreateRoleAction`
    - Jalankan `docker-compose exec app php artisan make:class Actions/Admin/CreateRoleAction`
    - Invokable class: terima `array $data`, buat Role dengan `guard_name = 'web'`, log aktivitas
    - _Requirements: 1.2_

  - [x] 3.2 Buat `UpdateRoleAction`
    - Jalankan `docker-compose exec app php artisan make:class Actions/Admin/UpdateRoleAction`
    - Invokable class: terima `Role $role, array $data`, update nama, log aktivitas
    - _Requirements: 1.4_

  - [x] 3.3 Buat `DeleteRoleAction`
    - Jalankan `docker-compose exec app php artisan make:class Actions/Admin/DeleteRoleAction`
    - Invokable class: terima `Role $role`
    - Tolak jika `$role->isSystemRole()` → throw `\RuntimeException`
    - Tolak jika role masih memiliki user aktif → throw `\RuntimeException` dengan jumlah user
    - Hapus role beserta permission assignment-nya, log aktivitas
    - _Requirements: 1.5, 1.6, 1.7_

  - [ ]* 3.4 Tulis property tests untuk Role Management actions
    - Tambahkan ke `tests/Feature/Admin/RoleManagementTest.php` (buat dengan `php artisan make:test --pest Admin/RoleManagementTest`)
    - **Property 2: Role creation round-trip** — Validates: Requirements 1.2
    - **Property 3: Role rename preserves non-system roles** — Validates: Requirements 1.4
    - **Property 4: Role deletion invariants** — Validates: Requirements 1.5, 1.6, 1.7
    - _Requirements: 1.2, 1.4, 1.5, 1.6, 1.7_

- [x] 4. Action Classes — Permission Management
  - [x] 4.1 Buat `CreatePermissionAction`
    - Jalankan `docker-compose exec app php artisan make:class Actions/Admin/CreatePermissionAction`
    - Invokable class: terima `array $data`, buat Permission dengan `guard_name = 'web'` dan `group`, log aktivitas
    - _Requirements: 2.2_

  - [x] 4.2 Buat `DeletePermissionAction`
    - Jalankan `docker-compose exec app php artisan make:class Actions/Admin/DeletePermissionAction`
    - Invokable class: terima `Permission $permission`
    - Cabut permission dari semua role sebelum menghapus (Spatie handles via cascade)
    - Flush Spatie cache, log aktivitas
    - _Requirements: 2.5_

  - [ ]* 4.3 Tulis property tests untuk Permission Management actions
    - Tambahkan ke `tests/Feature/Admin/PermissionManagementTest.php`
    - **Property 5: Permission grouping consistency** — Validates: Requirements 2.1
    - **Property 6: Permission creation round-trip** — Validates: Requirements 2.2
    - **Property 7: Permission deletion cascades to roles** — Validates: Requirements 2.5
    - _Requirements: 2.1, 2.2, 2.5_

- [x] 5. Action Classes — Role Permission Sync dan User Management
  - [x] 5.1 Buat `SyncRolePermissionsAction`
    - Jalankan `docker-compose exec app php artisan make:class Actions/Admin/SyncRolePermissionsAction`
    - Invokable class: terima `Role $role, array $permissionIds`
    - Bungkus dalam `DB::transaction()`: `$role->syncPermissions($permissionIds)`, flush Spatie cache, log aktivitas
    - _Requirements: 3.2, 3.4_

  - [x] 5.2 Buat `CreateRbacUserAction`
    - Jalankan `docker-compose exec app php artisan make:class Actions/Admin/CreateRbacUserAction`
    - Invokable class: terima `array $data`
    - Buat User, assign role via `$user->syncRoles($data['roles'])`, jika role `kepala_upt` sync UPT, flush cache, log aktivitas
    - _Requirements: 4.2, 4.4_

  - [x] 5.3 Buat `UpdateRbacUserAction`
    - Jalankan `docker-compose exec app php artisan make:class Actions/Admin/UpdateRbacUserAction`
    - Invokable class: terima `User $user, array $data`
    - Update data user, sync roles, jika role `kepala_upt` sync UPT, flush cache, log aktivitas
    - _Requirements: 4.6_

  - [x] 5.4 Buat `DeleteRbacUserAction`
    - Jalankan `docker-compose exec app php artisan make:class Actions/Admin/DeleteRbacUserAction`
    - Invokable class: terima `User $user`
    - Tolak jika user adalah satu-satunya admin aktif → throw `\RuntimeException`
    - Hapus user beserta role assignment, log aktivitas
    - _Requirements: 4.7, 4.8_

  - [ ]* 5.5 Tulis property tests untuk Sync dan User Management actions
    - Buat `tests/Feature/Admin/RolePermissionSyncTest.php` dengan `php artisan make:test --pest Admin/RolePermissionSyncTest`
    - **Property 9: Role permission sync is exact** — Validates: Requirements 3.2
    - **Property 10: Role detail shows correct users** — Validates: Requirements 3.1, 3.5
    - Buat `tests/Feature/Admin/RbacUserManagementTest.php` dengan `php artisan make:test --pest Admin/RbacUserManagementTest`
    - **Property 12: User creation round-trip** — Validates: Requirements 4.2
    - **Property 13: kepala_upt requires UPT** — Validates: Requirements 4.5
    - **Property 14: Role sync replaces previous roles** — Validates: Requirements 4.6
    - **Property 15: Minimum one admin invariant** — Validates: Requirements 4.7, 4.8
    - _Requirements: 3.2, 4.2, 4.5, 4.6, 4.7, 4.8_

- [x] 6. Checkpoint — Pastikan semua tests pass
  - Jalankan `docker-compose exec app php artisan test --compact`
  - Pastikan semua tests pass, tanyakan ke user jika ada pertanyaan.

- [x] 7. Form Requests
  - [x] 7.1 Buat `StoreRoleRequest` dan `UpdateRoleRequest`
    - Jalankan `php artisan make:request Admin/StoreRoleRequest` dan `Admin/UpdateRoleRequest`
    - `StoreRoleRequest`: validasi `name` required, string, max:255, `unique:roles,name`
    - `UpdateRoleRequest`: validasi `name` required, string, max:255, `unique:roles,name,{role_id}` (ignore current)
    - Sertakan pesan error Bahasa Indonesia
    - _Requirements: 1.2, 1.3, 1.4, 7.3_

  - [x] 7.2 Buat `StorePermissionRequest` dan `UpdatePermissionRequest`
    - Jalankan `php artisan make:request Admin/StorePermissionRequest` dan `Admin/UpdatePermissionRequest`
    - Validasi `name` (required, unique:permissions,name), `group` (required, string, in: master-data,monitoring,field-officer,rbac)
    - Sertakan pesan error Bahasa Indonesia
    - _Requirements: 2.2, 2.3, 7.3_

  - [x] 7.3 Buat `SyncRolePermissionsRequest`
    - Jalankan `php artisan make:request Admin/SyncRolePermissionsRequest`
    - Validasi `permissions` (nullable, array), `permissions.*` (string, exists:permissions,id)
    - _Requirements: 3.2, 7.3_

  - [x] 7.4 Buat `StoreRbacUserRequest` dan `UpdateRbacUserRequest`
    - Jalankan `php artisan make:request Admin/StoreRbacUserRequest` dan `Admin/UpdateRbacUserRequest`
    - `StoreRbacUserRequest`: validasi `name`, `email` (unique:users,email), `password` (confirmed), `roles` (required, array, exists:roles,id), `upt_id` (required_if:roles.*,kepala_upt_id)
    - `UpdateRbacUserRequest`: sama tapi `email` ignore current user, `password` nullable
    - Sertakan pesan error Bahasa Indonesia
    - _Requirements: 4.2, 4.3, 4.5, 7.3_

  - [ ]* 7.5 Tulis property test untuk Form Request validation
    - Tambahkan ke test files yang relevan
    - **Property 19: Invalid input is rejected by Form Request** — Validates: Requirements 7.3
    - Test edge case: nama duplikat, email duplikat, kepala_upt tanpa UPT
    - _Requirements: 7.3_

- [x] 8. Controllers
  - [x] 8.1 Buat `RoleController`
    - Jalankan `docker-compose exec app php artisan make:controller Admin/RoleController`
    - Implementasikan: `index()`, `create()`, `store(StoreRoleRequest, CreateRoleAction)`, `show(Role)`, `edit(Role)`, `update(UpdateRoleRequest, Role, UpdateRoleAction)`, `destroy(Role, DeleteRoleAction)`, `syncPermissions(SyncRolePermissionsRequest, Role, SyncRolePermissionsAction)`
    - `index()`: load roles dengan `withCount(['users', 'permissions'])`
    - `show()`: load role dengan permissions (grouped) dan users
    - Error dari Action ditangkap dan di-redirect back dengan `session('error')`
    - _Requirements: 1.1, 1.2, 1.4, 1.5, 1.6, 1.7, 3.1, 3.2, 3.5_

  - [x] 8.2 Buat `PermissionController`
    - Jalankan `docker-compose exec app php artisan make:controller Admin/PermissionController`
    - Implementasikan: `index()`, `create()`, `store(StorePermissionRequest, CreatePermissionAction)`, `destroy(Permission, DeletePermissionAction)`
    - `index()`: load permissions grouped by `group` menggunakan `groupBy('group')`
    - _Requirements: 2.1, 2.2, 2.5_

  - [x] 8.3 Buat `RbacUserController`
    - Jalankan `docker-compose exec app php artisan make:controller Admin/RbacUserController`
    - Implementasikan: `index(Request)`, `create()`, `store(StoreRbacUserRequest, CreateRbacUserAction)`, `edit(User)`, `update(UpdateRbacUserRequest, User, UpdateRbacUserAction)`, `destroy(User, DeleteRbacUserAction)`
    - `index()`: search by name/email, eager load roles, paginate(15)
    - `create()` dan `edit()`: pass `$roles` (semua role) dan `$upts` ke view
    - _Requirements: 4.1, 4.2, 4.4, 4.6, 4.7, 4.8_

  - [x] 8.4 Buat `AccessMonitoringController`
    - Jalankan `docker-compose exec app php artisan make:controller Admin/AccessMonitoringController`
    - `index()`: load semua roles dengan permissions grouped by group, hitung total permission per role
    - `show(Role)`: load detail permissions role grouped by group, load users yang memiliki role
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ]* 8.5 Tulis feature tests untuk Controllers
    - Tambahkan ke test files yang relevan
    - **Property 1: Role count accuracy** — Validates: Requirements 1.1
    - **Property 11: User search filters correctly** — Validates: Requirements 4.1
    - **Property 16: Access monitoring count accuracy** — Validates: Requirements 5.2, 5.3
    - **Property 18: RBAC routes require admin role** — Validates: Requirements 7.1, 7.2
    - Buat `tests/Feature/Admin/AccessMonitoringTest.php` dengan `php artisan make:test --pest Admin/AccessMonitoringTest`
    - _Requirements: 1.1, 4.1, 5.2, 5.3, 7.1, 7.2_

- [x] 9. Routes
  - [x] 9.1 Tambahkan RBAC routes ke `routes/web.php`
    - Tambahkan route group baru dengan middleware `['auth', 'role:admin']`, prefix `admin`, name `admin.`
    - Daftarkan: `Route::resource('roles', ...)` + `Route::put('roles/{role}/permissions', ...)->name('roles.permissions.sync')`
    - Daftarkan: `Route::resource('permissions', ...)->except(['show', 'edit', 'update'])`
    - Daftarkan: `Route::resource('rbac-users', ...)->except(['show'])`
    - Daftarkan: dua route `access-monitoring` (index dan show)
    - Import semua controller baru di bagian `use` statements
    - _Requirements: 7.1_

- [x] 10. Support Class — SidebarPermissionMap
  - [x] 10.1 Buat `app/Support/SidebarPermissionMap.php`
    - Jalankan `docker-compose exec app php artisan make:class Support/SidebarPermissionMap`
    - Implementasikan static method `groups(): array` sesuai struktur di design.md
    - Sertakan semua group: `master-data`, `monitoring`, `field-officer`, `rbac`
    - Item dengan `permission => null` (Dashboard, Profil) selalu tampil
    - Tambahkan item `kepala_upt` khusus untuk link "Data UPT" dengan logic kondisional
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 11. Views — Role Management
  - [x] 11.1 Buat `resources/views/admin/roles/index.blade.php`
    - Tabel daftar role dengan kolom: Nama, Jumlah User, Jumlah Permission, Aksi (lihat, edit, hapus)
    - Tombol "Tambah Role" di header
    - Konfirmasi JavaScript sebelum hapus
    - Tampilkan `session('error')` dan `session('success')` flash messages
    - _Requirements: 1.1, 7.4_

  - [x] 11.2 Buat `resources/views/admin/roles/create.blade.php`
    - Form input nama role, tombol simpan dan batal
    - Tampilkan `$errors` bag
    - _Requirements: 1.2_

  - [x] 11.3 Buat `resources/views/admin/roles/edit.blade.php`
    - Form edit nama role (disabled jika System_Role)
    - Tampilkan peringatan jika System_Role `admin`
    - _Requirements: 1.4, 3.3_

  - [x] 11.4 Buat `resources/views/admin/roles/show.blade.php`
    - Checklist permission grouped by Permission_Group dengan checkbox
    - Form sync permission (PUT ke `roles.permissions.sync`)
    - Daftar user yang memiliki role ini
    - Peringatan jika role adalah System_Role `admin`
    - _Requirements: 3.1, 3.3, 3.5_

- [x] 12. Views — Permission Management
  - [x] 12.1 Buat `resources/views/admin/permissions/index.blade.php`
    - Tampilkan permission grouped by group dalam card/section terpisah
    - Tombol hapus per permission dengan konfirmasi JavaScript
    - Pesan informatif jika belum ada permission (arahkan ke seeder)
    - _Requirements: 2.1, 5.5_

  - [x] 12.2 Buat `resources/views/admin/permissions/create.blade.php`
    - Form input nama permission dan dropdown group
    - Tampilkan `$errors` bag
    - _Requirements: 2.2_

- [x] 13. Views — RBAC User Management
  - [x] 13.1 Buat `resources/views/admin/rbac-users/index.blade.php`
    - Tabel daftar user dengan kolom: Nama, Email, Role(s), Aksi (edit, hapus)
    - Search form by nama/email
    - Pagination
    - _Requirements: 4.1_

  - [x] 13.2 Buat `resources/views/admin/rbac-users/create.blade.php`
    - Form: nama, email, password, password_confirmation, checkbox roles, conditional UPT dropdown (tampil jika kepala_upt dipilih via Alpine.js atau JavaScript)
    - _Requirements: 4.2, 4.4_

  - [x] 13.3 Buat `resources/views/admin/rbac-users/edit.blade.php`
    - Form edit user: nama, email, password (opsional), checkbox roles, conditional UPT dropdown
    - _Requirements: 4.6_

- [x] 14. Views — Access Monitoring
  - [x] 14.1 Buat `resources/views/admin/access-monitoring/index.blade.php`
    - Matriks akses: baris = role, kolom = Permission_Group
    - Indikator visual (centang/silang) untuk permission yang dimiliki
    - Jumlah total permission per role
    - Link ke halaman detail per role
    - _Requirements: 5.1, 5.2_

  - [x] 14.2 Buat `resources/views/admin/access-monitoring/show.blade.php`
    - Detail permission satu role grouped by Permission_Group
    - Daftar user yang memiliki role ini
    - _Requirements: 5.3, 5.4_

- [x] 15. Checkpoint — Pastikan semua tests pass
  - Jalankan `docker-compose exec app php artisan test --compact`
  - Pastikan semua tests pass, tanyakan ke user jika ada pertanyaan.

- [x] 16. Sidebar Berbasis Permission
  - [x] 16.1 Update `resources/views/layouts/admin.blade.php` ke permission-based sidebar
    - Ganti kondisi `isAdmin()` / `isKepalaUpt()` hardcoded dengan loop `SidebarPermissionMap::groups()`
    - Gunakan `@can($item['permission'])` untuk setiap item menu
    - Item dengan `permission => null` selalu tampil tanpa `@can`
    - Sembunyikan seluruh section group jika tidak ada item yang visible
    - Untuk item `kepala_upt` (Data UPT), tampilkan link ke UPT milik user
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

  - [ ]* 16.2 Tulis property test untuk Sidebar berbasis permission
    - Buat `tests/Feature/Admin/SidebarPermissionTest.php` dengan `php artisan make:test --pest Admin/SidebarPermissionTest`
    - **Property 17: Sidebar shows only permitted items** — Validates: Requirements 6.1, 6.2, 6.3
    - Test: user tanpa permission tidak melihat menu yang memerlukan permission
    - Test: Dashboard dan Profil selalu tampil untuk semua user login
    - _Requirements: 6.1, 6.2, 6.3_

  - [ ]* 16.3 Tulis property test untuk audit log
    - Tambahkan ke `RolePermissionSyncTest.php` dan `RbacUserManagementTest.php`
    - **Property 20: Audit log records assignment changes** — Validates: Requirements 7.5
    - _Requirements: 7.5_

- [x] 17. Final Checkpoint — Pastikan semua tests pass
  - Jalankan `docker-compose exec app php artisan test --compact`
  - Jalankan `vendor/bin/pint --dirty` untuk format akhir
  - Pastikan semua tests pass, tanyakan ke user jika ada pertanyaan.

## Notes

- Tasks bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirements spesifik untuk traceability
- Jalankan `vendor/bin/pint --dirty` setelah setiap task selesai
- Semua artisan command dijalankan via Docker: `docker-compose exec app php artisan ...`
- Property tests menggunakan Pest Datasets dengan minimal 100 variasi input (Faker)
- Tag format property test: `// Feature: rbac-management, Property {N}: {property_text}`
