<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds the slugs and the codes of the store.
 *
 * The catalog is addressed by name, not by the identifier of the row: a
 * product lives at "/products/camiseta-azul" and a category at
 * "/categories/ropa". The name the staff writes is turned into that address,
 * and the address is made unique, because a duplicate address would break the
 * links of the store.
 *
 * The rows that were removed also hold their address: a product that was
 * taken off the catalog keeps its slug, so the address of a new product is
 * never the one that was freed.
 */
final class Slug
{
    /**
     * An address that no other row of the table holds.
     *
     * @param  string  $table  The table the address belongs to.
     * @param  string  $value  The name the address is built from.
     * @param  int|null  $ignoreId  The row that is being written, so it does not collide with itself.
     */
    public static function unique(string $value, string $table, ?int $ignoreId = null): string
    {
        return self::make($value, $table, 'slug', $ignoreId, '-');
    }

    /**
     * A code that no other row of the table holds.
     *
     * The codes of the methods of the store are read by the store itself, so
     * they are written in snake case: "flat_rate", "wompi", "cash_on_delivery".
     *
     * @param  string  $table  The table the code belongs to.
     * @param  string  $value  The name the code is built from.
     * @param  int|null  $ignoreId  The row that is being written, so it does not collide with itself.
     */
    public static function uniqueCode(string $value, string $table, ?int $ignoreId = null): string
    {
        return self::make($value, $table, 'code', $ignoreId, '_');
    }

    /**
     * An address or a code that is free.
     *
     * @param  string  $table  The table it belongs to.
     * @param  string  $column  The column the address is written in.
     * @param  int|null  $ignoreId  The row that is being written.
     * @param  string  $separator  The word that joins the parts of the address.
     */
    private static function make(string $value, string $table, string $column, ?int $ignoreId, string $separator): string
    {
        $base = Str::slug(trim($value), $separator);

        if ($base === '') {
            $base = 'item';
        }

        $candidate = $base;
        $suffix = 1;

        while (self::taken($table, $column, $candidate, $ignoreId)) {
            $suffix++;
            $candidate = $base.$separator.$suffix;
        }

        return $candidate;
    }

    /**
     * Whether a row of the table already holds the address.
     */
    private static function taken(string $table, string $column, string $candidate, ?int $ignoreId): bool
    {
        return DB::table($table)
            ->where($column, $candidate)
            ->when($ignoreId !== null, fn (Builder $query): Builder => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
