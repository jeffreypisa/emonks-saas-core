<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

interface ModuleLifecycleInterface
{
    public function registerRoutes(): void;

    public function registerRest(): void;

    public function registerCapabilities(): void;

    public function registerDashboardCards(): void;
}

