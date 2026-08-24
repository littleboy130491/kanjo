<?php

namespace App\Services\DocumentApi;

use App\Models\Client;
use InvalidArgumentException;

class ClientSnapshotResolver
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     client_id: int|null,
     *     client_company: string,
     *     client_name: string,
     *     client_email: string|null,
     *     client_phone: string|null,
     *     client_address: string|null,
     *     client_pic_role: string|null,
     *     action: string,
     *     client: Client|null
     * }
     */
    public function resolve(array $payload, bool $persist): array
    {
        $overrides = is_array($payload['client'] ?? null) ? $payload['client'] : [];
        $clientId = $payload['client_id'] ?? null;

        if (filled($clientId)) {
            $client = Client::query()->find($clientId);

            if (! $client instanceof Client) {
                throw new InvalidArgumentException('The selected client id is invalid.');
            }

            $snapshot = $this->fromClient($client);

            return [
                ...$this->applyOverrides($snapshot, $overrides),
                'client_id' => $client->id,
                'action' => 'existing',
                'client' => $client,
            ];
        }

        $snapshot = $this->fromOverrides($overrides);

        if (! $persist) {
            return [
                ...$snapshot,
                'client_id' => null,
                'action' => 'create',
                'client' => null,
            ];
        }

        $client = Client::query()->create([
            'company' => $snapshot['client_company'],
            'name' => $snapshot['client_name'],
            'email' => $snapshot['client_email'],
            'phone' => $snapshot['client_phone'],
            'address' => $snapshot['client_address'],
            'notes' => [],
        ]);

        return [
            ...$snapshot,
            'client_id' => $client->id,
            'action' => 'create',
            'client' => $client,
        ];
    }

    /**
     * @return array{
     *     client_company: string,
     *     client_name: string,
     *     client_email: string|null,
     *     client_phone: string|null,
     *     client_address: string|null,
     *     client_pic_role: string|null
     * }
     */
    private function fromClient(Client $client): array
    {
        return [
            'client_company' => (string) $client->company,
            'client_name' => (string) $client->name,
            'client_email' => $client->email,
            'client_phone' => $client->phone,
            'client_address' => $client->address,
            'client_pic_role' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{
     *     client_company: string,
     *     client_name: string,
     *     client_email: string|null,
     *     client_phone: string|null,
     *     client_address: string|null,
     *     client_pic_role: string|null
     * }
     */
    private function fromOverrides(array $overrides): array
    {
        return [
            'client_company' => (string) ($overrides['company'] ?? ''),
            'client_name' => (string) ($overrides['name'] ?? ''),
            'client_email' => $this->nullableString($overrides['email'] ?? null),
            'client_phone' => $this->nullableString($overrides['phone'] ?? null),
            'client_address' => $this->nullableString($overrides['address'] ?? null),
            'client_pic_role' => $this->nullableString($overrides['pic_role'] ?? null),
        ];
    }

    /**
     * @param  array{
     *     client_company: string,
     *     client_name: string,
     *     client_email: string|null,
     *     client_phone: string|null,
     *     client_address: string|null,
     *     client_pic_role: string|null
     * }  $snapshot
     * @param  array<string, mixed>  $overrides
     * @return array{
     *     client_company: string,
     *     client_name: string,
     *     client_email: string|null,
     *     client_phone: string|null,
     *     client_address: string|null,
     *     client_pic_role: string|null
     * }
     */
    private function applyOverrides(array $snapshot, array $overrides): array
    {
        if ($overrides === []) {
            return $snapshot;
        }

        if (array_key_exists('company', $overrides)) {
            $snapshot['client_company'] = (string) $overrides['company'];
        }

        if (array_key_exists('name', $overrides)) {
            $snapshot['client_name'] = (string) $overrides['name'];
        }

        if (array_key_exists('email', $overrides)) {
            $snapshot['client_email'] = $this->nullableString($overrides['email']);
        }

        if (array_key_exists('phone', $overrides)) {
            $snapshot['client_phone'] = $this->nullableString($overrides['phone']);
        }

        if (array_key_exists('address', $overrides)) {
            $snapshot['client_address'] = $this->nullableString($overrides['address']);
        }

        if (array_key_exists('pic_role', $overrides)) {
            $snapshot['client_pic_role'] = $this->nullableString($overrides['pic_role']);
        }

        return $snapshot;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
