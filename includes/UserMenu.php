<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class UserMenu
{
    private const MARKER_URL = '#emonks-user-menu';
    private const MARKER_CLASS = 'emonks-user-menu';

    public function boot(): void
    {
        add_action('admin_head-nav-menus.php', [$this, 'registerMenusMetabox']);
        add_action('admin_post_emonks_user_menu_add_to_menu', [$this, 'handleDirectAddToMenu']);
        add_filter('wp_get_nav_menu_items', [$this, 'injectUserMenuItems'], 20, 3);
        add_filter('wp_nav_menu_objects', [$this, 'injectUserMenuObjects'], 20, 2);
    }

    public function defaults(): array
    {
        return [
            'enabled' => false,
            'show_avatar' => true,
            'name_mode' => 'full',
            'link_type' => 'link',
            'button_style' => 'btn-primary',
            'links' => [
                'account' => true,
                'workspaces' => true,
                'settings' => true,
                'billing' => true,
                'logout' => true,
            ],
        ];
    }

    public function getSettings(): array
    {
        $raw = emonks_get_setting('user_menu', []);
        $settings = array_replace_recursive($this->defaults(), is_array($raw) ? $raw : []);
        $settings['enabled'] = ! empty($settings['enabled']);
        $settings['show_avatar'] = ! empty($settings['show_avatar']);
        $settings['name_mode'] = in_array((string) $settings['name_mode'], ['full', 'first', 'display'], true) ? (string) $settings['name_mode'] : 'full';
        $settings['link_type'] = in_array((string) $settings['link_type'], ['link', 'button'], true) ? (string) $settings['link_type'] : 'link';
        $allowedStyles = ['btn-light', 'btn-outline-contrast', 'btn-dark', 'btn-primary', 'btn-gradient-light', 'btn-gradient-dark'];
        $settings['button_style'] = in_array((string) $settings['button_style'], $allowedStyles, true) ? (string) $settings['button_style'] : 'btn-primary';
        $settings['links'] = is_array($settings['links']) ? $settings['links'] : [];
        return $settings;
    }

    public function isEnabled(): bool
    {
        return ! empty($this->getSettings()['enabled']);
    }

    /** @return array<int,\WP_Post> */
    public function injectUserMenuItems(array $items, \WP_Term $menu, array $args): array
    {
        return $this->transformMenuItems($items);
    }

    /** @return array<int,\WP_Post> */
    public function injectUserMenuObjects(array $items): array
    {
        return $this->transformMenuItems($items);
    }

    public function registerMenusMetabox(): void
    {
        add_meta_box(
            'emonks-user-menu',
            'Emonks Profielmenu',
            [$this, 'renderMenusMetabox'],
            'nav-menus',
            'side',
            'default'
        );
    }

    public function renderMenusMetabox(): void
    {
        global $_nav_menu_placeholder, $nav_menu_selected_id;
        $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? (int) $_nav_menu_placeholder - 1 : -1;

        echo '<div class="customlinkdiv" id="emonks-user-menu-metabox">';
        echo '<input type="hidden" value="custom" name="menu-item[' . esc_attr((string) $_nav_menu_placeholder) . '][menu-item-type]" />';
        echo '<p class="howto">Dit item wordt automatisch dynamisch ingevuld voor de ingelogde gebruiker.</p>';
        echo '<p class="wp-clearfix" style="display:none;">';
        echo '<label for="emonks-custom-menu-item-url" class="screen-reader-text">URL</label>';
        echo '<input id="emonks-custom-menu-item-url" name="menu-item[' . esc_attr((string) $_nav_menu_placeholder) . '][menu-item-url]" type="text" value="' . esc_attr(self::MARKER_URL) . '" class="code menu-item-textbox form-required" />';
        echo '</p>';
        echo '<p class="wp-clearfix" style="display:none;">';
        echo '<label for="emonks-custom-menu-item-name" class="screen-reader-text">Link Text</label>';
        echo '<input id="emonks-custom-menu-item-name" name="menu-item[' . esc_attr((string) $_nav_menu_placeholder) . '][menu-item-title]" type="text" value="Account" class="regular-text menu-item-textbox" />';
        echo '</p>';
        echo '<input type="hidden" value="' . esc_attr(self::MARKER_CLASS) . '" name="menu-item[' . esc_attr((string) $_nav_menu_placeholder) . '][menu-item-classes]" />';
        echo '<p>Voegt een dynamisch account menu toe voor de ingelogde gebruiker.</p>';
        echo '<p class="button-controls wp-clearfix">';
        echo '<span class="add-to-menu">';
        echo '<input id="submit-emonks-user-menu" name="add-custom-menu-item" type="submit" ' . disabled($nav_menu_selected_id, 0, false) . ' class="button submit-add-to-menu right" value="Aan menu toevoegen" />';
        echo '<span class="spinner"></span>';
        echo '</span>';
        echo '</p>';
        echo '</div>';
    }

    public function renderShortcode(): string
    {
        if (! $this->isEnabled() || ! is_user_logged_in()) {
            return '';
        }

        $links = $this->buildLinks();
        if (empty($links)) {
            return '';
        }

        $user = wp_get_current_user();
        $name = $this->resolveName($user);
        $avatar = $this->getSettings()['show_avatar'] ? get_avatar((int) $user->ID, 28, '', $name, ['class' => 'emonks-user-menu-avatar']) : '';
        $settings = $this->getSettings();
        $triggerClass = 'emonks-user-menu-trigger';
        if (($settings['link_type'] ?? 'link') === 'button') {
            $triggerClass .= ' btn btn-sm ' . sanitize_html_class((string) ($settings['button_style'] ?? 'btn-primary'));
        }

        ob_start();
        echo '<div class="emonks-user-menu-shortcode">';
        echo '<button type="button" class="' . esc_attr($triggerClass) . '" aria-expanded="false"><span class="emonks-user-menu-trigger-inner">';
        echo wp_kses_post($avatar);
        echo '<span class="emonks-user-menu-name">' . esc_html($name) . '</span>';
        echo '</span></button>';
        echo '<ul class="emonks-user-menu-dropdown" hidden>';
        foreach ($links as $link) {
            echo '<li><a href="' . esc_url($link['url']) . '">' . esc_html($link['label']) . '</a></li>';
        }
        echo '</ul></div>';
        return (string) ob_get_clean();
    }

    public function handleDirectAddToMenu(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Unauthorized', 403);
        }

        check_admin_referer('emonks_user_menu_add_to_menu', 'emonks_user_menu_add_nonce');
        $menuId = absint((string) ($_POST['menu_id'] ?? 0));
        if ($menuId <= 0) {
            emonks_flash_add('settings_warning', 'Geen geldig menu gekozen.');
            wp_safe_redirect(admin_url('admin.php?page=emonks-saas-user-menu'));
            exit;
        }

        $exists = false;
        $items = wp_get_nav_menu_items($menuId, ['post_status' => 'any']);
        if (is_array($items)) {
            foreach ($items as $item) {
                if (! $item instanceof \WP_Post) {
                    continue;
                }
                if ($this->isUserMenuItem($item)) {
                    $exists = true;
                    break;
                }
            }
        }

        if (! $exists) {
            $itemId = wp_update_nav_menu_item($menuId, 0, [
                'menu-item-title' => 'Account',
                'menu-item-url' => self::MARKER_URL,
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
                'menu-item-classes' => self::MARKER_CLASS,
            ]);
            if (! is_wp_error($itemId) && (int) $itemId > 0) {
                update_post_meta((int) $itemId, '_emonks_user_menu', '1');
            }
        }

        emonks_flash_add('settings_success', $exists ? 'Profielmenu stond al in dit menu.' : 'Profielmenu toegevoegd aan menu.');
        wp_safe_redirect(add_query_arg([
            'page' => 'emonks-saas-user-menu',
            'menu_id' => $menuId,
        ], admin_url('admin.php')));
        exit;
    }

    /** @param array<int,\WP_Post> $items
     *  @return array<int,\WP_Post>
     */
    private function transformMenuItems(array $items): array
    {
        if (empty($items)) {
            return $items;
        }

        $existingChildrenByParent = [];
        foreach ($items as $existingItem) {
            if (! $existingItem instanceof \WP_Post) {
                continue;
            }
            $classes = is_array($existingItem->classes ?? null) ? $existingItem->classes : [];
            if (in_array('emonks-user-menu-child', $classes, true)) {
                $parentId = (string) ($existingItem->menu_item_parent ?? '');
                $existingChildrenByParent[$parentId] = true;
            }
        }

        $out = [];
        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }

            if (! $this->isUserMenuItem($item)) {
                $out[] = $item;
                continue;
            }

            if (! $this->isEnabled() || ! is_user_logged_in()) {
                continue;
            }

            $user = wp_get_current_user();
            $item->title = $this->resolveName($user);
            $item->url = '#';
            $classes = is_array($item->classes ?? null) ? $item->classes : [];
            $classes[] = self::MARKER_CLASS;
            $item->classes = array_values(array_unique($classes));
            $item->link_stijl = $this->resolveSkeletorLinkStyle();
            $out[] = $item;
            if (! empty($existingChildrenByParent[(string) $item->ID])) {
                continue;
            }

            $order = (int) $item->menu_order;
            foreach ($this->buildLinks() as $idx => $link) {
                $child = clone $item;
                $child->ID = -1 * ((int) $item->ID * 100 + $idx + 1);
                $child->db_id = $child->ID;
                $child->title = $link['label'];
                $child->url = $link['url'];
                $child->menu_order = $order + $idx + 1;
                $child->menu_item_parent = (string) $item->ID;
                $child->classes = ['menu-item', 'menu-item-type-custom', 'menu-item-object-custom', 'emonks-user-menu-child'];
                $out[] = $child;
            }
        }

        return $out;
    }

    private function isUserMenuItem(\WP_Post $item): bool
    {
        $url = (string) ($item->url ?? '');
        if ($url === self::MARKER_URL) {
            return true;
        }

        $meta = get_post_meta((int) $item->ID, '_emonks_user_menu', true);
        if ((string) $meta === '1') {
            return true;
        }

        $classes = is_array($item->classes ?? null) ? $item->classes : [];
        return in_array(self::MARKER_CLASS, $classes, true);
    }

    private function resolveName(\WP_User $user): string
    {
        $settings = $this->getSettings();
        $mode = (string) $settings['name_mode'];
        $first = trim((string) get_user_meta((int) $user->ID, 'first_name', true));
        $last = trim((string) get_user_meta((int) $user->ID, 'last_name', true));
        $full = trim($first . ' ' . $last);
        $display = (string) $user->display_name;

        if ($mode === 'first' && $first !== '') {
            return $first;
        }
        if ($mode === 'display' && $display !== '') {
            return $display;
        }
        if ($full !== '') {
            return $full;
        }
        if ($first !== '') {
            return $first;
        }
        return $display !== '' ? $display : 'Account';
    }

    /** @return array<int,array{label:string,url:string}> */
    private function buildLinks(): array
    {
        $settings = $this->getSettings();
        $links = is_array($settings['links']) ? $settings['links'] : [];
        $routes = emonks_get_routes();
        $result = [];

        if (! empty($links['account'])) {
            $result[] = ['label' => 'Account', 'url' => emonks_get_account_url()];
        }
        if (! empty($links['workspaces'])) {
            $result[] = ['label' => 'Workspaces', 'url' => emonks_get_account_url('workspaces')];
        }
        if (! empty($links['settings'])) {
            $result[] = ['label' => 'Profiel', 'url' => emonks_get_account_url('settings')];
        }
        if (! empty($links['billing'])) {
            $result[] = ['label' => 'Billing', 'url' => emonks_get_account_url('billing')];
        }
        if (! empty($links['logout'])) {
            $result[] = ['label' => 'Uitloggen', 'url' => home_url('/' . $routes['logout'] . '/')];
        }

        return $result;
    }

    private function resolveSkeletorLinkStyle(): string
    {
        $settings = $this->getSettings();
        if (($settings['link_type'] ?? 'link') !== 'button') {
            return 'link';
        }

        $style = (string) ($settings['button_style'] ?? 'btn-primary');
        if ($style === 'btn-primary') {
            return 'btn-primary';
        }

        return 'btn-secondary';
    }
}
