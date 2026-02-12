<?php

namespace App\Services;

class VhfAirbandCheckerService
{
    /**
     * Validate that freq is in 25kHz or 8.33kHz spacing
     * and that it is between 118.000 and 136.990 MHz.
     */
    public function check(string $freq): bool
    {
        if (! str_contains($freq, '.')) {
            return false;
        }

        [$main, $sub] = explode('.', $freq, 2);

        if (strlen($main) !== 3 || strlen($sub) !== 3) {
            return false;
        }

        if (! ctype_digit($main) || ! ctype_digit($sub)) {
            return false;
        }

        $main = (int) $main;
        $sub = (int) $sub;

        return $this->validateMain($main) && $this->validateSub($sub);
    }

    /**
     * Validate that the input is between 118.000 and 136.990 MHz.
     */
    protected function validateMain(int $main): bool
    {
        return $main >= 118 && $main < 137;
    }

    /**
     * Validate sub frequency.
     */
    protected function validateSub(int $sub): bool
    {
        return in_array($sub % 25, [0, 5, 10, 15], true);
    }
}
