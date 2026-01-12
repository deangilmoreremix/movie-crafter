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

        return $this->client->responses()->create([
            "model" => "gpt-5.2",
            "input" => "
                Title: `$title` \n
                Genre: `$genre` \n
                Description: `$description`
            ",
            "instructions" => config("openai.system_prompt"),
            "reasoning" => [
                "effort" => "medium"
            ],
            "text" => [
                "verbosity" => "high"
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
