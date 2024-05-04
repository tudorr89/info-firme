<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class CompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cui'           => ['required_if:company,null','exists:companies,cui'],
            'company'       => ['required_if:cui,null'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Company not found!',
            'data'      => $validator->errors()
        ]));
    }

    public function messages()
    {
        return [
            'cui.required_if' => 'The :attribute field is required when :other is :value.',
            'company.required_if' => 'The :attribute field is required when :other is :value.',
        ];
    }
}
