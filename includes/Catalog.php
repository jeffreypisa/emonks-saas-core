<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Catalog
{
    public function boot(): void
    {
        add_action('init', [self::class, 'registerPlanPostType']);
        add_action('init', [self::class, 'registerTaxonomies']);
        add_action('add_meta_boxes', [$this, 'registerPlanMetaBox']);
        add_action('save_post_emonks_plan', [$this, 'savePlanMeta'], 10, 2);
        add_action('emonks_service_add_form_fields', [$this, 'renderServiceFeatureAddFields']);
        add_action('emonks_service_edit_form_fields', [$this, 'renderServiceFeatureEditFields']);
        add_action('created_emonks_service', [$this, 'saveServiceFeatureMeta']);
        add_action('edited_emonks_service', [$this, 'saveServiceFeatureMeta']);
    }

    public static function registerPlanPostType(): void
    {
        register_post_type('emonks_plan', [
            'label' => 'Plans',
            'labels' => [
                'name' => 'Plans',
                'singular_name' => 'Plan',
                'add_new_item' => 'Add New Plan',
                'edit_item' => 'Edit Plan',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'page-attributes'],
            'has_archive' => false,
            'rewrite' => false,
            'map_meta_cap' => true,
            'capability_type' => 'post',
        ]);
    }

    public static function registerTaxonomies(): void
    {
        register_taxonomy('emonks_feature', ['emonks_plan'], [
            'label' => 'Features',
            'labels' => [
                'name' => 'Features',
                'singular_name' => 'Feature',
            ],
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'hierarchical' => false,
            'rewrite' => false,
        ]);

        register_taxonomy('emonks_service', ['emonks_plan'], [
            'label' => 'Services',
            'labels' => [
                'name' => 'Services',
                'singular_name' => 'Service',
            ],
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'hierarchical' => false,
            'rewrite' => false,
        ]);
    }

    public function registerPlanMetaBox(): void
    {
        add_meta_box('emonks_plan_config', 'Plan Configuration', [$this, 'renderPlanMetaBox'], 'emonks_plan', 'normal', 'high');
    }

    public function renderPlanMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('emonks_plan_meta_save', 'emonks_plan_meta_nonce');
        $maxWorkspaces = (int) get_post_meta($post->ID, 'max_workspaces', true);
        $priceMonthly = (float) get_post_meta($post->ID, 'price_monthly', true);
        $priceYearly = (float) get_post_meta($post->ID, 'price_yearly', true);
        $currency = (string) get_post_meta($post->ID, 'currency', true);
        $priceConstant = (string) get_post_meta($post->ID, 'stripe_price_constant', true);
        $priceMonthlyId = (string) get_post_meta($post->ID, 'stripe_price_id_monthly', true);
        $priceYearlyId = (string) get_post_meta($post->ID, 'stripe_price_id_yearly', true);

        if ($currency === '') {
            $currency = 'EUR';
        }

        echo '<table class="form-table"><tbody>';
        echo '<tr><th><label for="emonks_plan_key">Plan Key</label></th><td><input id="emonks_plan_key" name="emonks_plan_key" class="regular-text" value="' . esc_attr($post->post_name) . '" /><p class="description">Stable technical slug, e.g. starter, plus, pro.</p></td></tr>';
        echo '<tr><th><label for="emonks_max_workspaces">Max Workspaces</label></th><td><input id="emonks_max_workspaces" name="emonks_max_workspaces" type="number" min="0" class="small-text" value="' . esc_attr((string) $maxWorkspaces) . '" /></td></tr>';
        echo '<tr><th><label for="emonks_price_monthly">Monthly Price</label></th><td><input id="emonks_price_monthly" name="emonks_price_monthly" type="number" min="0" step="0.01" class="small-text" value="' . esc_attr((string) $priceMonthly) . '" /></td></tr>';
        echo '<tr><th><label for="emonks_price_yearly">Yearly Price</label></th><td><input id="emonks_price_yearly" name="emonks_price_yearly" type="number" min="0" step="0.01" class="small-text" value="' . esc_attr((string) $priceYearly) . '" /></td></tr>';
        echo '<tr><th><label for="emonks_currency">Currency</label></th><td><input id="emonks_currency" name="emonks_currency" class="small-text" value="' . esc_attr($currency) . '" /></td></tr>';
        echo '<tr><th><label for="emonks_price_constant">Stripe Price Constant</label></th><td><input id="emonks_price_constant" name="emonks_price_constant" class="regular-text" value="' . esc_attr($priceConstant) . '" /></td></tr>';
        echo '<tr><th><label for="emonks_price_monthly_id">Stripe Monthly Price ID</label></th><td><input id="emonks_price_monthly_id" name="emonks_price_monthly_id" class="regular-text" value="' . esc_attr($priceMonthlyId) . '" /></td></tr>';
        echo '<tr><th><label for="emonks_price_yearly_id">Stripe Yearly Price ID</label></th><td><input id="emonks_price_yearly_id" name="emonks_price_yearly_id" class="regular-text" value="' . esc_attr($priceYearlyId) . '" /></td></tr>';
        echo '</tbody></table>';
        echo '<p><strong>Tip:</strong> Koppel Features en Services via de taxonomie-boxen rechts (zoals tags/categorieen).</p>';
    }

    public function savePlanMeta(int $postId, \WP_Post $post): void
    {
        if (! isset($_POST['emonks_plan_meta_nonce']) || ! wp_verify_nonce((string) $_POST['emonks_plan_meta_nonce'], 'emonks_plan_meta_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        update_post_meta($postId, 'max_workspaces', absint((string) ($_POST['emonks_max_workspaces'] ?? 0)));
        update_post_meta($postId, 'price_monthly', (float) ($_POST['emonks_price_monthly'] ?? 0));
        update_post_meta($postId, 'price_yearly', (float) ($_POST['emonks_price_yearly'] ?? 0));
        update_post_meta($postId, 'currency', strtoupper(sanitize_text_field((string) ($_POST['emonks_currency'] ?? 'EUR'))));
        update_post_meta($postId, 'stripe_price_constant', sanitize_text_field((string) ($_POST['emonks_price_constant'] ?? '')));
        update_post_meta($postId, 'stripe_price_id_monthly', sanitize_text_field((string) ($_POST['emonks_price_monthly_id'] ?? '')));
        update_post_meta($postId, 'stripe_price_id_yearly', sanitize_text_field((string) ($_POST['emonks_price_yearly_id'] ?? '')));

        $incomingKey = sanitize_title((string) ($_POST['emonks_plan_key'] ?? $post->post_name));
        if ($incomingKey !== '' && $incomingKey !== $post->post_name) {
            remove_action('save_post_emonks_plan', [$this, 'savePlanMeta'], 10);
            wp_update_post(['ID' => $postId, 'post_name' => $incomingKey]);
            add_action('save_post_emonks_plan', [$this, 'savePlanMeta'], 10, 2);
        }
    }

    public function renderServiceFeatureAddFields(): void
    {
        wp_nonce_field('emonks_service_feature_meta_save', 'emonks_service_feature_meta_nonce');
        $this->renderServiceFeatureChecklist([]);
    }

    public function renderServiceFeatureEditFields(\WP_Term $term): void
    {
        wp_nonce_field('emonks_service_feature_meta_save', 'emonks_service_feature_meta_nonce');
        $selected = get_term_meta($term->term_id, 'supported_features', true);
        if (! is_array($selected)) {
            $selected = [];
        }

        echo '<tr class="form-field"><th scope="row">Supported Features</th><td>';
        $this->renderServiceFeatureChecklist($selected, true);
        echo '</td></tr>';
    }

    public function saveServiceFeatureMeta(int $termId): void
    {
        if (! isset($_POST['emonks_service_feature_meta_nonce']) || ! wp_verify_nonce((string) $_POST['emonks_service_feature_meta_nonce'], 'emonks_service_feature_meta_save')) {
            return;
        }

        if (! current_user_can('manage_categories')) {
            return;
        }

        $raw = $_POST['supported_features'] ?? [];
        $values = is_array($raw) ? $raw : [];
        $supported = array_values(array_filter(array_map(static fn($v) => sanitize_key((string) $v), $values)));
        update_term_meta($termId, 'supported_features', $supported);
    }

    /** @param array<int,string> $selected */
    private function renderServiceFeatureChecklist(array $selected, bool $tableLayout = false): void
    {
        $terms = get_terms([
            'taxonomy' => 'emonks_feature',
            'hide_empty' => false,
        ]);
        if (! is_array($terms) || empty($terms)) {
            echo '<p class="description">No features yet. Create feature terms first in Emonks SaaS > Features.</p>';
            return;
        }

        if (! $tableLayout) {
            echo '<div class="form-field term-group"><label>Supported Features</label>';
        }

        foreach ($terms as $feature) {
            if (! $feature instanceof \WP_Term) {
                continue;
            }
            $slug = sanitize_key($feature->slug);
            $checked = in_array($slug, $selected, true) ? 'checked' : '';
            echo '<label style="display:block;margin:4px 0;"><input type="checkbox" name="supported_features[]" value="' . esc_attr($slug) . '" ' . $checked . ' /> ' . esc_html($feature->name) . ' <code>' . esc_html($slug) . '</code></label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        if (! $tableLayout) {
            echo '<p class="description">Technical boundary: only these features can be enabled for this service.</p></div>';
        }
    }
}
