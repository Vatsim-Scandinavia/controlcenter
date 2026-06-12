<?php

namespace App\Services;

use anlutro\LaravelSettings\Facade as Setting;
use App\Helpers\TrainingStatus;
use App\Helpers\VatsimRating;
use App\Http\Controllers\ActivityLogController;
use App\Models\Booking;
use App\Models\Position;
use App\Models\Sweatbook;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Shared domain logic for ATC and sweatbox bookings.
 */
class BookingService
{
    /**
     * Get all active bookings of the given model, sorted by start time.
     *
     * @param  class-string<Booking|Sweatbook>  $model
     * @return \Illuminate\Support\Collection<int, Booking|Sweatbook> Active bookings
     */
    public function getActiveBookings(string $model = Booking::class)
    {
        return $model::query()
            ->when($model === Booking::class, fn ($query) => $query->where('deleted', false))
            ->with('user', 'position')
            ->orderBy('time_start')
            ->get();
    }

    /**
     * Get the bookable positions for a user.
     *
     * @return \Illuminate\Support\Collection<int, Position> Bookable positions
     */
    public function getBookablePositions(User $user)
    {
        // Moderators and above can book any position
        if ($user->hasPermission('bypass-booking-restrictions')) {
            return Position::all();
        }

        // Users with a rating of S1 or above can book positions up to their rating
        $positions = new Collection();

        if ($user->rating >= VatsimRating::S1->value) {
            if (Setting::get('atcActivityBasedOnTotalHours')) {
                $positions = Position::where('rating', '<=', $user->rating)->get();
            } else {
                $activeAreas = $user->atcActivity->pluck('area_id');
                $positionsInAreas = Position::whereIn('area_id', $activeAreas)->where('rating', '<=', $user->rating)->get();
                $positions = $positions->merge($positionsInAreas);
            }
        }

        if ($user->getActiveTraining(TrainingStatus::PRE_TRAINING->value)) {
            $positions = $positions->merge(
                $user->getActiveTraining()->area->positions->where('rating', '<=', $user->getActiveTraining()->getHighestVatsimRating()?->vatsim_rating)
            );
        }

        return $positions;
    }

    /**
     * Parse the validated date and time inputs into the booking period.
     *
     * @return array{0: Carbon, 1: Carbon} Start and end of the booking
     */
    public function parsePeriod(string $date, string $startAt, string $endAt): array
    {
        $bookingDate = Carbon::createFromFormat('d/m/Y', $date);

        return [
            Carbon::createFromFormat('H:i', $startAt)->setDateFrom($bookingDate),
            Carbon::createFromFormat('H:i', $endAt)->setDateFrom($bookingDate),
        ];
    }

    /**
     * Roll the end time to the next day for bookings crossing midnight.
     */
    public function adjustForOvernight(Carbon $startAt, Carbon $endAt): void
    {
        if ($startAt->diffInMinutes($endAt, false) < 0) {
            $endAt->addDay();
        }
    }

    public function isStartInPast(Carbon $startAt): bool
    {
        return $startAt->diffInMinutes(Carbon::now(), false) > 0;
    }

    /**
     * Check if another booking overlaps the given booking's position and period.
     */
    public function bookingConflictExists(Booking $booking): bool
    {
        return Booking::where('deleted', false)->where($this->overlapConstraints($booking))->exists();
    }

    /**
     * Check if another sweatbox booking overlaps the given booking's position and period.
     */
    public function sweatbookConflictExists(Sweatbook $booking): bool
    {
        return Sweatbook::where($this->overlapConstraints($booking))->exists();
    }

    /**
     * Query constraints matching bookings that overlap the given booking's
     * position and period, excluding adjacent bookings and the booking itself.
     */
    private function overlapConstraints(Booking|Sweatbook $booking): \Closure
    {
        return function ($query) use ($booking) {
            $query->where(function ($query) use ($booking) {
                $query->whereBetween('time_start', [$booking->time_start, $booking->time_end])
                    ->where('time_end', '!=', $booking->time_start)
                    ->where('time_start', '!=', $booking->time_end)
                    ->where('position_id', $booking->position_id)
                    ->where('id', '!=', $booking->id);
            })->orWhere(function ($query) use ($booking) {
                $query->whereBetween('time_end', [$booking->time_start, $booking->time_end])
                    ->where('time_end', '!=', $booking->time_start)
                    ->where('time_start', '!=', $booking->time_end)
                    ->where('position_id', $booking->position_id)
                    ->where('id', '!=', $booking->id);
            });
        };
    }

    /**
     * Check if the booking must carry a training tag because the booking user
     * lacks the rating or endorsement required for the position.
     */
    public function shouldForceTrainingTag(Booking $booking, Position $position, User $bookingUser): bool
    {
        if ($bookingUser->hasPermission('bypass-booking-restrictions')) {
            return false;
        }

        if ($booking->position->rating->value > $bookingUser->rating) {
            return true;
        }

        return $position->requiredRating && ! $bookingUser->hasEndorsementRating($position->requiredRating);
    }

    /**
     * Apply the requested tag to the booking's flags.
     *
     * @return string The booking type to report to the VATSIM booking API
     */
    public function applyTagToBooking(Booking $booking, ?int $tag): string
    {
        switch ($tag) {
            case 1:
                $booking->training = 1;
                $booking->exam = 0;
                $booking->event = 0;

                return 'training';
            case 2:
                $booking->training = 0;
                $booking->exam = 1;
                $booking->event = 0;

                return 'exam';
            case 3:
                $booking->training = 0;
                $booking->exam = 0;
                $booking->event = 1;

                return 'event';
            default:
                $booking->exam = 0;
                $booking->event = 0;

                return 'booking';
        }
    }

    public function logBookingCreated(Booking|Sweatbook $booking, bool $bulk = false): void
    {
        $kind = $bulk ? 'booking BULK' : $this->bookingKind($booking);
        ActivityLogController::info('BOOKING', 'Created ' . $kind . ' ' . $this->describeBooking($booking));
    }

    public function logBookingUpdated(Booking|Sweatbook $booking): void
    {
        ActivityLogController::info('BOOKING', 'Updated ' . $this->bookingKind($booking) . ' ' . $this->describeBooking($booking));
    }

    public function logBookingDeleted(Booking|Sweatbook $booking): void
    {
        ActivityLogController::warning('BOOKING', 'Deleted ' . $this->bookingKind($booking) . ' ' . $this->describeBooking($booking));
    }

    private function bookingKind(Booking|Sweatbook $booking): string
    {
        return $booking instanceof Sweatbook ? 'sweatbox' : 'booking';
    }

    private function describeBooking(Booking|Sweatbook $booking): string
    {
        return 'booking ' . $booking->id .
            ' ― from ' . Carbon::parse($booking->time_start)->toEuropeanDateTime() .
            ' → ' . Carbon::parse($booking->time_end)->toEuropeanDateTime() .
            ' ― Position: ' . $booking->position->callsign;
    }
}
