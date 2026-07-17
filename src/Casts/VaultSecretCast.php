<?php

namespace Rushing\AuthVault\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Rushing\AuthVault\Data\SecretData;

/**
 * Reversible encrypt/decrypt of the `payload` column, hydrating the per-scheme
 * {@see SecretData} DTO chosen by the row's `scheme`. The dedicated cast (rather than
 * the bare `encrypted` cast) is the indirection that lets an envelope/KMS scheme swap
 * in later keyed by `key_id` — a migration that stays additive. Decrypt only ever runs
 * where the row is reachable (in the owner's tenant frame), so plaintext materialises
 * deep in that frame and only plain-data crosses back.
 *
 * @implements CastsAttributes<SecretData|array<string, mixed>|null, SecretData|array<string, mixed>|null>
 */
class VaultSecretCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): SecretData|array|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode(Crypt::decryptString($value), true);

        $dtoClass = $this->dtoClass($attributes['scheme'] ?? null);

        return $dtoClass !== null ? $dtoClass::from($decoded) : $decoded;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        $array = $value instanceof SecretData ? $value->toArray() : (array) $value;

        return [$key => Crypt::encryptString(json_encode($array))];
    }

    /**
     * @return class-string<SecretData>|null
     */
    protected function dtoClass(?string $scheme): ?string
    {
        if ($scheme === null) {
            return null;
        }

        $class = config("auth-vault.schemes.{$scheme}");

        return is_string($class) && is_subclass_of($class, SecretData::class) ? $class : null;
    }
}
