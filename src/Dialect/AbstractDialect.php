<?php
namespace FishyBoat21\ExtendOrm\Dialect;

use FishyBoat21\ExtendOrm\ExtendORMException;

/**
 * Shared behaviour for dialects that differ only in their identifier quote
 * character and that all accept the standard "LIMIT n OFFSET m" form.
 */
abstract class AbstractDialect implements Dialect
{
    public function __construct(private readonly string $driver = '')
    {
    }

    /** The character used to delimit identifiers, e.g. ` or ". */
    abstract protected function quoteCharacter(): string;

    public function name(): string
    {
        return $this->driver;
    }

    public function quoteIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        if ($identifier === '*') {
            return '*';
        }
        if ($identifier === '') {
            throw new ExtendORMException('Cannot build a query with an empty identifier.');
        }

        $quote = $this->quoteCharacter();
        $quoted = [];
        foreach (explode('.', $identifier) as $part) {
            $part = trim($part);
            if ($part === '') {
                throw new ExtendORMException("Malformed identifier '{$identifier}'.");
            }
            if ($part === '*') {
                $quoted[] = '*';
                continue;
            }
            // Doubling the quote character is the standard escape.
            $quoted[] = $quote . str_replace($quote, $quote . $quote, $part) . $quote;
        }

        return implode('.', $quoted);
    }

    public function limitClause(?int $limit, ?int $offset): string
    {
        if ($limit === null) {
            return '';
        }
        if ($offset !== null && $offset > 0) {
            return " LIMIT $limit OFFSET $offset";
        }

        return " LIMIT $limit";
    }
}
