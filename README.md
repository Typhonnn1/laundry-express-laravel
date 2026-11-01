# 🧺 Sistem Laundry Express (Cuci Kilat) - Laravel

Aplikasi Web Sistem Pendaftaran Layanan Laundry & Cuci Kilat berbasis framework **Laravel**, antarmuka **Bootstrap 5**, dan ikon **Font Awesome**.

---

## 🚀 Fitur Utama

### 1. Portal Pelanggan
- **Katalog Layanan Interaktif**: Menampilkan daftar harga paket cuci Kiloan, Satuan, dan Express.
- **Kalkulator Dinamis & Formulir Pemesanan**: Perhitungan subtotal dan total biaya secara instan (real-time tanpa reload halaman) saat memilih layanan dan menginput jumlah/berat.
- **Nota Kuitansi Digital (`/nota/{order}`)**: Rincian pesanan lengkap dengan fitur:
  - Tombol **Kirim WhatsApp**: Otomatis membuka chat WhatsApp dengan teks format kuitansi rapi.
  - Tombol **Cetak Struk**: Menjalankan aksi cetak bersih (`window.print()`).
- **Pelacakan Status Cucian (`Lacak Pesanan`)**: Cari status cucian menggunakan nomor WhatsApp atau Nomor Nota dengan visualisasi progress bar bertahap (`Antrean` ➔ `Dicuci` ➔ `Selesai`).

### 2. Portal Administrator (`/admin`)
- **Autentikasi Aman**: Login menggunakan username (`admin` / `admin123`) dengan proteksi CSRF, proteksi middleware `auth`, dan penghentian sesi saat logout.
- **Dasbor Analitik (`/admin`)**: Kartu ringkasan metrik omzet pendapatan (`total_price`), jumlah pesanan antrean, sedang dicuci, dan selesai.
- **Kelola Master Layanan (`/admin/layanan`)**: CRUD penuh (Tambah, Lihat, Ubah, Hapus) paket layanan cuci.
- **Kelola Transaksi & Status Cuci (`/admin/pesanan`)**: Filter status, pencarian keyword, dropdown instan ubah status cucian, dan hapus transaksi.

---

## 🗄️ Struktur Basis Data (`db_laundry_express`)

1. **`users`**: Akun petugas admin (`name`, `username`, `email`, `password`).
2. **`services`**: Master tarif layanan cuci (`service_name`, `category`, `price_per_unit`, `unit_type`, `description`).
3. **`orders`**: Header transaksi pelanggan (`customer_name`, `whatsapp_number`, `total_price`, `status`).
4. **`order_details`**: Detail baris transaksi berelasi FK (`order_id`, `service_id`, `qty_or_weight`, `subtotal`).

---

## 🔐 Kredensial Default

- **URL Admin**: `http://127.0.0.1:8000/login`
- **Username**: `admin`
- **Password**: `admin123`

---

## 🛠️ Cara Menjalankan Aplikasi

1. **Jalankan Migrasi & Seeder Database (MySQL):**
   ```bash
   php artisan migrate:fresh --seed
   ```

2. **Jalankan Server Lokal:**
   ```bash
   php artisan serve
   ```
   Akses aplikasi di: `http://127.0.0.1:8000`

3. **Jalankan Rangkaian Pengujian Otomatis (402 Tests Passed):**
   ```bash
   php artisan test
   ```
