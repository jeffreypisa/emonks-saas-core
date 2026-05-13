<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class ModuleRegistry
{
    /** @var array<string,ModuleInterface> */
    private array $modules = [];

    public function boot(): void
    {
        do_action('emonks_register_modules', $this);
        $this->bootEnabledModules();
    }

    public function register(ModuleInterface $module): void
    {
        $key = sanitize_key($module->key());
        if ($key === '') {
            return;
        }

        $this->modules[$key] = $module;
    }

    public function isEnabled(string $key): bool
    {
        $key = sanitize_key($key);
        if ($key === '' || ! isset($this->modules[$key])) {
            return false;
        }

        $enabled = emonks_get_setting('modules.enabled', []);
        if (! is_array($enabled) || ! array_key_exists($key, $enabled)) {
            return $this->modules[$key]->isEnabledByDefault();
        }

        return (bool) $enabled[$key];
    }

    /** @return array<string,ModuleInterface> */
    public function all(): array
    {
        return $this->modules;
    }

    /** @return array<int,string> */
    public function enabledKeys(): array
    {
        $enabled = [];
        foreach (array_keys($this->modules) as $key) {
            if ($this->isEnabled($key)) {
                $enabled[] = $key;
            }
        }

        return $enabled;
    }

    private function bootEnabledModules(): void
    {
        foreach ($this->modules as $key => $module) {
            if (! $this->isEnabled($key)) {
                continue;
            }

            if ($module instanceof ModuleLifecycleInterface) {
                $module->registerCapabilities();
                $module->registerRoutes();
                $module->registerRest();
                $module->registerDashboardCards();
            }

            $module->boot();
        }
    }
}
