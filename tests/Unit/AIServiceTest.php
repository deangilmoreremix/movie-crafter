<?php

use App\Services\AI;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns correct reasoning effort for genres', function () {
    $ai = new AI();

    // Test complex genres get high reasoning
    expect($ai->getReasoningEffortForGenre('mystery'))->toBe('high');
    expect($ai->getReasoningEffortForGenre('thriller'))->toBe('high');
    expect($ai->getReasoningEffortForGenre('drama'))->toBe('high');
    expect($ai->getReasoningEffortForGenre('fantasy'))->toBe('high');

    // Test simple genres get medium reasoning
    expect($ai->getReasoningEffortForGenre('action'))->toBe('medium');
    expect($ai->getReasoningEffortForGenre('comedy'))->toBe('medium');
    expect($ai->getReasoningEffortForGenre('romance'))->toBe('medium');
});

it('handles case insensitive genre matching', function () {
    $ai = new AI();

    expect($ai->getReasoningEffortForGenre('MYSTERY'))->toBe('high');
    expect($ai->getReasoningEffortForGenre('Action'))->toBe('medium');
});

it('returns medium for unknown genres', function () {
    $ai = new AI();

    expect($ai->getReasoningEffortForGenre('unknown'))->toBe('medium');
    expect($ai->getReasoningEffortForGenre(''))->toBe('medium');
});