<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Validator;

class StoreInquiryRequest extends FormRequest
{
    /** A real person cannot fill in the form faster than this. */
    public const MIN_SECONDS = 4;

    /** Forms older than this must be reloaded. */
    public const MAX_SECONDS = 86400;

    public const BUDGETS = ['lt-2.5k', '2.5-7.5k', '7.5-15k', '15k+', 'unsure'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'company' => ['nullable', 'string', 'max:160'],
            'budget' => ['nullable', 'string', 'in:'.implode(',', self::BUDGETS)],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Bot traps. Both are checked in the controller, silently for the honeypot.
            'website' => ['nullable', 'string', 'max:255'],
            '_t' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('site.contact.name'),
            'email' => __('site.contact.email'),
            'company' => __('site.contact.company'),
            'budget' => __('site.contact.budget'),
            'message' => __('site.contact.message'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->has('_t')) {
                return;
            }

            if (! $this->timeIsPlausible()) {
                $v->errors()->add('message', __('site.contact.too_fast'));
            }
        });
    }

    /** @return bool true when the form was loaded between 4 seconds and 24 hours ago */
    public function timeIsPlausible(): bool
    {
        try {
            $loaded = (int) Crypt::decryptString((string) $this->input('_t'));
        } catch (DecryptException) {
            return false;
        }

        $age = time() - $loaded;

        return $age >= self::MIN_SECONDS && $age <= self::MAX_SECONDS;
    }

    public function isBot(): bool
    {
        return filled($this->input('website'));
    }
}
