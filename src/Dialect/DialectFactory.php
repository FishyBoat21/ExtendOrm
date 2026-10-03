<?php
namespace FishyBoat21\ExtendOrm\Dialect;

use PDO;

final class DialectFactory
{
    /** Resolves the dialect for a PDO driver name. */
    public static function forDriver(string $driver): Dialect
    {
        return match (strtolower($driver)) {
            'mysql' => new MySqlDialect($driver),
            default => new AnsiDialect($driver),
        };
    }

    /** Resolves the dialect from a live connection. */
    public static function forConnection(PDO $connection): Dialect
    {
        return self::forDriver((string) $connection->getAttribute(PDO::ATTR_DRIVER_NAME));
    }
}
