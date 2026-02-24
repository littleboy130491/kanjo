# Activity 01 — Project Setup

## Goal
Prepare the Laravel 12 + Filament 5 environment with all required packages installed and configured.

## Stack
- Laravel 12
- Filament 5
- Tailwind CSS (via Filament)
- Spatie Laravel Translatable
- Solution Forest: filament-translate-field
- awcodes/filament-curator
- Browsershot (PDF)

## Tasks

### 1. Verify Composer Dependencies
Ensure these packages are present in `composer.json`:
- `filament/filament: ^5.2`
- `awcodes/filament-curator: ^5.0`
- `solution-forest/filament-translate-field: ^3.0`
- `spatie/laravel-translatable: *`
- `spatie/browsershot` (add if not present)

### 2. Install Node Dependencies
Puppeteer is required by Browsershot:
```bash
npm install puppeteer
```

### 3. Configure .env
Add global client-access credentials:
```
GLOBAL_ACCESS_USERNAME=
GLOBAL_ACCESS_PASSWORD=
```

### 4. Run Filament Install
```bash
php artisan filament:install --panels
```

### 5. Publish Curator Assets
```bash
php artisan curator:install
```

### 6. Configure Translatable Locales
In `config/translatable.php` (publish if needed):
```php
'locales' => ['en', 'id'],
```

### 7. Configure App Locales
In `config/app.php`:
```php
'locale' => 'en',
'fallback_locale' => 'en',
'supported_locales' => ['en', 'id'],
```

## Acceptance Criteria
- `php artisan serve` runs without errors
- Filament admin panel accessible at `/admin`
- All packages resolve without conflicts
