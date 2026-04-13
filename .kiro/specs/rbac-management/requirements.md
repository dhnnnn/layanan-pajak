# Requirements Document

## Introduction

Fitur RBAC Management adalah modul administrasi untuk mengelola Role-Based Access Control pada aplikasi Layanan Pajak Kabupaten Pasuruan. Sistem saat ini menggunakan Spatie Permission dengan empat role hardcoded (`admin`, `kepala_upt`, `pegawai`, `pemimpin`) dan visibilitas sidebar yang dikontrol secara hardcoded melalui `isAdmin()` dan `isKepalaUpt()`.

Fitur ini memungkinkan administrator untuk mengelola role dan permission secara dinamis melalui antarmuka web, mengassign permission ke role, mengassign role ke user, serta mengubah visibilitas menu sidebar agar berbasis permission — bukan kondisi hardcoded. Fitur ini hanya dapat diakses oleh user dengan role `admin`.

## Glossary

- **RBAC_Manager**: Sistem manajemen role dan permission yang dibangun di atas Spatie Permission
- **Role**: Kelompok akses yang dapat diassign ke user (contoh: `admin`, `kepala_upt`, `pegawai`, `pemimpin`)
- **Permission**: Hak akses granular yang merepresentasikan satu aksi pada satu fitur (contoh: `view monitoring`, `manage employees`)
- **User**: Entitas pengguna aplikasi yang memiliki satu atau lebih Role
- **Guard**: Konteks autentikasi Spatie Permission; seluruh sistem menggunakan guard `web`
- **System_Role**: Role bawaan sistem (`admin`, `kepala_upt`, `pegawai`, `pemimpin`) yang tidak boleh dihapus
- **Sidebar**: Navigasi utama aplikasi di `resources/views/layouts/admin.blade.php`
- **Permission_Group**: Pengelompokan permission berdasarkan modul/fitur (contoh: `master-data`, `monitoring`, `rbac`)

---

## Requirements

### Requirement 1: Manajemen Role

**User Story:** Sebagai admin, saya ingin mengelola daftar role yang tersedia, sehingga saya dapat mendefinisikan struktur akses sesuai kebutuhan organisasi.

#### Acceptance Criteria

1. THE RBAC_Manager SHALL menampilkan daftar semua Role yang tersedia beserta jumlah User dan jumlah Permission yang dimiliki masing-masing Role.
2. WHEN admin mengisi nama role baru dan menekan tombol simpan, THE RBAC_Manager SHALL membuat Role baru dengan guard `web` dan menampilkan pesan konfirmasi keberhasilan.
3. IF nama role yang diinputkan sudah ada dalam sistem, THEN THE RBAC_Manager SHALL menolak penyimpanan dan menampilkan pesan error bahwa nama role sudah digunakan.
4. WHEN admin mengubah nama role yang bukan System_Role, THE RBAC_Manager SHALL memperbarui nama role dan memperbarui semua referensi middleware yang menggunakan nama role tersebut.
5. IF admin mencoba menghapus System_Role (`admin`, `kepala_upt`, `pegawai`, `pemimpin`), THEN THE RBAC_Manager SHALL menolak penghapusan dan menampilkan pesan bahwa role bawaan sistem tidak dapat dihapus.
6. IF admin mencoba menghapus Role yang masih memiliki User aktif, THEN THE RBAC_Manager SHALL menolak penghapusan dan menampilkan jumlah User yang masih menggunakan role tersebut.
7. WHEN admin menghapus Role yang tidak memiliki User aktif dan bukan System_Role, THE RBAC_Manager SHALL menghapus Role beserta seluruh permission assignment-nya.

---

### Requirement 2: Manajemen Permission

**User Story:** Sebagai admin, saya ingin mendefinisikan dan mengelola permission per fitur, sehingga saya dapat mengontrol akses secara granular ke setiap bagian aplikasi.

#### Acceptance Criteria

