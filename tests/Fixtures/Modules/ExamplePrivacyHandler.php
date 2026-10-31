<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Privacy\PrivacyHandler;

class ExamplePrivacyHandler implements PrivacyHandler
{
    /** @var array<int, array<string, mixed>> */
    public static array $calls = [];

    public function describe(): string
    {
        return 'Example note per user.';
    }

    public function export(int $cid): array
    {
        self::$calls[] = ['action' => 'export', 'cid' => $cid];

        return ['note' => "example note for {$cid}"];
    }

    public function pseudonymise(int $cid): void
    {
        self::$calls[] = ['action' => 'pseudonymise', 'cid' => $cid];
    }

    public function delete(int $cid): void
    {
        self::$calls[] = ['action' => 'delete', 'cid' => $cid];
    }
}
