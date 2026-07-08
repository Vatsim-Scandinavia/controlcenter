<?php

namespace Tests\Feature;

use App\Helpers\VatsimRating;
use App\Models\Endorsement;
use App\Models\Rating;
use App\Models\User;
use App\Services\DivisionApi\Adapters\VATEUD;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VATEUDAdapterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function remove_examiner_calls_api_for_s3_rated_endorsement(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'ok'], 200),
        ]);

        $user = User::factory()->create();
        $requester = User::factory()->create();

        $rating = Rating::factory()->create(['vatsim_rating' => VatsimRating::S3->value]);
        $endorsement = Endorsement::factory()->create([
            'user_id' => $user->id,
            'type' => 'EXAMINER',
        ]);
        $endorsement->ratings()->attach($rating->id);
        $endorsement->load('ratings');

        $adapter = new VATEUD();
        $result = $adapter->removeExaminer($user, $endorsement, $requester->id);

        $this->assertNotFalse($result);
    }

    #[Test]
    public function remove_examiner_calls_api_for_c1_rated_endorsement(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'ok'], 200),
        ]);

        $user = User::factory()->create();
        $requester = User::factory()->create();

        // C1 (value 5) is higher than S3 (value 4), so the API should be called
        $rating = Rating::factory()->create(['vatsim_rating' => VatsimRating::C1->value]);
        $endorsement = Endorsement::factory()->create([
            'user_id' => $user->id,
            'type' => 'EXAMINER',
        ]);
        $endorsement->ratings()->attach($rating->id);
        $endorsement->load('ratings');

        $adapter = new VATEUD();
        $result = $adapter->removeExaminer($user, $endorsement, $requester->id);

        $this->assertNotFalse($result);
    }

    #[Test]
    public function remove_examiner_skips_api_for_s2_rated_endorsement(): void
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();

        $rating = Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value]);
        $endorsement = Endorsement::factory()->create([
            'user_id' => $user->id,
            'type' => 'EXAMINER',
        ]);
        $endorsement->ratings()->attach($rating->id);
        $endorsement->load('ratings');

        $adapter = new VATEUD();
        $result = $adapter->removeExaminer($user, $endorsement, $requester->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function assign_theory_exam_matches_exam_type_to_rating(): void
    {
        Http::fake([
            '*exams/assign' => Http::response(['status' => 'ok'], 200),
            '*exams' => Http::response(['data' => [
                ['id' => 99, 'flag_exam_type' => VatsimRating::S3->value - 1],
                ['id' => 42, 'flag_exam_type' => VatsimRating::S2->value - 1],
            ]], 200),
        ]);

        $user = User::factory()->create();
        $requester = User::factory()->create();
        $rating = Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value]);

        $adapter = new VATEUD();
        $result = $adapter->assignTheoryExam($user, $rating, $requester->id);

        $this->assertNotNull($result);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'exams/assign')
            && $request['exam_id'] === 42);
    }

    #[Test]
    public function user_has_passed_theory_exam_returns_true_for_matching_passed_exam(): void
    {
        Http::fake([
            '*exams' => Http::response(['data' => ['results' => [
                ['flag_exam_type' => VatsimRating::S2->value - 1, 'passed' => true],
            ]]], 200),
        ]);

        $user = User::factory()->create();
        $rating = Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value]);

        $adapter = new VATEUD();

        $this->assertTrue($adapter->userHasPassedTheoryExam($user, $rating));
    }
}
