<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMovieRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $genreOptions = [
            'action' => 'Action',
            'comedy' => 'Comedy',
            'drama' => 'Drama',
            'fantasy' => 'Fantasy',
            'horror' => 'Horror',
            'mystery' => 'Mystery',
            'romance' => 'Romance',
            'thriller' => 'Thriller',
            'western' => 'Western',
        ];

        return [
            'title' => ['required', 'string', 'min:2', 'max:50'],
            'description' => ['required', 'string', 'min:50', 'max:512'],
            'genre' => ['required', 'string', Rule::in(array_keys($genreOptions))],
        ];
    }
}