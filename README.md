# 🧺 Sistem Manajemen Transaksi & Layanan Laundry ("Sistem Laundry") - Laravel

Aplikasi Web Sistem Manajemen Transaksi & Layanan Laundry berbasis framework **Laravel**, antarmuka responsif **Tailwind CSS**, dan arsitektur **MVC (Model-View-Controller)** sesuai dengan standar Ujian Praktikum & Sertifikasi Kompetensi (Serkom) **BNSP / LSP Skema Junior Web Developer**.

---

## 🚀 Fitur Utama

### 1. Autentikasi Mandiri & Redirection Peran (Role-Based Access)
- **Login Terproteksi (`/login`)**: Pemeriksaan kredensial menggunakan `Auth::attempt` dengan regenerasi sesi terhadap serangan *Session Fixation*.
- **Role-Based Redirection**: Pengalihan otomatis saat login sukses:
  - **Administrator** ➔ `/admin/packages`
  - **Customer** ➔ `/catalog`
- **Registrasi Pelanggan Mandiri (`/register`)**: Pendaftar otomatis terdaftar dengan role `customer` dan langsung diautentikasi.
- **Proteksi Akses (RBAC Guard)**: Pengguna customer yang mencoba mengakses panel admin diblokir langsung dengan respon `403 Forbidden`.

### 2. Portal Pelanggan (Customer)
- **Katalog Layanan Laundry (`/catalog`)**: Menampilkan daftar seluruh paket laundry aktif beserta estimasi durasi dan tarif per kg.
- **Formulir Pemesanan Cucian (`/order/create/{package_id}`)**:
  - Live preview perkiraan biaya interaktif via JavaScript saat bobot timbangan diinput.
  - Mendukung input timbangan desimal presisi (`step="any"`, min: 0.5 kg).
  - **Kalkulasi Server-Side Anti-Manipulasi**: Total rupiah dihitung resmi di backend controller:
    ```php
    $total_price = (int) round($package->price_per_kg * $request->weight);
    ```

### 3. Portal Administrator (Admin)
- **Master Paket Laundry (`/admin/packages`)**: CRUD penuh (Tambah, Tampil, Edit, Hapus) paket layanan laundry dengan validasi harga per kg dan estimasi waktu.
- **Rekapitulasi Transaksi Tanpa Filter (`/admin/orders`)**:
  - Kartu metrik ringkasan: Total Transaksi, Total Berat (Kg), dan Total Omset Pendapatan.
  - Tabel rekapitulasi seluruh pesanan masuk dengan implementasi *Eager Loading* (`Order::with('package', 'user')`) untuk mencegah *N+1 Query Problem*.
  - **Pembaruan Status Cucian Instan**: Dropdown select status (`Baru` ➔ `Diproses` ➔ `Selesai` ➔ `Diambil`) yang otomatis mengirimkan request `PATCH` secara instan.

---

## 🗄️ Struktur Basis Data

1. **`users`**: Akun pengguna dengan kolom `role` enum (`admin`, `customer`).
2. **`laundry_packages`**: Master paket laundry (`name`, `price_per_kg`, `estimated_duration`, `description`).
3. **`orders`**: Transaksi pemesanan laundry berelasi foreign key cascade ke `users` dan `laundry_packages` (`user_id`, `package_id`, `customer_name`, `weight`, `total_price`, `status`, `order_date`).

---

## 🔐 Kredensial Default (Seeder)

| Peran (Role) | Alamat Email | Kata Sandi | Halaman Beranda (Redirect) |
|---|---|---|---|
| **Administrator** | `admin@laundry.com` | `admin123` | Master Paket (`/admin/packages`) |
| **Customer** | `customer@gmail.com` | `customer123` | Katalog Layanan (`/catalog`) |

---

## 🛠️ Cara Menjalankan Aplikasi

1. **Konfigurasi Environment (`.env`):**
   Salin file `.env.example` ke `.env` dan sesuaikan koneksi database MySQL / SQLite:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

2. **Jalankan Migrasi & Seeder Database:**
   ```bash
   php artisan migrate:fresh --seed
   ```

3. **Jalankan Server Lokal:**
   ```bash
   php artisan serve
   ```
   Akses aplikasi di browser: [http://127.0.0.1:8000](http://127.0.0.1:8000)

4. **Jalankan Rangkaian Pengujian Otomatis (18 Feature Tests Passed):**
   ```bash
   php artisan test
   ```
