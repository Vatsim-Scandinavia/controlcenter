<?php

namespace Tests\Feature;

use App\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilesTest extends TestCase
{

    use WithFaker, RefreshDatabase;

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

    /** @test */
    public function mentor_can_upload_a_pdf_file()
    {
        $user = factory(\App\User::class)->create(['group' => 3]);
        $file = UploadedFile::fake()->create($this->faker->word . '.pdf', 2048, 'application/pdf');

        $response = $this->actingAs($user)->postJson(route('file.store'), ['file' => $file]);
        $file = File::find($response->decodeResponseJson('file_id'));

        Storage::disk('test')->assertExists($file->full_path);
    }

    /** @test */
    public function mentor_can_upload_an_image_file()
    {
        $user = factory(\App\User::class)->create(['group' => 3]);
        $file = UploadedFile::fake()->image($this->faker->word);

        $response = $this->actingAs($user)->postJson(route('file.store'), ['file' => $file]);
        $file = File::find($response->decodeResponseJson('file_id'));

        Storage::disk('test')->assertExists($file->full_path);
    }

    /** @test */
    public function user_can_see_a_file_they_uploaded()
    {
        $user = factory(\App\User::class)->create(['group' => 3]);
        $file = UploadedFile::fake()->image($this->faker->word);

        $response = $this->actingAs($user)->postJson(route('file.store'), ['file' => $file]);
        $file = File::find($response->decodeResponseJson('file_id'));

        Storage::disk('test')->assertExists($file->full_path);

        $this->actingAs($user)->get(route('file.get', ['file' => $file]))
            ->assertStatus(200);
    }

    /** @test */
    public function regular_user_cant_upload_a_file()
    {
        $user = factory(\App\User::class)->create(['group' => null]);
        $file = UploadedFile::fake()->image($this->faker->word);

        $this->actingAs($user)->postJson(route('file.store'), ['file' => $file])
            ->assertStatus(403);
    }

    /** @test */
    public function owner_can_delete_their_own_files()
    {
        $user = factory(\App\User::class)->create(['group' => 3]);
        $file = UploadedFile::fake()->image($this->faker->word);
        $response = $this->actingAs($user)->postJson(route('file.store'), ['file' => $file]);
        $file_id = $response->decodeResponseJson('file_id');
        $response->assertStatus(200)->assertJsonFragment(['message' => 'File successfully uploaded']);

        $this->actingAs($user)->delete(route('file.delete', ['file' => $file_id]))->assertRedirect()->assertSessionHas('message', 'File successfully deleted');
    }

    /** @test */
    public function moderator_can_delete_another_users_file()
    {
        $user = factory(\App\User::class)->create(['group' => 3]);
        $file = UploadedFile::fake()->image($this->faker->word);
        $response = $this->actingAs($user)->postJson(route('file.store'), ['file' => $file]);
        $file_id = $response->decodeResponseJson('file_id');
        $response->assertStatus(200)->assertJsonFragment(['message' => 'File successfully uploaded']);

        $moderator = factory(\App\User::class)->create(['group' => 2]);

        $this->actingAs($moderator)->delete(route('file.delete', ['file' => $file_id]))->assertRedirect()->assertSessionHas('message', 'File successfully deleted');
    }

    /** @test */
    public function regular_user_cant_delete_another_users_file()
    {
        $user = factory(\App\User::class)->create(['group' => 3]);
        $file = UploadedFile::fake()->image($this->faker->word);
        $response = $this->actingAs($user)->postJson(route('file.store'), ['file' => $file]);
        $file_id = $response->decodeResponseJson('file_id');
        $response->assertStatus(200)->assertJsonFragment(['message' => 'File successfully uploaded']);

        $otherUser = factory(\App\User::class)->create(['group' => null]);

        $this->actingAs($otherUser)->delete(route('file.delete', ['file' => $file_id]))->assertStatus(403)->assertSessionMissing('message');
    }

}
