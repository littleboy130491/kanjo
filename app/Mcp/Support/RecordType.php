<?php

namespace App\Mcp\Support;

use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\ProposalController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SpkController;
use App\Http\Requests\Api\V1\UpdateClientRequest;
use App\Http\Requests\Api\V1\UpdateCompanyRequest;
use App\Http\Requests\Api\V1\UpdateInvoiceRequest;
use App\Http\Requests\Api\V1\UpdateProposalRequest;
use App\Http\Requests\Api\V1\UpdateServiceRequest;
use App\Http\Requests\Api\V1\UpdateSpkRequest;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\Spk;
use Illuminate\Database\Eloquent\Model;

/**
 * Record types exposed by the MCP server, mapped to their Document API controllers.
 */
enum RecordType: string
{
    case Company = 'company';
    case Client = 'client';
    case Proposal = 'proposal';
    case Invoice = 'invoice';
    case Spk = 'spk';
    case Service = 'service';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return class-string<Model>
     */
    public function model(): string
    {
        return match ($this) {
            self::Company => Company::class,
            self::Client => Client::class,
            self::Proposal => Proposal::class,
            self::Invoice => Invoice::class,
            self::Spk => Spk::class,
            self::Service => Service::class,
        };
    }

    /**
     * @return class-string
     */
    public function controller(): string
    {
        return match ($this) {
            self::Company => CompanyController::class,
            self::Client => ClientController::class,
            self::Proposal => ProposalController::class,
            self::Invoice => InvoiceController::class,
            self::Spk => SpkController::class,
            self::Service => ServiceController::class,
        };
    }

    /**
     * @return class-string
     */
    public function updateRequest(): string
    {
        return match ($this) {
            self::Company => UpdateCompanyRequest::class,
            self::Client => UpdateClientRequest::class,
            self::Proposal => UpdateProposalRequest::class,
            self::Invoice => UpdateInvoiceRequest::class,
            self::Spk => UpdateSpkRequest::class,
            self::Service => UpdateServiceRequest::class,
        };
    }

    public function find(int $id): Model
    {
        return $this->model()::query()->findOrFail($id);
    }
}
