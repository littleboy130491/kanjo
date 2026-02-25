# Activity 02 — Database Migrations

## Goal
Create migrations for all core tables: `companies`, `clients`, `services`, `proposals`, `invoices`.

---

## Migration 1: `companies`

```php
Schema::create('companies', function (Blueprint $table) {
    $table->id();
    $table->string('company_name');
    $table->string('brand_name');
    $table->unsignedBigInteger('logo')->nullable(); // Curator media ID
    $table->text('address')->nullable();
    $table->string('email_1');
    $table->string('email_2')->nullable();
    $table->string('phone_1');
    $table->string('phone_2')->nullable();
    $table->string('tax_id')->nullable();        // NPWP
    $table->string('website')->nullable();
    $table->string('default_currency')->default('IDR');
    $table->string('color_primary')->nullable();  // hex
    $table->string('color_secondary')->nullable(); // hex
    $table->json('footer_text')->nullable();       // translatable
    $table->json('bank')->nullable();              // array of bank objects
    $table->json('pic')->nullable();               // array of PIC objects
    $table->timestamps();
});
```

---

## Migration 2: `clients`

```php
Schema::create('clients', function (Blueprint $table) {
    $table->id();
    $table->string('name');           // Client contact person
    $table->string('company');        // Client's company name
    $table->string('email');
    $table->string('phone');
    $table->json('notes')->nullable(); // non-translatable JSON array
    $table->softDeletes();
    $table->timestamps();
});
```

---

## Migration 3: `services`

```php
Schema::create('services', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('domain')->nullable();         // website URL
    $table->string('start_date')->nullable();     // when service became active
    $table->string('renewal_date')->nullable();   // date and month for renewal
    $table->enum('status', ['terminated', 'on-going', 'suspended'])->default('on-going');
    $table->json('notes')->nullable();            // non-translatable JSON array
    $table->foreignId('client_id')->constrained()->nullOnDelete();
    $table->softDeletes();
    $table->timestamps();
});
```

---

## Migration 4: `proposals`

```php
Schema::create('proposals', function (Blueprint $table) {
    $table->id();

    // Document numbering
    $table->string('document_number')->index();
    $table->unsignedInteger('document_number_raw');
    $table->string('document_number_suffix')->default('NEW');
    $table->boolean('document_number_override')->default(false);

    // Client info (frozen snapshot)
    $table->string('client_company');
    $table->string('client_name');
    $table->string('client_email');
    $table->string('client_phone');

    // Dates
    $table->date('issue_date');
    $table->date('valid_until')->nullable();

    // Financials
    $table->string('currency')->default('IDR');
    $table->decimal('tax_rate', 5, 2)->default(11);
    $table->decimal('tax_amount', 15, 2)->default(0);
    $table->decimal('total_amount', 15, 2)->default(0);

    // Translatable JSON fields
    $table->json('brief')->nullable();
    $table->json('core_services')->nullable();
    $table->json('features')->nullable();
    $table->json('server')->nullable();
    $table->json('assets')->nullable();
    $table->json('security')->nullable();
    $table->json('support')->nullable();
    $table->json('additional_benefit')->nullable();
    $table->json('add_on')->nullable();
    $table->json('payment')->nullable();
    $table->json('terms_condition')->nullable();

    // Non-translatable JSON fields
    $table->json('portfolios')->nullable();

    // Offer 1
    $table->string('offer_name_1')->nullable();
    $table->decimal('offer_1_price', 15, 2)->nullable();
    $table->decimal('offer_1_original_price', 15, 2)->nullable();
    $table->decimal('offer_1_renewal_price', 15, 2)->nullable();
    $table->decimal('offer_1_original_renewal_price', 15, 2)->nullable();
    $table->json('offer_1_project_timeline')->nullable(); // translatable

    // Offer 2
    $table->string('offer_name_2')->nullable();
    $table->decimal('offer_2_price', 15, 2)->nullable();
    $table->decimal('offer_2_original_price', 15, 2)->nullable();
    $table->decimal('offer_2_renewal_price', 15, 2)->nullable();
    $table->decimal('offer_2_original_renewal_price', 15, 2)->nullable();
    $table->json('offer_2_project_timeline')->nullable(); // translatable

    // Status & access
    $table->enum('status', ['draft', 'published'])->default('draft');
    $table->string('access_username')->nullable();
    $table->string('access_password')->nullable(); // hashed

    // Internal
    $table->text('notes')->nullable();

    // Relations
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();

    $table->softDeletes();
    $table->timestamps();
});
```

---

## Migration 5: `invoices`

```php
Schema::create('invoices', function (Blueprint $table) {
    $table->id();

    // Document numbering
    $table->string('document_number')->index();
    $table->unsignedInteger('document_number_raw');
    $table->string('document_number_suffix')->default('NEW');
    $table->boolean('document_number_override')->default(false);

    // Client info (frozen snapshot)
    $table->string('client_company');
    $table->string('client_name');
    $table->string('client_email');
    $table->string('client_phone');

    // Dates
    $table->date('issue_date');
    $table->date('due_date');

    // Financials
    $table->string('currency')->default('IDR');
    $table->decimal('tax_rate', 5, 2)->default(0);
    $table->decimal('tax_amount', 15, 2)->default(0);
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->decimal('total', 15, 2)->default(0);

    // Items (translatable sub-fields)
    $table->json('items')->nullable();

    // Status
    $table->enum('status', ['draft', 'published'])->default('draft');
    $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('unpaid');

    // Payment tracking
    $table->decimal('paid_amount', 15, 2)->default(0);
    $table->timestamp('paid_at')->nullable();
    $table->string('payment_method')->nullable();

    // Access
    $table->string('access_username')->nullable();
    $table->string('access_password')->nullable(); // hashed

    // Internal
    $table->text('notes')->nullable();

    // Relations
    $table->foreignId('proposal_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();

    $table->softDeletes();
    $table->timestamps();
});
```

---

## Unique Constraint Notes

The PRD specifies a compound unique constraint on `type + raw_number + month + year` to prevent duplicate document numbers within a month. Since proposals and invoices are in separate tables, each table needs its own constraint. The month/year are derived from `issue_date`.

Options:
1. Add computed/virtual columns `issue_month` and `issue_year` and create a unique index on `(document_number_raw, issue_month, issue_year)`.
2. Enforce uniqueness at the app level inside `DocumentNumberService` using a DB transaction with row locking.

Recommended: Use option 2 (app-level enforcement in DocumentNumberService with DB locking) since MySQL virtual columns on date parts add complexity.

## Acceptance Criteria
- `php artisan migrate` runs cleanly
- All five tables exist with correct columns and indexes
- Foreign keys are properly constrained
- `client_id` and `service_id` are nullable on proposals and invoices
- `client_phone` exists on both proposals and invoices
- `offer_1_original_renewal_price` and `offer_2_original_renewal_price` exist on proposals
