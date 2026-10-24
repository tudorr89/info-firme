<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cui' => ['required_if:company,null', 'exists:companies,cui'],
            'company' => ['required_if:cui,null'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        // The only rules are the presence and the existence of a company
        // identifier, so a failure means the company cannot be found.
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Company not found!',
            'data' => $validator->errors(),
        ], 404));
    }

    public function messages()
    {
        return [
            'cui.required_if' => 'The :attribute field is required when :other is :value.',
            'company.required_if' => 'The :attribute field is required when :other is :value.',
        ];
    }
}
