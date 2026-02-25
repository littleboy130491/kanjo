# Activity 13 — Translation Setup (Spatie + Filament Translate Field)

## Goal
Configure bilingual (EN + ID) support across models and Filament admin forms using Spatie Laravel Translatable and Solution Forest's filament-translate-field.

---

## Packages
- `spatie/laravel-translatable` — model-level translation storage
- `solution-forest/filament-translate-field` — Filament UI translation tabs

---

## Locale Configuration

**`config/app.php`:**
```php
'locale' => 'en',
'fallback_locale' => 'en',
'supported_locales' => ['en', 'id'],
```

**`config/translatable.php`** (publish via `php artisan vendor:publish`):
```php
'locales' => ['en', 'id'],
```

---

## Model Integration

All translatable models use the `HasTranslations` trait:
```php
use Spatie\Translatable\HasTranslations;

class Proposal extends Model
{
    use HasTranslations;

    public array $translatable = ['features', 'core_services', ...];
}
```

### JSON Storage Format
Spatie stores each translatable field as a JSON object keyed by locale:
```json
{
  "en": [...],
  "id": [...]
}
```

For JSON array fields with sub-field translation, each text sub-field within the array items is individually structured with locale keys:
```json
[
  {
    "feature_name": { "en": "Responsive Design", "id": "Desain Responsif" },
    "feature_description": { "en": "...", "id": "..." }
  }
]
```

---

## Filament Form Integration

Use `TranslatableFields` wrapper from Solution Forest around repeaters:

```php
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

Translate::make()
    ->schema([
        Repeater::make('features')
            ->schema([
                TextInput::make('feature_name'),
                Textarea::make('feature_description'),
            ]),
    ])
    ->locales(['en', 'id'])
```

This renders tabs — one for each locale — within the form section.

---

## Fields That Are Translatable (Proposals)
- `brief`
- `core_services`
- `features`
- `server`
- `assets`
- `security`
- `support`
- `additional_benefit`
- `add_on`
- `payment`
- `terms_condition`
- `offer_1_project_timeline`
- `offer_2_project_timeline`

## Fields That Are Translatable (Invoices)
- `items`

## Fields That Are Translatable (Company)
- `footer_text`

## Fields That Are NOT Translatable
- `portfolios` (Proposal) — plain JSON array, no locale keys
- `bank`, `pic` (Company) — plain JSON arrays
- `notes` (Client, Service) — plain JSON arrays

---

## Frontend Locale Switching

In the client-facing views, support a locale toggle:
```php
// In controller
$locale = request('lang', 'en');
App::setLocale($locale);
```

Pass locale to views so the language switcher can highlight the active tab.

---

## Translation Retrieval in Views

```blade
{{ $proposal->getTranslation('features', app()->getLocale()) }}
```

Or if locale is already set:
```blade
{{ $proposal->features }}
```

---

## Acceptance Criteria
- Filament form shows EN and ID tabs for all translatable repeater fields
- Saving in one locale does not overwrite the other locale's data
- Client-facing view renders in the selected locale
- `App::setLocale('id')` causes all translatable fields to return Indonesian values
- Fallback to `en` if `id` translation is missing
- Non-translatable JSON arrays (portfolios, bank, pic, notes) are stored as plain arrays without locale keys
