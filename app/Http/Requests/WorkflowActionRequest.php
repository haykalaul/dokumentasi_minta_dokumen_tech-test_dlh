<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WorkflowActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        // Check if the current action requires notes (Revision and Rejection)
        $isNotesRequired = str_contains($this->getPathInfo(), 'revision') || str_contains($this->getPathInfo(), 'reject');

        $rules['notes'] = $isNotesRequired
            ? ['required', 'string', 'min:5', 'max:1000']
            : ['nullable', 'string', 'max:1000'];

        return $rules;
    }
}
