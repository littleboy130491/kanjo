<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\DocumentApi\DocumentUpdater;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        $companies = Company::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Company $company): array => $this->payload($company));

        return response()->json(['data' => $companies]);
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json(['data' => $this->payload($company)]);
    }

    public function update(UpdateCompanyRequest $request, Company $company, DocumentUpdater $updater): JsonResponse
    {
        return response()->json($updater->company($company, $request->validated()));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Company $company): array
    {
        $pics = [];

        foreach (is_array($company->pic) ? $company->pic : [] as $index => $pic) {
            if (! is_array($pic)) {
                continue;
            }

            $pics[] = [
                'index' => (int) $index,
                'pic_name' => (string) ($pic['pic_name'] ?? ''),
                'pic_role' => (string) ($pic['pic_role'] ?? ''),
            ];
        }

        return [
            'id' => $company->id,
            'company_name' => $company->company_name,
            'brand_name' => $company->brand_name,
            'address' => $company->address,
            'email_1' => $company->email_1,
            'phone_1' => $company->phone_1,
            'default_currency' => $company->default_currency,
            'pic' => $pics,
        ];
    }
}
