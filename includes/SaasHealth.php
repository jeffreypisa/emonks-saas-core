<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class SaasHealth
{
    public function boot(): void
    {
    }

    /** @return array<string,array<int,array<string,mixed>>> */
    public function report(): array
    {
        return [
            'core' => $this->coreChecks(),
            'config' => $this->configChecks(),
            'services' => $this->serviceChecks(),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function coreChecks(): array
    {
        global $wpdb;
        $membershipsTable = $wpdb->prefix . 'emonks_account_memberships';

        return [
            $this->row('Plugin geladen', true, 'Emonks SaaS core is actief.'),
            $this->row('Membership tabel', $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $membershipsTable)) === $membershipsTable, $membershipsTable),
            $this->row('Template loader', Plugin::instance()->get('template_loader') instanceof TemplateLoader, 'Theme overrides en plugin templates kunnen worden resolved.'),
            $this->row('Field library', ! empty(emonks_get_field_library()['fields'] ?? []), 'Core velden zijn beschikbaar.'),
            $this->row('Form templates', ! empty(emonks_get_form_templates()['forms'] ?? []), 'Core formulieren zijn beschikbaar.'),
            $this->row('REST API', true, '/wp-json/emonks/v1/health'),
            $this->row('ACF Bridge', emonks_acf_available(), emonks_acf_available() ? 'ACF is beschikbaar.' : 'ACF is optioneel en niet actief.'),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function configChecks(): array
    {
        $fields = emonks_get_field_library()['fields'] ?? [];
        $forms = emonks_get_form_templates()['forms'] ?? [];
        $services = emonks_get_service_schemas()['services'] ?? [];
        $fieldRefsOk = true;
        foreach ($forms as $form) {
            if (! is_array($form)) {
                continue;
            }
            foreach ((array) ($form['field_refs'] ?? []) as $ref) {
                $fieldKey = is_array($ref) ? sanitize_key((string) ($ref['field_key'] ?? '')) : sanitize_key((string) $ref);
                if ($fieldKey !== '' && ! isset($fields[$fieldKey])) {
                    $fieldRefsOk = false;
                }
            }
        }

        return [
            $this->row('Services ingericht', ! empty($services), empty($services) ? 'Nog geen services aangemaakt. Dit is normaal bij een verse installatie.' : count($services) . ' service(s) gevonden.', empty($services) ? 'info' : 'ok'),
            $this->row('Velden', ! empty($fields), count((array) $fields) . ' veld(en) in de bibliotheek.'),
            $this->row('Formulieren', ! empty($forms), count((array) $forms) . ' formulier(en) beschikbaar.'),
            $this->row('Field refs', $fieldRefsOk, $fieldRefsOk ? 'Alle formulierverwijzingen kloppen.' : 'Een of meer formulieren verwijzen naar ontbrekende velden.'),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function serviceChecks(): array
    {
        $services = emonks_get_service_schemas()['services'] ?? [];
        $rows = [];
        foreach ($services as $key => $schema) {
            if (! is_array($schema)) {
                continue;
            }
            $serviceKey = sanitize_key((string) $key);
            $usages = is_array($schema['form_usages'] ?? null) ? $schema['form_usages'] : [];
            $hasCreate = $this->hasUsage($usages, 'workspace_create');
            $hasEdit = $this->hasUsage($usages, 'workspace_edit');
            $template = sanitize_text_field((string) ($schema['render_hints']['template'] ?? ''));
            $acfGroup = sanitize_text_field((string) ($schema['acf_group_key_default'] ?? ''));
            $rows[] = $this->row($serviceKey, $hasCreate && $hasEdit, sprintf('Forms: %d, create: %s, edit: %s, template: %s, ACF: %s', count($usages), $hasCreate ? 'ja' : 'nee', $hasEdit ? 'ja' : 'nee', $template !== '' ? $template : 'niet ingesteld', $acfGroup !== '' ? $acfGroup : 'niet ingesteld'), $hasCreate && $hasEdit ? 'ok' : 'info');
        }

        return $rows;
    }

    /** @param array<int,mixed> $usages */
    private function hasUsage(array $usages, string $context): bool
    {
        foreach ($usages as $usage) {
            if (is_array($usage) && sanitize_key((string) ($usage['context'] ?? '')) === $context && ! empty($usage['enabled'])) {
                return true;
            }
        }
        return false;
    }

    private function row(string $label, bool $passed, string $details = '', string $status = ''): array
    {
        return [
            'label' => $label,
            'passed' => $passed,
            'status' => $status !== '' ? $status : ($passed ? 'ok' : 'warning'),
            'details' => $details,
        ];
    }
}
