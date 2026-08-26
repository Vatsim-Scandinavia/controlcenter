<?php

namespace Tests\Unit;

use App\Facades\DivisionApi;
use App\Services\DivisionApi\DivisionApiError;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DivisionApiErrorTest extends TestCase
{
    private function response(int $status, string $body, array $headers = []): Response
    {
        return new Response(new GuzzleResponse($status, $headers, $body));
    }

    private function jsonResponse(int $status, array $payload): Response
    {
        return $this->response($status, json_encode($payload), ['Content-Type' => 'application/json']);
    }

    #[Test]
    public function it_uses_the_message_the_api_returned(): void
    {
        $detail = DivisionApiError::detail($this->jsonResponse(422, ['message' => 'Position is already endorsed']));

        $this->assertSame('Position is already endorsed', $detail);
    }

    #[Test]
    public function it_falls_back_to_the_body_when_the_response_is_not_json(): void
    {
        $detail = DivisionApiError::detail($this->response(502, '<html>Bad Gateway</html>'));

        // Without the fallback this would be an empty explanation of a total outage.
        $this->assertStringContainsString('502', $detail);
        $this->assertStringContainsString('Bad Gateway', $detail);
    }

    #[Test]
    public function it_falls_back_to_the_body_when_json_carries_no_message_string(): void
    {
        // A validation-error payload: 'message' is absent, and errors are nested.
        $detail = DivisionApiError::detail($this->jsonResponse(422, ['errors' => ['user_cid' => ['is required']]]));

        $this->assertStringContainsString('422', $detail);
        $this->assertStringContainsString('is required', $detail);
        $this->assertStringNotContainsString('Array', $detail);
    }

    #[Test]
    public function it_reports_an_empty_body_by_status(): void
    {
        $this->assertSame('HTTP 429 with an empty body.', DivisionApiError::detail($this->response(429, '')));
    }

    #[Test]
    public function it_truncates_a_long_body(): void
    {
        $detail = DivisionApiError::detail($this->response(500, str_repeat('x', 5000)));

        $this->assertLessThan(300, strlen($detail));
    }

    #[Test]
    public function it_handles_a_missing_response(): void
    {
        // Adapters return null when a call is not made at all, e.g. NoOpAdapter.
        $this->assertSame('no response received.', DivisionApiError::detail(null));
    }

    #[Test]
    public function it_prefixes_the_standard_sentence_with_the_api_name(): void
    {
        DivisionApi::shouldReceive('getName')->andReturn('VATEUD');

        $message = DivisionApiError::message($this->jsonResponse(422, ['message' => 'nope']));

        $this->assertSame('Request failed due to error in VATEUD API: nope', $message);
    }
}
