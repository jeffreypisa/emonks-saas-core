<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class ServiceItems
{
    public function boot(): void
    {
        add_action('init', [self::class, 'registerPostType']);
    }

    public static function registerPostType(): void
    {
        register_post_type('emonks_service_item', [
            'label' => 'Service Items',
            'public' => false,
            'show_ui' => false,
            'supports' => ['title', 'author'],
            'has_archive' => false,
            'rewrite' => false,
            'map_meta_cap' => true,
        ]);
    }

    public function create(int $userId, int $accountId, int $workspaceId, string $title, string $status = 'open'): int
    {
        $title = sanitize_text_field($title);
        $status = emonks_normalize_status($status, 'open');
        if ($accountId <= 0 || $workspaceId <= 0 || $title === '') {
            return 0;
        }

        $id = wp_insert_post([
            'post_type' => 'emonks_service_item',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_author' => $userId,
        ]);

        if (! is_numeric($id) || (int) $id <= 0) {
            return 0;
        }

        $id = (int) $id;
        update_post_meta($id, 'account_id', $accountId);
        update_post_meta($id, 'workspace_id', $workspaceId);
        update_post_meta($id, 'status', $status);
        update_post_meta($id, 'module_key', sanitize_key((string) emonks_get_workspace_meta($workspaceId, 'module_key', 'client_portal')));
        update_post_meta($id, 'updated_at', current_time('mysql'));

        do_action('emonks_service_item_created', $id, $accountId, $workspaceId, $userId);
        return $id;
    }

    public function update(int $itemId, string $title = '', string $status = ''): bool
    {
        $itemId = absint((string) $itemId);
        if ($itemId <= 0 || get_post_type($itemId) !== 'emonks_service_item') {
            return false;
        }

        if ($title !== '') {
            wp_update_post(['ID' => $itemId, 'post_title' => sanitize_text_field($title)]);
        }

        if ($status !== '') {
            update_post_meta($itemId, 'status', emonks_normalize_status($status, 'open'));
        }

        update_post_meta($itemId, 'updated_at', current_time('mysql'));
        do_action('emonks_service_item_updated', $itemId);
        return true;
    }

    /** @return array<int,\WP_Post> */
    public function listByAccount(int $accountId, string $moduleKey = ''): array
    {
        if ($accountId <= 0) {
            return [];
        }

        $metaQuery = [
            [
                'key' => 'account_id',
                'value' => $accountId,
                'compare' => '=',
                'type' => 'NUMERIC',
            ],
        ];

        if ($moduleKey !== '') {
            $metaQuery[] = [
                'key' => 'module_key',
                'value' => sanitize_key($moduleKey),
                'compare' => '=',
            ];
        }

        $posts = get_posts([
            'post_type' => 'emonks_service_item',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => $metaQuery,
        ]);

        return is_array($posts) ? array_values(array_filter($posts, static fn($p) => $p instanceof \WP_Post)) : [];
    }
}
