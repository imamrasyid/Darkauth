<?php

namespace Darkauth\Security;

use Darkauth\Core\UserInterface;

/**
 * Class RiskEngine
 * 
 * Analyzes authentication attempts for risk factors.
 */
class RiskEngine
{
    /**
     * Calculate risk score for a login attempt.
     * 0 = Safe, 100 = Critical
     *
     * @param UserInterface|null $user
     * @param array $context [ip, user_agent, last_login_ip, etc]
     * @return int
     */
    public function calculateScore(?UserInterface $user, array $context): int
    {
        $score = 0;

        // 1. New IP Detection
        if (isset($context['ip'], $context['last_ip']) && $context['ip'] !== $context['last_ip']) {
            $score += 30;
        }

        // 2. New User Agent Detection
        if (isset($context['ua'], $context['last_ua']) && $context['ua'] !== $context['last_ua']) {
            $score += 20;
        }

        // 3. Abnormal Timing (e.g., login at 3 AM)
        $hour = (int) date('H');
        if ($hour >= 0 && $hour <= 5) {
            $score += 15;
        }

        // 4. Multiple failures in context
        if (isset($context['recent_failures']) && $context['recent_failures'] > 3) {
            $score += 40;
        }

        return min(100, $score);
    }

    /**
     * Determine if a challenge (MFA/Captcha) is required based on risk.
     *
     * @param int $score
     * @param int $threshold
     * @return bool
     */
    public function shouldChallenge(int $score, int $threshold = 50): bool
    {
        return $score >= $threshold;
    }
}
