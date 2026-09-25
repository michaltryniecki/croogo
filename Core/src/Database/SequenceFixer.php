<?php
declare(strict_types=1);

namespace Croogo\Core\Database;

use Cake\Database\Connection;
use Cake\Database\Driver\Postgres;
use Cake\Datasource\ConnectionManager;
use Cake\Log\LogTrait;
use Psr\Log\LogLevel;

/**
 * Moves Postgres sequences past rows inserted with explicit ids (seeds).
 */
class SequenceFixer
{
    use LogTrait;

    /**
     * @param \Cake\Database\Connection|string $connection Connection or its name
     * @param array<string> $tables Limit to these tables, all tables when empty
     * @return void
     */
    public function fix(Connection|string $connection, array $tables = []): void
    {
        $db = $connection instanceof Connection ? $connection : ConnectionManager::get($connection);
        $driver = $db->getDriver();

        if ($driver instanceof Postgres) {
            $this->fixPostgres($db, $tables);
        }
    }

    /**
     * @param \Cake\Database\Connection $db Connection
     * @param array<string> $tables Limit to these tables, all tables when empty
     * @return void
     */
    protected function fixPostgres(Connection $db, array $tables = []): void
    {
        $config = $db->config();
        $schema = $config['schema'] ?? 'public';
        $driver = $db->getDriver();

        // Both kinds of sequence-backed columns: `serial` (nextval() default) and
        // identity columns, which is what cakephp/migrations 5 creates. The latter
        // have no column_default, so pg_get_serial_sequence() names the sequence.
        $columns = $db->execute(
            "SELECT table_name, column_name,
                    pg_get_serial_sequence(quote_ident(table_schema) || '.' || quote_ident(table_name), column_name)
                        AS sequence_name
               FROM information_schema.columns
              WHERE table_catalog = current_database()
                AND table_schema = :schema
                AND (column_default LIKE 'nextval%' OR is_identity = 'YES')",
            ['schema' => $schema],
        )->fetchAll('assoc');

        foreach ($columns as $column) {
            if ($tables && !in_array($column['table_name'], $tables, true)) {
                continue;
            }
            if (empty($column['sequence_name'])) {
                continue;
            }

            $max = $db->execute(sprintf(
                'SELECT MAX(%s) AS max FROM %s',
                $driver->quoteIdentifier($column['column_name']),
                $driver->quoteIdentifier($schema . '.' . $column['table_name']),
            ))->fetch('assoc');
            $max = (int)($max['max'] ?? 0);

            // Empty table: next value is 1. Otherwise: next value is max + 1.
            $db->execute(
                'SELECT setval(CAST(:sequence AS regclass), :value, :called)',
                ['sequence' => $column['sequence_name'], 'value' => max($max, 1), 'called' => $max > 0],
                ['sequence' => 'string', 'value' => 'integer', 'called' => 'boolean'],
            );
            $this->log(
                sprintf('Sequence %s reset to %d', $column['sequence_name'], $max + 1),
                LogLevel::WARNING,
            );
        }
    }
}
