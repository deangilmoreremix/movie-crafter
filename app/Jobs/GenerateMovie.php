<?php

namespace App\Jobs;

use App\Models\Movie;
use App\Models\MovieAnswer;
use App\Models\MovieStoryBoard;
use App\MovieStatus;
use App\Services\AI;
use App\Services\Pinata;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function Livewire\Volt\title;

class GenerateMovie implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // seconds
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Movie $movie)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(Pinata $pinataService, AI $aiService): void
    {
        try {
            DB::beginTransaction();
            Log::info('Starting movie generation', [
                'movie_id' => $this->movie->id,
                'title' => $this->movie->title,
                'genre' => $this->movie->genre,
                'description_length' => strlen($this->movie->description)
            ]);

            $result = $aiService->prompt(
                title: $this->movie->title,
                genre: $this->movie->genre,
                description: $this->movie->description
            );

            // Check for tool calls (not supported in single-turn implementation)
            if (isset($result->tool_calls) && !empty($result->tool_calls)) {
                Log::warning('AI response contains tool calls, which are not handled in this implementation', [
                    'movie_id' => $this->movie->id,
                    'tool_calls' => $result->tool_calls
                ]);
                // For now, continue without tool handling
            }

            $answer = $result->output->content ?? $result->content ?? '';
            if (empty($answer)) {
                throw new \Exception('Empty response from AI service');
            }

            $parsed = json_decode($answer, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error', [
                    'movie_id' => $this->movie->id,
                    'answer' => $answer,
                    'error' => json_last_error_msg()
                ]);
                throw new \Exception('Invalid JSON response from AI: ' . json_last_error_msg());
            }

            if (!isset($parsed['scenario']) || !isset($parsed['storyboards']) || !isset($parsed['short_description'])) {
                Log::error('Missing required fields in AI response', [
                    'movie_id' => $this->movie->id,
                    'parsed' => $parsed
                ]);
                throw new \Exception('Missing required fields in AI response');
            }
            MovieAnswer::query()->create([
                "answer_raw" => $answer,
                "scenario" => $parsed["scenario"],
                "story_boards" => $parsed["storyboards"],
                "title" => $this->movie->title,
                "short_description" => $parsed["short_description"],
                "error" => "",
                "metadata" => [
                    "prompt_tokens" => $result->usage->promptTokens ?? 0,
                    "completion_tokens" => $result->usage->completionTokens ?? 0,
                    "total_tokens" => $result->usage->totalTokens ?? 0,
                    "creative_notes" => $parsed["creative_notes"] ?? null
                ],
                "is_successful" => true,
                "movie_id" => $this->movie->id
            ]);

            foreach ($parsed["storyboards"] as $i => $storyboard){
                $result = $aiService->generateStoryboardImage(
                    shortDescription: $parsed["short_description"],
                    storyboardDescription: $storyboard
                );

                $upload = $pinataService->uploadFile($this->movie->uuid . "-" . $i . ".png", null, file_get_contents($result->data[0]->url) );
                MovieStoryBoard::query()->create([
                    "movie_id" => $this->movie->id,
                    "description" => $storyboard,
                    "order" => $i,
                    "pinata_id" => $upload["data"]["id"],
                    "pinata_cid" => $upload["data"]["cid"]
                ]);
            }

            $this->movie->update([
                "status" => MovieStatus::SUCCESSFUL
            ]);
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception, ["movieId" => $this->movie->id]);

            $this->movie->update([
                "status" => MovieStatus::FAILED
            ]);
        }

    }
}
