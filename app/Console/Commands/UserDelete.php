<?php

namespace App\Console\Commands;

use App\Helpers\TrainingStatus;
use App\Models\Training;
use App\Models\User;
use App\Modules\ModuleRegistry;
use App\Notifications\TrainingClosedNotification;
use ControlCenter\ModuleApi\Privacy\PrivacyHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe or pseudonymize a user';

    public function __construct(private ModuleRegistry $modules)
    {
        parent::__construct();
    }

    /**
     * Run the given privacy action against every *installed* module's privacy
     * handlers (enabled or not — a disabled module's user data still exists).
     *
     * Each handler runs in isolation: a failing module is reported and skipped
     * so it cannot abort the deletion of the remaining modules' data or core.
     *
     * @param  'pseudonymise'|'delete'  $action
     */
    /**
     * Invoke the given privacy action on every installed module's handler,
     * isolating each. Returns false if any handler failed, so the caller can
     * surface incomplete cleanup rather than report unconditional success.
     */
    private function runModulePrivacyHandlers(string $action, int $cid): bool
    {
        $allSucceeded = true;

        $plan = $this->modules->privacyCleanupPlan();

        // A module that could not be initialised might still hold this user's
        // data. Treat that as incomplete cleanup rather than silently assuming
        // there is nothing to remove.
        foreach ($plan['failed'] as $slug => $reason) {
            $this->error($reason);
            $allSucceeded = false;
        }

        foreach ($plan['handlers'] as $slug => $handlerClasses) {
            foreach ($handlerClasses as $handlerClass) {
                try {
                    /** @var PrivacyHandler $handler */
                    $handler = app($handlerClass);
                    $handler->{$action}($cid);

                    $this->comment("Module [{$slug}] handled {$action} for {$cid}.");
                } catch (Throwable $e) {
                    report($e);
                    $this->error("Module [{$slug}] failed to {$action} data for {$cid}: {$e->getMessage()}");
                    $allSucceeded = false;
                }
            }
        }

        return $allSucceeded;
    }

    /**
     * Close trainings of user to remove them from queue if applicable
     */
    private function closeUserTrainings($user)
    {

        $trainings = Training::where('user_id', $user->id)->where('status', '>=', TrainingStatus::IN_QUEUE)->get();
        foreach ($trainings as $training) {
            // Training should be closed
            $training->updateStatus(TrainingStatus::CLOSED_BY_SYSTEM);

            // Detach mentors
            foreach ($training->mentors as $mentor) {
                $training->mentors()->detach($mentor);
            }

            // Notify the student
            $training->closed_reason = 'Closed due to data deletion request.';
            $training->save();
            $training->user->notify(new TrainingClosedNotification($training, TrainingStatus::CLOSED_BY_SYSTEM, 'Closed due to data deletion request.'));
        }
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cid = $this->ask("What is the user's CID?");

        if ($user = User::find($cid)) {
            $userInfo = $user->name . ' (' . $cid . ')';

            $this->comment($userInfo . ' found in records of Control Center!');

            $choices = [
                'PSEUDONYMISE -> Used for GDPR deletion requests from user directly to us',
                'PERMANENTLY DELETE -> Deletes all data related to this user, only applicable if user is banned from VATSIM and will never return',
            ];
            $choice = $this->choice('Do you want to PSEUDONYMISE or DELETE the user?', $choices);

            // PSEUDONYMISE
            if ($choice == $choices[0]) {
                $confirmed = $this->confirm('Are you sure you want to PSEUDONYMISE ' . $userInfo . '?');
                if ($confirmed) {
                    // Remove things from Control Center
                    $this->closeUserTrainings($user);
                    $user->roleAssignments()->get()->each->delete();

                    $user->email = 'void@void.void';
                    $user->first_name = 'Anonymous';
                    $user->last_name = 'Anonymous';
                    $user->region = 'XXX';
                    $user->division = null;
                    $user->subdivision = null;
                    $user->access_token = null;
                    $user->refresh_token = null;
                    $user->token_expires = null;
                    $user->remember_token = null;
                    $user->setting_workmail_address = null;
                    $user->setting_workmail_expire = null;
                    $user->setting_notify_newreport = false;
                    $user->setting_notify_newreq = false;
                    $user->setting_notify_closedreq = false;
                    $user->setting_notify_newexamreport = false;

                    $user->save();

                    // Pseudonymise the user's data held by every installed module.
                    $privacyOk = $this->runModulePrivacyHandlers('pseudonymise', (int) $cid);

                    $this->comment($userInfo . ' has been pseudoymised in Control Center. This will be reverted IF they log into CC again.');

                    if (! $privacyOk) {
                        $this->error('One or more modules could not pseudonymise their data (see errors above). Cleanup is incomplete; resolve the errors and run the command again.');

                        return self::FAILURE;
                    }
                }
                // PERMANENTLY DELETE
            } elseif ($choice == $choices[1]) {
                $confirmed = $this->confirm('Are you sure you want to PERMANENTLY DELETE ' . $userInfo . '? This is IRREVERSIBLE!');
                if ($confirmed) {
                    // Permanently delete the user's data held by every installed
                    // module FIRST: a module with a restrictive foreign key to
                    // users would otherwise block $user->delete(), leaving the
                    // handlers to never run.
                    $privacyOk = $this->runModulePrivacyHandlers('delete', (int) $cid);

                    // If a module could not remove its data (or could not be
                    // initialised at all), do NOT delete the core user: doing so
                    // would strand that module's personal data with no user to
                    // tie it back to. Keep the user so the operator can resolve
                    // the failure and retry, and signal incompleteness.
                    if (! $privacyOk) {
                        $this->error($userInfo . ' was NOT deleted: one or more modules could not remove their data (see errors above). Resolve the errors and run the command again.');

                        return self::FAILURE;
                    }

                    // Remove notification logs as it's not cascaded due to morph data structure
                    DB::table('notifications')->where('notifiable_type', 'App\Models\User')->where('notifiable_id', $cid)->delete();

                    // Remove things from Control Center
                    try {
                        $user->delete();
                    } catch (Throwable $e) {
                        report($e);
                        $this->error('Failed to delete the core user record for ' . $userInfo . ': ' . $e->getMessage());

                        return self::FAILURE;
                    }

                    $this->comment('All data related to ' . $userInfo . ' has been permanently deleted from Control Center!');
                }
            }

            return self::SUCCESS;
        }

        // An unknown CID is not an error condition (it matches main's exit 0);
        // only genuinely incomplete deletions below return a failing code.
        $this->error('No records of ' . $cid . ' was found.');

        return self::SUCCESS;
    }
}
