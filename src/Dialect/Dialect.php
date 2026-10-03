<?php
namespace FishyBoat21\ExtendOrm\Dialect;

/**
 * Database-specific SQL rendering.
 *
 * The builder delegates everything that differs between drivers to a Dialect, so
 * supporting a new database means adding one class rather than editing the
 * query builder.
 */
interface Dialect
{
    /** The PDO driver name this dialect was resolved for. */
    public function name(): string;

    /**
     * Quotes one identifier, supporting "table.column" and a trailing "*".
     *
     * @throws \FishyBoat21\ExtendOrm\ExtendORMException on an empty or malformed identifier
     */
    public function quoteIdentifier(string $identifier): string;

    /** Renders a LIMIT/OFFSET clause, or '' when the query is not paged. */
    public function limitClause(?int $limit, ?int $offset): string;
}
