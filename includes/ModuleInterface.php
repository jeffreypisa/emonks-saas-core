<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

interface ModuleInterface
{
    public function key(): string;

    public function boot(): void;

    public function isEnabledByDefault(): bool;
}

