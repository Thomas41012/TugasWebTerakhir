# 🌐 Global Supply Chain Intelligence
### *Platform Intelijen & Analisis Risiko Rantai Pasok Global Real-Time*

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-3.x-4E5BA6?style=for-the-badge&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38BDF8?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16.x-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Render](https://img.shields.io/badge/Render-Deployed-46E3B7?style=for-the-badge&logo=render&logoColor=white)](https://global-supply-chain.onrender.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

---

## 📌 Tentang Proyek (*About Project*)

**Global Supply Chain Intelligence** adalah platform web intelijen berbasis **Laravel 12 & Livewire 3** yang dirancang untuk memantau, menganalisis, dan memprediksi tingkat risiko rantai pasok global secara *real-time*. 

Sistem ini mengintegrasikan data terdistribusi dari **5 REST API Internasional** (Cuaca, Profil Geografis, Ekonomi Bank Dunia, Kurs Mata Uang, dan Berita Logistik) untuk menghasilkan **Indeks Skor Risiko Rantai Pasok (0 - 100)** secara otomatis.

---

## 🚀 Fitur Utama (*Key Features*)

### 1. 📊 Global Executive Dashboard
- **KPI Real-Time**: Ringkasan total negara aktif, pelabuhan utama, rata-rata skor risiko global, dan statistik berita logistik terkini.
- **Auto-Sync Trigger**: Fitur sinkronisasi data otomatis dari 5 API eksternal hanya dengan 1 klik.

### 2. 🗺️ Interactive Maritime Map (Leaflet.js)
- Visualisasi geolokasi pelabuhan maritim utama di dunia.
- Penanda (*marker*) interaktif lengkap dengan indikator warna level risiko (*Low*, *Medium*, *High*, *Critical*).

### 3. 🧮 Automated Risk Scoring Engine
- **Algoritma Risiko Gabungan**: Menghitung skor risiko berdasarkan:
  - 🌤️ **Kondisi Cuaca Ekstrem** (Suhu & Angin via *Open-Meteo*)
  - 📉 **Indikator Ekonomi Makro** (Inflasi & PDB via *World Bank*)
  - 💱 **Fluktuasi Kurs Valuta Asing** (USD vs Mata Uang Lokal via *ExchangeRate API*)
  - 🧠 **Sentimen Berita Logistik** (*GNews API* & NLP Keyword Sentiment)

### 4. 📰 News Intelligence & Sentiment Analysis
- Feed berita logistik & maritim global real-time.
- Klasifikasi sentimen otomatis (*Positif*, *Netral*, *Negatif*).
- Tombol baca langsung (*Direct Read Link*) ke artikel sumber berita asli.

### 5. ⚔️ Head-to-Head Country Comparison Tool
- Fitur komparasi *side-by-side* antara 2 negara untuk membandingkan tingkat risiko, stabilitas mata uang, cuaca, dan kondisi ekonomi.

### 6. 🩺 API Health & Status Monitor
- Dashboard pemantau kesehatan *real-time* untuk 5 REST API eksternal (Status ONLINE/OFFLINE, Latency/Response Time dalam ms, dan Log Error).

### 7. 🛡️ Admin Management Panel
- Manajemen role pengguna, pemeliharaan data pelabuhan maritim, penyesuaian sentimen berita, dan audit log sistem.

---

## 🏗️ Teknologi & Arsitektur (*Tech Stack & Architecture*)

| Komponen | Teknologi |
| :--- | :--- |
| **Backend** | PHP 8.2+, Laravel 12.x |
| **Frontend Reaktif** | Livewire 3.x, Livewire Volt |
| **Styling & UI** | Tailwind CSS, Dark Mode, Glassmorphism UI |
| **Peta Interaktif** | Leaflet.js |
| **Database** | SQLite / MySQL (Lokal), PostgreSQL (Production Render) |
| **Deployment & Hosting** | Docker Container, Render Cloud Hosting |

---

## 🌐 Integrasi 5 REST API Eksternal

1. 🌤️ **Open-Meteo API**: Data cuaca real-time di pelabuhan & negara (`https://api.open-meteo.com/v1`).
2. 🇮🇩 **REST Countries API**: Data geografis & profil negara (`https://restcountries.com/v3.1`).
3. 🏦 **World Bank API**: Indikator ekonomi PDB, inflasi, populasi (`https://api.worldbank.org/v2`).
4. 💱 **Exchange Rate API**: Nilai tukar valuta asing real-time (`https://open.er-api.com/v6`).
5. 📰 **GNews API**: Feed berita rantai pasok & maritim (`https://gnews.io/api/v4`).

---

## 💻 Panduan Instalasi Lokal (*Local Setup Guide*)

### Prasyarat:
- PHP >= 8.2
- Composer >= 2.x
- Node.js & NPM

### Langkah-langkah:

1. **Clone Repository**:
   ```bash
   git clone https://github.com/Thomas41012/TugasWebTerakhir.git
   cd TugasWebTerakhir
   ```

2. **Install Depedensi Composer & NPM**:
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi File Environment (`.env`)**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Migrasi Database & Seeding Data Baseline**:
   ```bash
   php artisan migrate --seed
   ```

5. **Jalankan Development Server**:
   ```bash
   php artisan serve
   ```
   Akses aplikasi di browser: **`http://127.0.0.1:8000`**

---

## 👤 Penulis / Pengembang (*Author*)

* **Nama / Akun GitHub**: [Thomas41012](https://github.com/Thomas41012)
* **Proyek**: Tugas Akhir Pemrograman Web (*Global Supply Chain Intelligence*)

---

## 📜 Lisensi (*License*)

Proyek ini berada di bawah lisensi terbuka [MIT License](LICENSE).
