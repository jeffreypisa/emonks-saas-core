<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Branding
{
    public function boot(): void
    {
        add_action('init', [$this, 'normalizeRoleLabels'], 5);
    }

    public function normalizeRoleLabels(): void
    {
        if (! function_exists('wp_roles')) {
            return;
        }

        $roles = wp_roles();
        if (! $roles instanceof \WP_Roles) {
            return;
        }

        $roleKey = 'emonks_customer';
        if (! isset($roles->roles[$roleKey])) {
            return;
        }

        $desired = 'Emonks Customer';
        $current = (string) ($roles->roles[$roleKey]['name'] ?? '');
        if ($current === $desired) {
            return;
        }

        $caps = (array) ($roles->roles[$roleKey]['capabilities'] ?? ['read' => true]);
        remove_role($roleKey);
        add_role($roleKey, $desired, $caps);
    }
}