1. THE RBAC_Manager SHALL menampilkan daftar semua Permission yang tersedia, dikelompokkan berdasarkan Permission_Group.
2. WHEN admin membuat Permission baru dengan nama dan group, THE RBAC_Manager SHALL menyimpan Permission dengan guard `web` dan menampilkan Permission pada group yang sesuai.
3. IF nama permission yang diinputkan sudah ada dalam sistem, THEN THE RBAC_Manager SHALL menolak penyimpanan dan menampilkan pesan error bahwa nama permission sudah digunakan.
4. THE RBAC_Manager SHALL menyediakan seeder yang mendefinisikan permission awal untuk seluruh modul yang ada: `master-data` (tax-types, districts, employees, upts), `monitoring` (realization, forecasting, tax-targets, import), `field-officer`, dan `rbac` (roles, permissions, users).
5. WHEN admin menghapus Permission, THE RBAC_Manager SHALL mencabut permission tersebut dari semua Role yang memilikinya sebelum menghapus.
6. IF admin mencoba menghapus Permission yang merupakan bagian dari modul `rbac`, THEN THE RBAC_Manager SHALL menampilkan peringatan konfirmasi sebelum melanjutkan penghapusan.

---

### Requirement 3: Assignment Permission ke Role

**User Story:** Sebagai admin, saya ingin mengassign permission ke role, sehingga saya dapat mengontrol fitur apa saja yang dapat diakses oleh setiap role.

#### Acceptance Criteria

1. WHEN admin membuka halaman detail Role, THE RBAC_Manager SHALL menampilkan semua Permission yang tersedia dalam bentuk checklist, dikelompokkan berdasarkan Permission_Group, dengan status centang sesuai permission yang sudah dimiliki Role tersebut.
2. WHEN admin menyimpan perubahan permission pada sebuah Role, THE RBAC_Manager SHALL melakukan sync permission (menambah yang baru, mencabut yang dihilangkan) secara atomik dalam satu transaksi database.
3. IF Role yang diedit adalah System_Role `admin`, THEN THE RBAC_Manager SHALL tetap mengizinkan perubahan permission namun menampilkan peringatan bahwa role ini adalah role sistem.
4. THE RBAC_Manager SHALL membersihkan Spatie Permission cache setelah setiap perubahan assignment permission ke role.
5. WHEN admin melihat halaman detail Role, THE RBAC_Manager SHALL menampilkan daftar User yang saat ini memiliki Role tersebut.

---

### Requirement 4: Manajemen User dan Assignment Role

**User Story:** Sebagai admin, saya ingin mengelola user dan mengassign role ke user, sehingga saya dapat mengontrol hak akses setiap pengguna aplikasi.

#### Acceptance Criteria

1. THE RBAC_Manager SHALL menampilkan daftar semua User beserta role yang dimiliki, email, dan status aktif, dengan fitur pencarian berdasarkan nama atau email.
2. WHEN admin membuat User baru melalui halaman RBAC, THE RBAC_Manager SHALL membuat User dengan nama, email, password, dan minimal satu Role yang dipilih.
3. IF email User baru yang diinputkan sudah terdaftar, THEN THE RBAC_Manager SHALL menolak penyimpanan dan menampilkan pesan error bahwa email sudah digunakan.
4. WHEN admin mengassign Role `kepala_upt` ke User, THE RBAC_Manager SHALL menampilkan field tambahan untuk memilih UPT yang akan dipimpin oleh User tersebut.
5. IF admin mengassign Role `kepala_upt` ke User tanpa memilih UPT, THEN THE RBAC_Manager SHALL menolak penyimpanan dan menampilkan pesan bahwa UPT wajib dipilih untuk role Kepala UPT.
6. WHEN admin mengubah assignment role pada User, THE RBAC_Manager SHALL melakukan sync role (mengganti role lama dengan role baru) dan membersihkan Spatie Permission cache.
7. IF admin mencoba menghapus User yang memiliki role `admin` dan User tersebut adalah satu-satunya admin aktif, THEN THE RBAC_Manager SHALL menolak penghapusan dan menampilkan pesan bahwa minimal harus ada satu admin aktif.
8. WHEN admin menghapus User yang bukan satu-satunya admin, THE RBAC_Manager SHALL menghapus User beserta seluruh role assignment dan data terkait (tasks, importLogs).

