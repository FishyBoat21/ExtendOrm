<?php
namespace FishyBoat21\ExtendOrm\Dialect;

/**
 * MySQL / MariaDB: identifiers are delimited with backticks.
 */
final class MySqlDialect extends AbstractDialect
{
    public function __construct(string $driver = 'mysql')
    {
        parent::__construct($driver);
    }

    protected function quoteCharacter(): string
    {
        return '`';
    }
}
