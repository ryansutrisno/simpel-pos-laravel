<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MayarWebhookRequest extends FormRequest
{
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
            'event' => ['required', 'string'],
            'data' => ['required', 'array'],
            'data.id' => ['required', 'string', 'max:64'],
            'data.status' => ['required', 'boolean'],
            'data.amount' => ['required', 'integer', 'min:1'],
            'data.merchantId' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'event.required' => 'Mayar event wajib diisi.',
            'event.string' => 'Mayar event harus berupa teks.',
            'data.required' => 'Data webhook Mayar wajib diisi.',
            'data.array' => 'Data webhook Mayar harus berupa objek.',
            'data.id.required' => 'ID delivery Mayar wajib diisi.',
            'data.id.string' => 'ID delivery Mayar harus berupa teks.',
            'data.id.max' => 'ID delivery Mayar terlalu panjang.',
            'data.status.required' => 'Status webhook Mayar wajib diisi.',
            'data.status.boolean' => 'Status webhook Mayar harus berupa boolean.',
            'data.amount.required' => 'Nominal webhook Mayar wajib diisi.',
            'data.amount.integer' => 'Nominal webhook Mayar harus berupa bilangan bulat.',
            'data.amount.min' => 'Nominal webhook Mayar harus lebih besar dari nol.',
            'data.merchantId.required' => 'Merchant ID Mayar wajib diisi.',
            'data.merchantId.string' => 'Merchant ID Mayar harus berupa teks.',
        ];
    }
}
