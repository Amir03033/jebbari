<?php

namespace App\Http\Requests;

use App\Scan\UnsafeUrl;
use App\Scan\UrlGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreScanRequest extends FormRequest
{
    /** The checked URL, once validation passed. */
    public ?array $target = null;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['url' => UrlGuard::normalize((string) $this->input('url'))]);
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'privacy' => ['accepted'],
            'contact_consent' => ['nullable', 'boolean'],
            // Bot traps, as on the contact form.
            'website' => ['nullable', 'string', 'max:255'],
            '_t' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'url' => __('scan.form.url'),
            'email' => __('scan.form.email'),
            'privacy' => __('scan.form.privacy_attr'),
        ];
    }

    public function messages(): array
    {
        return ['privacy.accepted' => __('scan.form.privacy_required')];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->has('url')) {
                return;
            }

            try {
                $this->target = app(UrlGuard::class)->check((string) $this->input('url'));
            } catch (UnsafeUrl $e) {
                $v->errors()->add('url', __('scan.error.'.$e->reason));

                return;
            }

            if (! $v->errors()->has('_t') && ! StoreInquiryRequest::plausibleTime((string) $this->input('_t'))) {
                $v->errors()->add('url', __('site.contact.too_fast'));
            }
        });
    }

    public function isBot(): bool
    {
        return filled($this->input('website'));
    }
}
