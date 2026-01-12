<?php

use App\Jobs\GenerateMovie;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('can create a movie and dispatch job', function () {
    Queue::fake();

    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post(route('movies.create'), [
        'title' => 'Test Movie',
        'description' => 'This is a test description that is long enough to pass validation.',
        'genre' => 'action',
    ]);

    $response->assertRedirect(route('dashboard'));

    Queue::assertPushed(GenerateMovie::class);

    $this->assertDatabaseHas('movies', [
        'title' => 'Test Movie',
        'user_id' => $user->id,
        'status' => 'in_progress',
    ]);
});

it('validates movie creation input', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post(route('movies.create'), [
        'title' => '',
        'description' => 'Short',
        'genre' => 'invalid',
    ]);

    $response->assertSessionHasErrors(['title', 'description', 'genre']);
});