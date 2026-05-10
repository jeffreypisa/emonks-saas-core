<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class StripeBillingProvider implements BillingProviderInterface
{
    public function __construct(private Stripe $stripe)
    {
    }

    public function createCheckoutSession(int $userId, string $plan, string $successUrl, string $cancelUrl, string $cycle = 'monthly'): array
    {
        return $this->stripe->createCheckoutSession($userId, $plan, $successUrl, $cancelUrl, $cycle);
    }

    public function createCustomerPortalSession(int $userId, string $returnUrl): array
    {
        return $this->stripe->createCustomerPortalSession($userId, $returnUrl);
    }

    public function changeSubscriptionPlan(int $userId, string $targetPlan, bool $immediate = true, string $cycle = 'monthly'): array
    {
        return $this->stripe->changeSubscriptionPlan($userId, $targetPlan, $immediate, $cycle);
    }
}

