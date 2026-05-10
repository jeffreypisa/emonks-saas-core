<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

interface BillingProviderInterface
{
    /** @return array<string,mixed> */
    public function createCheckoutSession(int $userId, string $plan, string $successUrl, string $cancelUrl, string $cycle = 'monthly'): array;

    /** @return array<string,mixed> */
    public function createCustomerPortalSession(int $userId, string $returnUrl): array;

    /** @return array<string,mixed> */
    public function changeSubscriptionPlan(int $userId, string $targetPlan, bool $immediate = true, string $cycle = 'monthly'): array;
}

