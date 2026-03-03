<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About This Project

This is a quotation and invoice management web app for a web design agency with multiple brands. Built with Laravel 12 and Filament 5.

## Features

- Multi-brand company management
- Client management with service tracking
- Proposal creation and management
- Invoice generation with payment tracking
- Document access for clients via username/password
- PDF generation for proposals and invoices
- Dashboard with revenue and outstanding analytics

## Tech Stack

- **Framework**: Laravel 12
- **Admin Panel**: Filament 5
- **CSS**: Tailwind CSS
- **Translations**: Spatie Laravel Translatable (EN/ID)
- **Media**: Curator (Filament plugin)
- **PDF**: Browsershot

## Installation

```bash
# Clone the repository
git clone https://github.com/littleboy130491/kanjo.git

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Copy environment file
copy .env from .env link

# Run migrations
php artisan migrate

# Seed the database
php artisan db:seed

```

## Default Login

- **URL**: `/admin`
- **Username**: `admin@example.com`
- **Password**: `password`

### Create admin user

```bash
php artisan make:filament-user

```

## Development

```bash
# Run development server
npm run dev

# Build for production
npm run build
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
