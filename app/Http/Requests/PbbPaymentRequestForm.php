<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PbbPaymentRequestForm extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'applicant_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'email' => ['nullable', 'email:rfc,dns'],
            'nops' => ['required', 'array', 'min:1', 'max:25'],
            'nops.*.nop' => ['required', 'string', 'max:40'],
            'nops.*.tax_year' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'applicant_name.required' => 'Nama pemohon wajib diisi.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.regex' => 'Format nomor WhatsApp tidak valid.',
            'nops.required' => 'Minimal satu NOP harus ditambahkan.',
            'nops.min' => 'Minimal satu NOP harus ditambahkan.',
            'nops.*.nop.required' => 'NOP tidak boleh kosong.',
            'nops.*.tax_year.integer' => 'Tahun pajak NOP harus berupa angka.',
        ];
    }
}
