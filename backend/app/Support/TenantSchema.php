<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Operaciones de bajo nivel sobre los schemas PostgreSQL de cada tenant.
 *
 * El nombre del schema SIEMPRE coincide con el subdominio del tenant y se valida como
 * etiqueta DNS estricta antes de interpolarse en una sentencia SQL: `SET search_path` /
 * `CREATE SCHEMA` no admiten parámetros enlazados, así que la única defensa frente a
 * inyección es validar + entrecomillar el identificador.
 *
 * En drivers sin schemas (SQLite en tests) todas las operaciones son no-op silenciosas.
 */
final class TenantSchema
{
    /** Etiqueta DNS estricta: idéntico a lo que admite un subdominio real. */
    public const PATTERN = '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/';

    public static function isValid(string $schema): bool
    {
        return preg_match(self::PATTERN, $schema) === 1;
    }

    public static function assertValid(string $schema): void
    {
        if (! self::isValid($schema)) {
            throw new InvalidArgumentException("Identificador de schema no válido: '{$schema}'.");
        }
    }

    /** Fija el search_path al schema del tenant con `public` como fallback (uso en runtime). */
    public static function use(string $schema): void
    {
        self::assertValid($schema);

        if (! self::isPgsql()) {
            return;
        }

        DB::statement(sprintf('SET search_path TO "%s", public', $schema));
    }

    /**
     * Fija el search_path SOLO al schema del tenant (sin `public`).
     *
     * Imprescindible al migrar: si `public` estuviera en el path, el migrador encontraría
     * la tabla `migrations` de `public` y registraría ahí las migraciones del tenant,
     * contaminando el log entre schemas. Aislando el path, cada tenant tiene su propia
     * tabla `migrations` en su schema.
     */
    public static function useOnly(string $schema): void
    {
        self::assertValid($schema);

        if (! self::isPgsql()) {
            return;
        }

        DB::statement(sprintf('SET search_path TO "%s"', $schema));
    }

    public static function usePublic(): void
    {
        if (! self::isPgsql()) {
            return;
        }

        DB::statement('SET search_path TO public');
    }

    public static function create(string $schema): void
    {
        self::assertValid($schema);

        if (! self::isPgsql()) {
            return;
        }

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $schema));
    }

    public static function drop(string $schema): void
    {
        self::assertValid($schema);

        if (! self::isPgsql()) {
            return;
        }

        DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $schema));
    }

    private static function isPgsql(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }
}
