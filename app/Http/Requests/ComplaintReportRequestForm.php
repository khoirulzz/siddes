<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComplaintReportRequestForm extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nik' => [
                'required',
                'digits:16',
                Rule::exists('population_records', 'nik'),
            ],
            'reporter_name' => 'required|string|max:255',
            'phone' => 'required|string|min:10|max:15|regex:/^[0-9+\-\s()]+$/',
            'email' => 'nullable|email:rfc,dns',
            'subject' => 'required|string|max:255',
            'category' => 'required|string|in:Infrastruktur,Pelayanan Publik,Sosial,Keamanan,Lainnya',
            'location' => 'nullable|string|max:255',
            'description' => 'required|string|min:10',
            'evidence' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf,mp4,mov',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,video/mp4,video/quicktime',
                'max:5120',
            ],
        ];
    }

    public function messages()
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nik.exists' => 'NIK tidak ditemukan pada data kependudukan.',
            'reporter_name.required' => 'Nama pelapor harus diisi',
            'reporter_name.max' => 'Nama tidak boleh lebih dari 255 karakter',
            'phone.required' => 'No. HP/WhatsApp harus diisi',
            'phone.regex' => 'No. HP/WhatsApp tidak valid. Harus dimulai dengan +62 atau 0',
            'email.email' => 'Format email tidak valid',
            'subject.required' => 'Judul pengaduan harus diisi',
            'subject.max' => 'Judul tidak boleh lebih dari 255 karakter',
            'category.required' => 'Kategori harus dipilih',
            'category.in' => 'Kategori tidak valid',
            'description.required' => 'Uraian pengaduan harus diisi',
            'description.min' => 'Uraian minimal harus 10 karakter',
            'evidence.mimes' => 'Format file tidak didukung. Gunakan: jpg, jpeg, png, webp, pdf, mp4, mov',
            'evidence.mimetypes' => 'Tipe file bukti tidak valid.',
            'evidence.max' => 'Ukuran file maksimal 5 MB',
        ];
    }
}
