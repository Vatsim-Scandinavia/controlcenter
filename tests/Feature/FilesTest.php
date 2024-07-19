<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FilesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Create a special tearDown method since we don't want our test files
     * to lie around on the server after the tests are done.
     *
     * @throws \Throwable
     */
    protected function tearDown(): void
    {
        Storage::deleteDirectory('public/');
        parent::tearDown();
    }

    #[Test]
    public function mentor_can_upload_a_pdf_file()
    {
        $user = User::factory()->create(['id' => 10000001]);
        $user->groups()->attach(3, ['area_id' => 1]);
        $file = UploadedFile::fake()->create($this->faker->word . '.pdf', 2048, 'application/pdf');

        $response = $this->actingAs($user)->postJson(route('file.store'), ['file' => $file]);
        $file = File::find($response->json('file_id'));

        Storage::disk('test')->assertExists($file->full_path);
    }

    #[Test]
    public function mentor_can_upload_an_image_file()
    {
        $user = User::factory()->create(['id' => 10000001]);
        $user->groups()->attach(3, ['area_id' => 1]);
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        $response = $this->actingAs($user)->postJson(route('file.store'), ['file' => $file]);
        $file = File::find($response->json('file_id'));

        Storage::disk('test')->assertExists($file->full_path);
    }

    #[Test]
    public function user_can_see_a_file_they_uploaded()
    {
        $user = User::factory()->create(['id' => 10000001]);
        $user->groups()->attach(3, ['area_id' => 1]);
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        $response = $this->actingAs($user)->postJson(route('file.store'), ['file' => $file]);
        $file = File::find($response->json('file_id'));

        Storage::disk('test')->assertExists($file->full_path);

        $this->actingAs($user)->get(route('file.get', ['file' => $file]))
            ->assertStatus(200);
    }

    #[Test]
    public function regular_user_cant_upload_a_file()
    {
        $user = User::factory()->create(['id' => 10000001]);
        $file = UploadedFile::fake()->image($this->faker->word);

        $this->actingAs($user)->postJson(route('file.store'), ['file' => $file])
            ->assertStatus(403);
    }

    #[Test]
    public function owner_can_delete_their_own_files()
    {
        $user = User::factory()->create(['id' => 10000001]);
        $user->groups()->attach(3, ['area_id' => 1]);
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');
        $response = $this->actingAs($user)->postJson(route('file.store'), ['file' => $file]);
        $file_id = $response->json('file_id');
        $response->assertStatus(200)->assertJsonFragment(['message' => 'File successfully uploaded']);

        $this->actingAs($user)->delete(route('file.delete', ['file' => $file_id]))
            ->assertRedirect()
            ->assertSessionHas('success', 'File successfully deleted');
    }

    #[Test]
    public function moderator_can_delete_another_users_file()
    {
        $user = User::factory()->create(['id' => 10000001]);
        $user->groups()->attach(3, ['area_id' => 1]);
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');
        $response = $this->actingAs($user)->postJson(route('file.store'), ['file' => $file]);
        $file_id = $response->json('file_id');
        $response->assertStatus(200)->assertJsonFragment(['message' => 'File successfully uploaded']);

        $moderator = User::factory()->create();
        $moderator->groups()->attach(2, ['area_id' => 1]);

        $this->actingAs($moderator)->delete(route('file.delete', ['file' => $file_id]))->assertRedirect()->assertSessionHas('success', 'File successfully deleted');
    }

    #[Test]
    public function regular_user_cant_delete_another_users_file()
    {
        $user = \App\Models\User::factory()->create(['id' => 10000001]);
        $user->groups()->attach(3, ['area_id' => 1]);
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');
        $response = $this->actingAs($user)->postJson(route('file.store'), ['file' => $file]);
        $file_id = $response->json('file_id');
        $response->assertStatus(200)->assertJsonFragment(['message' => 'File successfully uploaded']);

        $otherUser = \App\Models\User::factory()->create();

        $this->actingAs($otherUser)->delete(route('file.delete', ['file' => $file_id]))->assertStatus(403)->assertSessionMissing('message');
    }
}