---

### Requirement 5: Monitoring Akses per Role

**User Story:** Sebagai admin, saya ingin melihat ringkasan akses yang dimiliki setiap role, sehingga saya dapat mengaudit dan memverifikasi konfigurasi hak akses.

#### Acceptance Criteria

1. WHEN admin membuka halaman monitoring akses, THE RBAC_Manager SHALL menampilkan matriks akses yang memperlihatkan setiap Role pada baris dan setiap Permission_Group pada kolom, dengan indikator visual untuk permission yang dimiliki.
2. THE RBAC_Manager SHALL menampilkan jumlah total permission yang dimiliki setiap Role pada halaman monitoring akses.
3. WHEN admin memilih satu Role pada halaman monitoring, THE RBAC_Manager SHALL menampilkan detail lengkap permission yang dimiliki Role tersebut, dikelompokkan per Permission_Group.
4. THE RBAC_Manager SHALL menampilkan daftar User yang memiliki Role tersebut pada halaman detail monitoring Role.
5. WHILE tidak ada Permission yang didefinisikan dalam sistem, THE RBAC_Manager SHALL menampilkan pesan informatif yang mengarahkan admin untuk menjalankan seeder permission.

---

### Requirement 6: Sidebar Berbasis Permission

**User Story:** Sebagai pengguna aplikasi, saya ingin menu sidebar yang tampil sesuai dengan permission yang saya miliki, sehingga saya hanya melihat menu yang relevan dengan peran saya.

#### Acceptance Criteria

1. THE Sidebar SHALL menampilkan item menu hanya jika User yang sedang login memiliki permission yang sesuai dengan menu tersebut, menggantikan kondisi hardcoded `isAdmin()` dan `isKepalaUpt()`.
2. WHEN User tidak memiliki permission apapun untuk sebuah Permission_Group, THE Sidebar SHALL menyembunyikan seluruh section group tersebut termasuk label section-nya.
3. THE Sidebar SHALL tetap menampilkan menu Dashboard dan menu profil untuk semua User yang sudah login, tanpa memerlukan permission khusus.
4. IF User memiliki role `kepala_upt` dengan UPT yang terassign, THE Sidebar SHALL menampilkan link "Data UPT" yang mengarah ke UPT milik User tersebut.
5. THE RBAC_Manager SHALL mendefinisikan mapping antara nama permission dan item menu sidebar dalam satu konfigurasi terpusat, sehingga perubahan permission tidak memerlukan modifikasi template Blade secara langsung.
6. WHEN permission User berubah (role diubah atau permission role diubah), THE Sidebar SHALL mencerminkan perubahan tersebut pada request berikutnya setelah cache Spatie Permission dibersihkan.

---

### Requirement 7: Keamanan dan Proteksi Akses RBAC

**User Story:** Sebagai admin, saya ingin memastikan bahwa fitur manajemen RBAC hanya dapat diakses oleh administrator, sehingga konfigurasi akses sistem terlindungi dari perubahan yang tidak sah.

#### Acceptance Criteria

1. THE RBAC_Manager SHALL memproteksi semua route manajemen RBAC dengan middleware `role:admin`, sehingga hanya User dengan role `admin` yang dapat mengakses halaman tersebut.
2. IF User tanpa role `admin` mencoba mengakses route RBAC secara langsung melalui URL, THEN THE RBAC_Manager SHALL mengembalikan response HTTP 403 Forbidden.
3. THE RBAC_Manager SHALL memvalidasi semua input dari form menggunakan Form Request class sebelum memproses perubahan data role, permission, atau user.
4. WHEN admin melakukan operasi yang bersifat destruktif (hapus role, hapus permission, hapus user), THE RBAC_Manager SHALL meminta konfirmasi eksplisit dari admin sebelum melanjutkan operasi.
5. THE RBAC_Manager SHALL mencatat (log) setiap perubahan assignment role dan permission ke dalam Laravel application log dengan informasi: admin yang melakukan perubahan, entitas yang diubah, dan timestamp.
