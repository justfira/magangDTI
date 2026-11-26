<?php

namespace App\Http\Requests\Mentor;

use App\Models\ProgresTugas;
use Illuminate\Foundation\Http\FormRequest;

class ReviewProgresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()?->punyaPeran('MENTOR') ?? false);
    }

    public function rules(): array
    {
        $hasilReviewValid = implode(',', ProgresTugas::HASIL_REVIEW);

        return [
            'hasil_review' => "required|in:{$hasilReviewValid}",
            'catatan_review' => 'required_if:hasil_review,Perlu Perbaikan|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'hasil_review.required' => 'Keputusan/catatan belum lengkap (keputusan review wajib dipilih).',
            'hasil_review.in' => 'Keputusan review tidak valid.',
            'catatan_review.required_if' => 'Keputusan/catatan belum lengkap (catatan wajib diisi jika status Perlu Perbaikan).',
        ];
    }
}
