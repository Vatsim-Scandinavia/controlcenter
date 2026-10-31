<?php

namespace Tests\Feature\Modules;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\Fixtures\Modules\ExamplePrivacyHandler;
use Tests\TestCase;

/**
 * The user:delete command must invoke the privacy handler of every *installed*
 * module — enabled or not, because a disabled module's user data still exists.
 */
class ModulePrivacyDeletionTest extends TestCase
{
    use InteractsWithModules, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ExamplePrivacyHandler::$calls = [];
    }

    private function choices(): array
    {
        return [
            'PSEUDONYMISE -> Used for GDPR deletion requests from user directly to us',
            'PERMANENTLY DELETE -> Deletes all data related to this user, only applicable if user is banned from VATSIM and will never return',
        ];
    }

    #[Test]
    public function pseudonymising_a_user_invokes_module_privacy_handlers(): void
    {
        // Registered but NOT enabled: the handler must still run.
        $this->bootModules([ExampleModule::class]);

        $user = User::factory()->create();
        $info = $user->name . ' (' . $user->id . ')';

        $this->artisan('user:delete')
            ->expectsQuestion("What is the user's CID?", $user->id)
            ->expectsChoice('Do you want to PSEUDONYMISE or DELETE the user?', $this->choices()[0], $this->choices())
            ->expectsConfirmation('Are you sure you want to PSEUDONYMISE ' . $info . '?', 'yes')
            ->assertExitCode(0);

        $this->assertContains(
            ['action' => 'pseudonymise', 'cid' => (int) $user->id],
            ExamplePrivacyHandler::$calls,
        );
    }

    #[Test]
    public function permanently_deleting_a_user_invokes_module_privacy_handlers(): void
    {
        $this->bootModules([ExampleModule::class]);

        $user = User::factory()->create();
        $info = $user->name . ' (' . $user->id . ')';
        $cid = (int) $user->id;

        $this->artisan('user:delete')
            ->expectsQuestion("What is the user's CID?", $user->id)
            ->expectsChoice('Do you want to PSEUDONYMISE or DELETE the user?', $this->choices()[1], $this->choices())
            ->expectsConfirmation('Are you sure you want to PERMANENTLY DELETE ' . $info . '? This is IRREVERSIBLE!', 'yes')
            ->assertExitCode(0);

        $this->assertContains(
            ['action' => 'delete', 'cid' => $cid],
            ExamplePrivacyHandler::$calls,
        );
    }
}
