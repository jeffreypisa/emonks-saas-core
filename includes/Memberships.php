<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Memberships
{
    public const TABLE = 'emonks_account_memberships';

    public function boot(): void
    {
    }

    public static function install(): void
    {
        global $wpdb;
        $table = self::tableName();
        $charsetCollate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            account_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            role VARCHAR(32) NOT NULL DEFAULT 'account_member',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY account_user (account_id, user_id),
            KEY user_id (user_id),
            KEY account_id (account_id)
        ) {$charsetCollate};";

        dbDelta($sql);
    }

    public function addMembership(int $accountId, int $userId, string $role): bool
    {
        global $wpdb;
        $role = sanitize_key($role);
        if ($accountId <= 0 || $userId <= 0 || $role === '') {
            return false;
        }

        $now = current_time('mysql');
        $result = $wpdb->replace(
            self::tableName(),
            [
                'account_id' => $accountId,
                'user_id' => $userId,
                'role' => $role,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        return is_numeric($result);
    }

    /** @return array<int,int> */
    public function getUserAccountIds(int $userId): array
    {
        global $wpdb;
        if ($userId <= 0) {
            return [];
        }

        if (! $this->tableExists()) {
            return [];
        }

        $rows = $wpdb->get_col(
            $wpdb->prepare(
                'SELECT account_id FROM ' . self::tableName() . ' WHERE user_id = %d',
                $userId
            )
        );

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_unique(array_map(static fn($id) => absint((string) $id), $rows)));
    }

    public function userHasAccount(int $userId, int $accountId): bool
    {
        if (user_can($userId, 'manage_options')) {
            return true;
        }

        return in_array($accountId, $this->getUserAccountIds($userId), true);
    }

    public static function tableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    private function tableExists(): bool
    {
        global $wpdb;
        $table = self::tableName();
        $result = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        return is_string($result) && $result === $table;
    }
}
