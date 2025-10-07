<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebsiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => [
                'required',
                'string',
                'url',
                Rule::unique('websites', 'url')->where(function ($query) {
                    return $query->where('user_id', $this->user()->id);
                })->ignore($this->route('website')->id),
            ],
            'ssl_monitoring_enabled' => ['boolean'],
            'uptime_monitoring_enabled' => ['boolean'],
            'monitoring_config' => ['array'],
            'monitoring_config.timeout' => ['nullable', 'integer', 'min:5', 'max:300'],
            'monitoring_config.description' => ['nullable', 'string', 'max:500'],
            'monitoring_config.content_expected_strings' => ['nullable', 'array'],
            'monitoring_config.content_expected_strings.*' => ['string', 'max:255'],
            'monitoring_config.content_forbidden_strings' => ['nullable', 'array'],
            'monitoring_config.content_forbidden_strings.*' => ['string', 'max:255'],
            'monitoring_config.content_regex_patterns' => ['nullable', 'array'],
            'monitoring_config.content_regex_patterns.*' => ['string', 'max:255'],
            'monitoring_config.javascript_enabled' => ['boolean'],
            'monitoring_config.javascript_wait_seconds' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A website name is required.',
            'url.required' => 'A website URL is required.',
            'url.url' => 'Please enter a valid URL (e.g., https://example.com).',
            'url.unique' => 'You are already monitoring this URL.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize URL before validation
        if ($this->has('url')) {
            $url = trim($this->url);

            // Add protocol if missing
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            // Normalize to lowercase and remove trailing slash
            $url = strtolower(rtrim($url, '/'));

            $this->merge(['url' => $url]);
        }
    }
}
