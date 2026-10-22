<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['sometimes', 'in:day,week,month,year,custom'],
            'start_date' => ['required_if:period,custom', 'date_format:Y-m-d', 'nullable', 'before_or_equal:today'],
            'end_date' => [
                'required_if:period,custom',
                'date_format:Y-m-d',
                'nullable',
                'after_or_equal:start_date',
                'before_or_equal:today',
            ],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
