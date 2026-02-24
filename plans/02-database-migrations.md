# Activity 02 — Database Migrations

## Goal
Create migrations for all core tables: `companies`, `proposals`, `invoices`.

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

## Migration 2: `proposals`

```php
Schema::create('proposals', function (Blueprint $table) {
    $table->id();

    // Document numbering
    $table->string('document_number')->index();
    $table->unsignedInteger('document_number_raw');
    $table->string('document_number_suffix')->default('NEW');
    $table->boolean('document_number_override')->default(false);
    $table->unique(['type', 'document_number_raw', 'issue_month', 'issue_year'], 'proposals_doc_unique');
    // note: type is implicitly 'QUO'; use DB-level check or app-level only

    // Client info
    $table->string('client_company');
    $table->string('client_name');
    $table->string('client_email');

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
    $table->json('offer_1_project_timeline')->nullable(); // translatable sub-fields

    // Offer 2
    $table->string('offer_name_2')->nullable();
    $table->decimal('offer_2_price', 15, 2)->nullable();
    $table->decimal('offer_2_original_price', 15, 2)->nullable();
    $table->decimal('offer_2_renewal_price', 15, 2)->nullable();
    $table->json('offer_2_project_timeline')->nullable(); // translatable sub-fields

    // Status & access
    $table->enum('status', ['draft', 'published'])->default('draft');
    $table->string('access_username')->nullable();
    $table->string('access_password')->nullable(); // hashed

    // Internal
    $table->text('notes')->nullable();

    // Relations
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();

    $table->softDeletes();
    $table->timestamps();
});
```

---

## Migration 3: `invoices`

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

    $table->softDeletes();
    $table->timestamps();
});
```

---

## Unique Constraint Notes
For `proposals` and `invoices`, add a helper index to detect duplicate document numbers within a month:
```php
$table->unique(['document_number_raw', 'issue_month', 'issue_year'], 'unique_doc_per_month');
```
> `issue_month` and `issue_year` are virtual/computed columns or enforced at app level.

## Acceptance Criteria
- `php artisan migrate` runs cleanly
- All three tables exist with correct columns and indexes
- Foreign keys are properly constrained
