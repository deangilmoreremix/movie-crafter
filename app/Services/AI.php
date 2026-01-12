<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;
use OpenAI;
use OpenAI\Client;

class AI
{
    protected Client $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config("openai.key"));
    }

    public function prompt($title, $genre, $description)
    {
        RateLimiter::attempt('openai_prompt', 10, function () {
            // Allow 10 requests per minute
        }, 60);

        // Dynamically adjust reasoning effort based on genre complexity
        $reasoningEffort = $this->getReasoningEffortForGenre($genre);

        // Adjust verbosity based on description length
        $verbosity = strlen($description) > 200 ? 'high' : 'medium';

        return $this->client->responses()->create([
            "model" => "gpt-5.2",
            "input" => "
                Title: `$title` \n
                Genre: `$genre` \n
                Description: `$description`
            ",
            "instructions" => config("openai.system_prompt") . "\n\nBefore generating the movie content, explain your creative approach and key decisions.",
            "reasoning" => [
                "effort" => $reasoningEffort
            ],
            "text" => [
                "verbosity" => $verbosity
            ],
            "tools" => [
                [
                    "type" => "custom",
                    "name" => "get_genre_conventions",
                    "description" => "Get common tropes and conventions for a specific movie genre"
                ],
                [
                    "type" => "custom",
                    "name" => "analyze_description",
                    "description" => "Analyze the user's description for key themes and elements"
                ]
            ],
            "tool_choice" => [
                "type" => "allowed_tools",
                "mode" => "auto",
                "tools" => ["get_genre_conventions", "analyze_description"]
            ],
            "output" => [
                "format" => [
                    "type" => "json_schema",
                    "name" => "movie-structure-schema",
                    "strict" => true,
                    "schema" => [
                        "type" => "object",
                        "properties" => [
                            "scenario" => [
                                "type" => "string"
                            ],
                            "short_description" => [
                                "type" => "string"
                            ],
                            "storyboards" => [
                                "type" => "array",
                                "items" => [
                                    "type" => "string"
                                ],
                            ],
                            "creative_notes" => [
                                "type" => "string"
                            ]
                        ],
                        "required" => [
                            "scenario",
                            "storyboards",
                            "short_description"
                        ],
                        "additionalProperties" => false
                    ]
                ]
            ]
        ]);
    }

    public function getReasoningEffortForGenre($genre)
    {
        $complexGenres = ['mystery', 'thriller', 'drama', 'fantasy'];
        return in_array(strtolower($genre), $complexGenres) ? 'high' : 'medium';
    }

    public function generateStoryboardImage($shortDescription, $storyboardDescription){
        RateLimiter::attempt('openai_image', 5, function () {
            // Allow 5 image requests per minute
        }, 60);

        return $this->client->images()->create([
            'model' => 'dall-e-3',
            'prompt' => "You are tasked to draw the storyboard images for a movie. Below, you will find the movie description, and an instruction for a storyboard image. Be sure to use the storyboard image style (black and white) for the sketch image. You should always only draw ONE story board at the time, only for the storyboard description. \n
                        ----- \n
                        \n
                        # Movie short description \n
                        $shortDescription \n
                        # Storyboard description\n
                        $storyboardDescription
                        ",
            'n' => 1,
            'size' => '1024x1024',
            'response_format' => 'url',
        ]);
    }
}
