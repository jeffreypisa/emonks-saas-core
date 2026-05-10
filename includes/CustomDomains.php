<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: CustomDomains
 * Purpose: Domain mapping architecture layer (prepared, not full DNS automation).
 * Responsibilities: Store mappings, validate ownership proof tokens, resolve host->workspace.
 * Example: mapDomain($workspaceId, 'guide.example.com').
 * Hooks: emonks_custom_domain_mapped.
 * Architecture Role: Foundation for future custom domain routing.
 */
final class CustomDomains
{
    public const OPTION_KEY = 'emonks_custom_domains';

    public function boot(): void
    {
    }

    /** @return array<string,mixed> */
    public function mapDomain(int $workspaceId, string $hostname): array
    {
        $hostname = $this->normalizeHostname($hostname);
        if ($hostname === '') {
            return ['ok' => false, 'message' => 'Invalid hostname'];
        }

        $domains = get_option(self::OPTION_KEY, []);
        if (! is_array($domains)) {
            $domains = [];
        }

        $token = wp_generate_password(24, false, false);
        $domains[$hostname] = [
            'workspace_id' => $workspaceId,
            'status' => 'pending_verification',
            'verification_token' => $token,
            'ssl_status' => 'pending',
            'updated_at' => current_time('mysql'),
        ];

        update_option(self::OPTION_KEY, $domains);
        do_action('emonks_custom_domain_mapped', $hostname, $workspaceId, $token);

        return ['ok' => true, 'hostname' => $hostname, 'verification_token' => $token];
    }

    /** @return array<string,mixed> */
    public function resolveByHost(string $host): array
    {
        $host = $this->normalizeHostname($host);
        $domains = get_option(self::OPTION_KEY, []);
        if (! is_array($domains)) {
            return [];
        }

        return $domains[$host] ?? [];
    }

    private function normalizeHostname(string $hostname): string
    {
        $hostname = strtolower(trim($hostname));
        $hostname = preg_replace('#^https?://#', '', $hostname) ?? '';
        $hostname = rtrim($hostname, '/');
        $hostname = sanitize_text_field($hostname);

        return preg_match('/^[a-z0-9.-]+$/', $hostname) ? $hostname : '';
    }
}
