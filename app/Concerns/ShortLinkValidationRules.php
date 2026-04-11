<?php

namespace App\Concerns;

use App\Models\ShortLink;
use App\Support\ReservedSlugs;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ShortLinkValidationRules
{
    /**
     * Validation rules for creating a short link.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function shortLinkStoreRules(): array
    {
        return [
            'destination_url' => $this->shortLinkDestinationUrlRules(),
            'slug' => $this->shortLinkSlugStoreRules(),
            'utm_source' => $this->shortLinkUtmFieldRules(),
            'utm_medium' => $this->shortLinkUtmFieldRules(),
            'utm_campaign' => $this->shortLinkUtmFieldRules(),
            'utm_term' => $this->shortLinkUtmFieldRules(),
            'utm_content' => $this->shortLinkUtmFieldRules(),
            'expires_at' => $this->shortLinkExpiresAtRules(),
            'is_active' => $this->shortLinkIsActiveRules(),
        ];
    }

    /**
     * Validation rules for updating a short link.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function shortLinkUpdateRules(ShortLink $link): array
    {
        return [
            'destination_url' => $this->shortLinkDestinationUrlRules(),
            'slug' => $this->shortLinkSlugUpdateRules($link),
            'utm_source' => $this->shortLinkUtmFieldRules(),
            'utm_medium' => $this->shortLinkUtmFieldRules(),
            'utm_campaign' => $this->shortLinkUtmFieldRules(),
            'utm_term' => $this->shortLinkUtmFieldRules(),
            'utm_content' => $this->shortLinkUtmFieldRules(),
            'expires_at' => $this->shortLinkExpiresAtRules(),
            'is_active' => $this->shortLinkIsActiveRules(),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function shortLinkDestinationUrlRules(): array
    {
        return ['required', 'string', 'max:2048', 'regex:/^https?:\\/\\/.+/i'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function shortLinkSlugStoreRules(): array
    {
        return [
            'nullable',
            'string',
            'max:64',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('short_links', 'slug'),
            $this->shortLinkReservedSlugRule(),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function shortLinkSlugUpdateRules(ShortLink $link): array
    {
        return [
            'required',
            'string',
            'max:64',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('short_links', 'slug')->ignore($link->id),
            $this->shortLinkReservedSlugRule(),
        ];
    }

    /**
     * @return \Closure(string, mixed, \Closure): void
     */
    protected function shortLinkReservedSlugRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && ReservedSlugs::contains($value)) {
                $fail(__('This slug is reserved.'));
            }
        };
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function shortLinkUtmFieldRules(): array
    {
        return ['nullable', 'string', 'max:255'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function shortLinkExpiresAtRules(): array
    {
        return ['nullable', 'date'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function shortLinkIsActiveRules(): array
    {
        return ['boolean'];
    }
}
