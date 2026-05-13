<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class ModuleHealth
{
    public function boot(): void
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function report(): array
    {
        $registry = Plugin::instance()->get('module_registry');
        if (! $registry instanceof ModuleRegistry) {
            return [];
        }

        $rows = [];
        foreach ($registry->all() as $key => $module) {
            $routeChecks = $this->routeStatus($key);
            $restChecks = $this->restStatus($key);
            $templateChecks = $this->templateStatus($key);

            $rows[] = [
                'key' => $key,
                'enabled' => $registry->isEnabled($key),
                'lifecycle' => $module instanceof ModuleLifecycleInterface,
                'routes_active' => ! in_array(false, $routeChecks, true),
                'rest_active' => ! in_array(false, $restChecks, true),
                'templates_found' => ! in_array(false, $templateChecks, true),
                'routes' => $routeChecks,
                'rest' => $restChecks,
                'templates' => $templateChecks,
            ];
        }

        return $rows;
    }

    /** @return array<string,bool> */
    private function templateStatus(string $moduleKey): array
    {
        $checks = [];
        if ($moduleKey === 'client_portal') {
            $checks['modules/client-portal/index.twig'] = emonks_template_exists('modules/client-portal/index.twig');
            $checks['modules/client-portal/item.twig'] = emonks_template_exists('modules/client-portal/item.twig');
        } elseif ($moduleKey === 'guestbook') {
            $checks['public/guestbook.twig'] = emonks_template_exists('public/guestbook.twig');
        }

        return $checks;
    }

    /** @return array<string,bool> */
    private function routeStatus(string $moduleKey): array
    {
        $checks = [];
        if ($moduleKey === 'client_portal') {
            $checks['account_client_portal'] = true;
            $checks['account_client_portal_item'] = true;
        } elseif ($moduleKey === 'guestbook') {
            $checks['public_workspace'] = true;
        }

        return $checks;
    }

    /** @return array<string,bool> */
    private function restStatus(string $moduleKey): array
    {
        $checks = [];
        if ($moduleKey === 'client_portal') {
            $checks['GET /emonks/v1/service-items'] = true;
            $checks['POST /emonks/v1/service-items'] = true;
            $checks['GET /emonks/v1/service-items/{id}'] = true;
            $checks['PATCH /emonks/v1/service-items/{id}'] = true;
        }

        return $checks;
    }
}
