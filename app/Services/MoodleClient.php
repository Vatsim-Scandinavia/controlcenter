<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MoodleClient
{
    public function isConfigured(): bool
    {
        return (bool) config('services.moodle.enabled')
            && filled(config('services.moodle.url'))
            && filled(config('services.moodle.token'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findUsersByUsername(string $username): array
    {
        $result = $this->call('core_user_get_users_by_field', [
            'field' => 'username',
            'values' => [$username],
        ]);

        return is_array($result) ? array_values($result) : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findUserById(int $moodleUserId): ?array
    {
        $result = $this->call('core_user_get_users_by_field', [
            'field' => 'id',
            'values' => [(string) $moodleUserId],
        ]);

        return isset($result[0]) && is_array($result[0]) ? $result[0] : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchUsers(string $query): array
    {
        $result = $this->call('core_user_search_identity', ['query' => $query]);

        return collect($result['list'] ?? [])->map(function (array $user): array {
            $extraFields = collect($user['extrafields'] ?? [])->mapWithKeys(
                fn (array $field): array => [$field['name'] => $field['value']]
            );

            return [
                'id' => (int) $user['id'],
                'fullname' => (string) $user['fullname'],
                'username' => $extraFields->get('username'),
                'email' => $extraFields->get('email'),
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function courses(): array
    {
        $result = $this->call('core_course_get_courses_by_field', [
            'field' => '',
            'value' => '',
        ]);

        return collect($result['courses'] ?? [])
            ->filter(fn (array $course): bool => (int) ($course['id'] ?? 0) > 1)
            ->values()
            ->all();
    }

    public function enrolUser(int $moodleUserId, int $moodleCourseId): void
    {
        $this->call('enrol_manual_enrol_users', [
            'enrolments' => [[
                'roleid' => (int) config('services.moodle.student_role_id'),
                'userid' => $moodleUserId,
                'courseid' => $moodleCourseId,
            ]],
        ]);
    }

    /**
     * @return array<string, mixed>|array<int, mixed>|null
     */
    protected function call(string $function, array $parameters = []): ?array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Moodle integration is not configured.');
        }

        $response = $this->request()->post('/webservice/rest/server.php', array_merge($parameters, [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => $function,
            'moodlewsrestformat' => 'json',
        ]));

        if ($response->failed()) {
            throw new RuntimeException("Moodle returned HTTP {$response->status()}.");
        }

        $result = $response->json();
        if (is_array($result) && isset($result['exception'])) {
            throw new RuntimeException((string) ($result['message'] ?? 'Moodle rejected the request.'));
        }

        if ($result !== null && ! is_array($result)) {
            throw new RuntimeException('Moodle returned an unexpected response.');
        }

        return $result;
    }

    protected function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.moodle.url'), '/'))
            ->asForm()
            ->acceptJson()
            ->timeout(15);
    }
}
