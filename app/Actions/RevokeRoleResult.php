<?php

namespace App\Actions;

/**
 * Outcome of RevokeRole: an error means nothing was revoked, a warning means
 * the role was revoked but something about it needs the actor's attention.
 */
final readonly class RevokeRoleResult
{
    public function __construct(
        public ?string $error = null,
        public ?string $warning = null,
    ) {}

    public static function failed(string $error): self
    {
        return new self(error: $error);
    }

    public static function revoked(?string $warning = null): self
    {
        return new self(warning: $warning);
    }

    public function succeeded(): bool
    {
        return $this->error === null;
    }
}
