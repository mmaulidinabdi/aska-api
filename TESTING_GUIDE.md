# Aska Email Verification API - Testing Guide

## Setup Environment di Postman

### 1. Import Collection
- Buka Postman
- Click **Import** → Upload `Aska_Email_Verification_API.postman_collection.json`

### 2. Import Environment
- Click **Environments** (di sidebar)
- Click **Import**
- Upload `Aska_Environment.postman_environment.json`
- Pilih environment ini dari dropdown (kanan atas)

### 3. Setting Variables
Klik environment di dropdown → set ini:
- `base_url`: `http://localhost:8000`
- `email`: `test@example.com` (ganti sesuai keinginan)
- `password`: `password123` (sesuai yang Anda ingin test)

---

## Testing Flow

### Step 1: Register (Buat Akun)
**Endpoint:** `POST /api/auth/register`

**Request Body:**
```json
{
  "email": "{{email}}",
  "password": "{{password}}",
  "password_confirmation": "{{password}}"
}
```

**Expected Response (201 Created):**
```json
{
  "success": true,
  "message": "Registrasi berhasil! Kode OTP telah dikirim ke email Anda.",
  "data": {
    "user_id": 1,
    "email": "test@example.com",
    "expires_in": "5 menit"
  }
}
```

**Save Response:**
- Copy `user_id` → set di variable `user_id`

---

### Step 2: Check Verification Status
**Endpoint:** `GET /api/auth/verification-status?email={{email}}`

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Email belum diverifikasi",
  "data": {
    "verified": false,
    "otp_sent": true,
    "otp_expired": false,
    "expires_at": "2026-06-16 14:35:00",
    "message": "OTP masih berlaku. Silakan input kode untuk verifikasi."
  }
}
```

**Info:** Status ini menunjukkan OTP ada dan masih berlaku

---

### Step 3: Cek Email di Mailtrap
1. Buka https://mailtrap.io
2. Login ke akun Anda
3. Dashboard → Demo Inbox
4. **Copy kode OTP dari email** (6 digit)
5. Set di variable `otp_code`

---

### Step 4: Verify OTP
**Endpoint:** `POST /api/auth/verify-otp`

**Request Body:**
```json
{
  "email": "{{email}}",
  "otp_code": "{{otp_code}}"
}
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Email berhasil diverifikasi",
  "data": {
    "user_id": 1,
    "email": "test@example.com",
    "token": "1|abcdef123456xyz..."
  }
}
```

**Save Response:**
- Copy `token` → set di variable `token`

---

### Step 5: Login
**Endpoint:** `POST /api/auth/login`

**Request Body:**
```json
{
  "email": "{{email}}",
  "password": "{{password}}"
}
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user_id": 1,
    "email": "test@example.com",
    "token": "2|abcdef123456xyz..."
  }
}
```

**Save Response:**
- Update `token` variable dengan token baru

---

### Step 6: Get Profile (Protected Route)
**Endpoint:** `GET /api/auth/profile`

**Header Required:**
```
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Data profil user",
  "data": {
    "id": 1,
    "email": "test@example.com",
    "email_verified_at": "2026-06-16 14:30:45",
    "created_at": "2026-06-16 14:25:00",
    "updated_at": "2026-06-16 14:30:45"
  }
}
```

---

### Step 7: Logout (Protected Route)
**Endpoint:** `POST /api/auth/logout`

**Header Required:**
```
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Logout berhasil"
}
```

**Note:** Setelah logout, token tidak bisa digunakan lagi

---

## Skenario Testing Tambahan

### Test Case: OTP Expired
1. Register akun baru
2. Tunggu 5 menit (OTP akan expired)
3. Cek status: `GET /api/auth/verification-status?email=test@example.com`
4. Response akan show: `"otp_expired": true`
5. Verify akan gagal dengan message "Kode OTP sudah kadaluarsa"

### Test Case: Resend OTP
1. Setelah register, tunggu 2 menit
2. Call: `POST /api/auth/resend-otp` dengan email
3. OTP baru akan dikirim ke Mailtrap
4. Gunakan OTP baru untuk verify

### Test Case: Invalid OTP
1. Register akun
2. Verify dengan OTP yang salah
3. Response akan show: `"message": "Kode OTP tidak valid"`

### Test Case: Email Already Registered
1. Register dengan email: `test@example.com`
2. Register ulang dengan email yang sama
3. Response akan show: `"email.unique": "Email sudah terdaftar"`

### Test Case: Protected Route Without Token
1. Call `GET /api/auth/profile` **tanpa** header Authorization
2. Response (401 Unauthorized):
```json
{
  "message": "Unauthenticated."
}
```

---

## Tips & Troubleshooting

### Email tidak masuk ke Mailtrap?
- Cek `.env` file MAIL_* settings
- Pastikan `MAIL_MAILER=smtp`
- Test connection: `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('xxx@example.com'))`

### OTP tidak valid?
- Pastikan copy dengan benar dari email
- Pastikan belum kadaluarsa (5 menit)
- Cek di database: `SELECT * FROM email_verifications`

### Token expired?
- Jika token tidak bekerja, login ulang untuk dapat token baru
- Default token valid sampai session end

### CORS Error?
- Pastikan server Laravel berjalan di port 8000
- Cek `config/cors.php` jika ada cors policy

---

## Quick Start Command

```bash
# Terminal 1: Jalankan Laravel Server
php artisan serve

# Terminal 2: Monitor logs (optional)
tail -f storage/logs/laravel.log
```

Setelah server jalan:
1. Import collection & environment ke Postman
2. Set environment yang diimport
3. Mulai testing dari Step 1 (Register)

---

**Good luck testing!** 🚀
