# YoPrint Laravel Coding Project

This project is a submission for the YoPrint Laravel Coding Challenge. It implements a CSV product upload system with asynchronous processing and file deduplication using Laravel.

## 🛠 Features

- 📁 Drag-and-drop CSV file upload interface (Bootstrap)
- 📄 SHA-256 hash-based duplicate file detection
- ⚙️ Background job processing with Laravel Queues
- 📥 CSV parsing and product import into database
- 📊 Upload tracking with `status` indicators
- ✅ Fully CSRF-protected form and backend

## 📂 Technologies Used

- Laravel 10.x
- PHP 8.2
- Bootstrap 5
- Laravel Queues
- MySQL

## 📄 Database Schema

### `uploads` Table
- `id`
- `file_name`
- `file_path`
- `file_hash`
- `status` (`pending`, `processing`, `completed`, `failed`)
- `created_at`, `updated_at`

### `products` Table
- `id`
- `name`
- `sku`
- `description`
- `price`
- `created_at`, `updated_at`

## 🔁 Workflow Overview

1. User uploads a CSV file.
2. Backend checks for duplicate uploads using hash.
3. File saved to disk & record created in `uploads`.
4. Laravel Job `ProcessCsvUpload` is dispatched.
5. Each CSV row is parsed and inserted into `products`.
6. Upload status is updated accordingly.

## 🚀 Getting Started

```bash
git clone https://github.com/yourusername/yoprint-upload-project.git
cd yoprint-upload-project

composer install
cp .env.example .env
php artisan key:generate

# Create your DB and update .env
php artisan migrate
php artisan serve

#Start queue 
php artisan queue:work
