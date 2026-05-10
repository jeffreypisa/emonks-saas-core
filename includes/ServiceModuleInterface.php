<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

interface ServiceModuleInterface
{
    public function key(): string;

    /** @return array<string,mixed> */
    public function definition(): array;
}

