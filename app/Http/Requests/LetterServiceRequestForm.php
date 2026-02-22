<?php

namespace App\Http\Requests;

use App\Support\LetterSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LetterServiceRequestForm extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]{16}$/'],
            'phone' => ['required', 'string', 'min:10', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'letter_type' => ['required', Rule::in(LetterSchema::types())],
            'dynamic_data' => ['nullable', 'array'],
            'email' => ['nullable', 'email', 'max:120'],
        ];

        foreach (LetterSchema::allDynamicFields() as $field) {
            $fieldRules = ['nullable'];
            $type = (string) ($field['type'] ?? 'text');
            $max = (int) ($field['max'] ?? 255);

            if ($type === 'date') {
                $fieldRules[] = 'date';
            } elseif ($type === 'time') {
                $fieldRules[] = 'date_format:H:i';
            } elseif ($type === 'select') {
                $fieldRules[] = 'string';
                if (! empty($field['options']) && is_array($field['options'])) {
                    $fieldRules[] = Rule::in($field['options']);
                }
            } else {
                $fieldRules[] = 'string';
            }

            if (in_array('string', $fieldRules, true)) {
                $fieldRules[] = "max:{$max}";
            }

            $rules['dynamic_data.' . $field['name']] = $fieldRules;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nik.size' => 'NIK harus terdiri dari 16 digit.',
            'nik.regex' => 'NIK hanya boleh berisi angka.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.regex' => 'Format nomor WhatsApp tidak valid.',
            'letter_type.required' => 'Jenis surat wajib dipilih.',
            'letter_type.in' => 'Jenis surat tidak valid.',
            'email.email' => 'Format email tidak valid.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('letter_type');
            $requiredDynamicFields = LetterSchema::requiredFieldsForType($type);
            $fieldsMap = LetterSchema::fieldsMap();

            foreach ($requiredDynamicFields as $fieldName) {
                $value = data_get($this->input('dynamic_data', []), $fieldName);
                if (is_string($value)) {
                    $value = trim($value);
                }

                if ($value === null || $value === '') {
                    $label = $fieldsMap[$fieldName]['label'] ?? $fieldName;
                    $validator->errors()->add("dynamic_data.{$fieldName}", "{$label} wajib diisi untuk jenis surat yang dipilih.");
                }
            }
        });
    }
}
