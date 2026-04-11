<?php

namespace App\Http\Requests;

use App\Concerns\ShortLinkValidationRules;
use App\Models\ShortLink;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShortLinkRequest extends FormRequest
{
    use ShortLinkValidationRules;

    public function authorize(): bool
    {
        /** @var ShortLink $link */
        $link = $this->route('link');

        return $this->user()?->can('update', $link) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var ShortLink $link */
        $link = $this->route('link');

        return $this->shortLinkUpdateRules($link);
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug');
        if (is_string($slug)) {
            $slug = mb_strtolower(trim($slug));
        }

        $expires = $this->input('expires_at');
        if ($expires === '') {
            $expires = null;
        }

        $utm = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $field) {
            if ($this->input($field) === '') {
                $utm[$field] = null;
            }
        }

        $this->merge([
            'slug' => $slug,
            'is_active' => $this->boolean('is_active'),
            'expires_at' => $expires,
            ...$utm,
        ]);
    }
}
