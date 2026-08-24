<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Client::query()->orderByDesc('id');
        $term = trim((string) $request->query('q', ''));

        if ($term !== '') {
            $query->where(function ($builder) use ($term): void {
                $builder
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('company', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $clients = $query
            ->limit(50)
            ->get()
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'company' => $client->company,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
            ]);

        return response()->json(['data' => $clients]);
    }
}
