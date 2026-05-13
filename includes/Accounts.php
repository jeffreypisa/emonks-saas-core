<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Accounts
{
    public function boot(): void
    {
        add_action('init', [self::class, 'registerPostType']);
        add_action('emonks_account_registered', [$this, 'ensurePersonalAccount'], 10, 1);
    }

    public static function registerPostType(): void
    {
        register_post_type('emonks_account', [
            'label' => 'Accounts',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title'],
            'has_archive' => false,
            'rewrite' => false,
            'map_meta_cap' => true,
        ]);
    }

    public function ensurePersonalAccount(int $userId): int
    {
        $existing = absint((string) get_user_meta($userId, 'emonks_primary_account_id', true));
        if ($existing > 0 && get_post_type($existing) === 'emonks_account') {
            return $existing;
        }

        $user = get_userdata($userId);
        if (! $user instanceof \WP_User) {
            return 0;
        }

        $accountId = wp_insert_post([
            'post_type' => 'emonks_account',
            'post_status' => 'publish',
            'post_title' => $user->display_name !== '' ? $user->display_name . ' Account' : $user->user_email . ' Account',
        ]);

        if (! is_numeric($accountId) || (int) $accountId <= 0) {
            return 0;
        }

        $accountId = (int) $accountId;
        update_post_meta($accountId, 'owner_user_id', $userId);
        update_user_meta($userId, 'emonks_primary_account_id', $accountId);

        $memberships = Plugin::instance()->get('memberships');
        if ($memberships instanceof Memberships) {
            $memberships->addMembership($accountId, $userId, 'account_owner');
        }

        return $accountId;
    }
}

