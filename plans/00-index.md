# Plans Index — Quotation & Invoice Web App

## Activity Breakdown

| # | File | Activity | Description |
|---|------|----------|-------------|
| 01 | [01-project-setup.md](./01-project-setup.md) | Project Setup | Install packages, configure .env, Filament, Curator, locales |
| 02 | [02-database-migrations.md](./02-database-migrations.md) | Database Migrations | `companies`, `clients`, `services`, `proposals`, `invoices` tables |
| 03 | [03-models.md](./03-models.md) | Eloquent Models | Company, Client, Service, Proposal, Invoice — casts, translatable, relationships |
| 04 | [04-document-numbering.md](./04-document-numbering.md) | Document Numbering | `QUO/001/IV/26/NEW` format, monthly reset, override logic |
| 05 | [05-filament-resources.md](./05-filament-resources.md) | Filament Resources | CRUD forms for Company, Client, Service, Proposal, Invoice, User |
| 06 | [06-filament-tables-and-filters.md](./06-filament-tables-and-filters.md) | Tables & Filters | Columns, filters, search for Proposal and Invoice tables |
| 07 | [07-custom-actions.md](./07-custom-actions.md) | Custom Actions | Convert, Renewal, Duplicate, PDF, Mark as Paid, Create Client, Create Service, View Proposal |
| 08 | [08-client-access.md](./08-client-access.md) | Client Access Auth | Per-document session auth, global .env fallback |
| 09 | [09-frontend-views.md](./09-frontend-views.md) | Frontend Views | Client-facing Blade views for proposals and invoices |
| 10 | [10-pdf-generation.md](./10-pdf-generation.md) | PDF Generation | Browsershot setup, print CSS, PDF download routes |
| 11 | [11-scheduled-command.md](./11-scheduled-command.md) | Scheduled Command | `documents:check-overdue` — expire proposals, flag overdue invoices |
| 12 | [12-dashboard-widgets.md](./12-dashboard-widgets.md) | Dashboard Widgets | 4 Filament stats: outstanding, overdue, pending, revenue |
| 13 | [13-translation-setup.md](./13-translation-setup.md) | Translation Setup | Spatie + filament-translate-field, EN/ID bilingual config |

## Suggested Build Order

```
01 → 02 → 03 → 04 → 05 → 06 → 07 → 08 → 09 → 10 → 11 → 12 → 13
```

Activities 11, 12, and 13 can be done in parallel after Activity 07.
