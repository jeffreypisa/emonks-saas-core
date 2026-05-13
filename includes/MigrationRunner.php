<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class MigrationRunner
{
    private const OPTION_SCHEMA_VERSION = 'emonks_schema_version';
    private const OPTION_MIGRATION_HISTORY = 'emonks_migration_history';

    public function boot(): void
    {
        add_action('init', [$this, 'run'], 1);
    }

    public function run(): void
    {
        $migrations = $this->registry();
        if (empty($migrations)) {
            return;
        }

        $history = get_option(self::OPTION_MIGRATION_HISTORY, []);
        if (! is_array($history)) {
            $history = [];
        }

        $currentVersion = (int) get_option(self::OPTION_SCHEMA_VERSION, 0);
        $latestVersion = $currentVersion;

        foreach ($migrations as $version => $migration) {
            $version = (int) $version;
            if ($version <= 0 || ! is_callable($migration)) {
                continue;
            }

            $alreadyRan = ! empty($history[(string) $version]);
            if ($alreadyRan && $version <= $currentVersion) {
                continue;
            }

            call_user_func($migration);

            $history[(string) $version] = current_time('mysql');
            if ($version > $latestVersion) {
                $latestVersion = $version;
            }
        }

        update_option(self::OPTION_MIGRATION_HISTORY, $history, false);
        if ($latestVersion > $currentVersion) {
            update_option(self::OPTION_SCHEMA_VERSION, $latestVersion, false);
        }
    }

    /** @return array<int,callable> */
    private function registry(): array
    {
        $migrations = [
            1 => static function (): void {
                Memberships::install();
            },
        ];

        ksort($migrations);
        return apply_filters('emonks_migration_registry', $migrations);
    }
}
