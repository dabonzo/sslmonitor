<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebsiteRequest extends FormRequest
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
                'regex:/^https:\/\//',
                Rule::unique('websites', 'url')->where(function ($query) {
                    return $query->where('user_id', $this->user()->id);
                }),
            ],
            'ssl_monitoring_enabled' => ['boolean'],
            'uptime_monitoring_enabled' => ['boolean'],
            'monitoring_config' => ['array'],
            'monitoring_config.description' => ['nullable', 'string', 'max:500'],
            'monitoring_config.check_interval' => ['nullable', 'integer', 'min:60', 'max:3600'],
            'monitoring_config.content_expected_strings' => ['nullable', 'array'],
            'monitoring_config.content_expected_strings.*' => ['string', 'max:255'],
            'monitoring_config.content_forbidden_strings' => ['nullable', 'array'],
            'monitoring_config.content_forbidden_strings.*' => ['string', 'max:255'],
            'monitoring_config.content_regex_patterns' => ['nullable', 'array'],
            'monitoring_config.content_regex_patterns.*' => ['string', 'max:255'],
            'monitoring_config.javascript_enabled' => ['boolean'],
            'monitoring_config.javascript_wait_seconds' => ['integer', 'min:1', 'max:30'],
            'immediate_check' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A website name is required.',
            'url.required' => 'A website URL is required.',
            'url.url' => 'Please enter a valid URL (e.g., https://example.com).',
            'url.regex' => 'Only secure HTTPS URLs are allowed. HTTP is not supported for SSL monitoring.',
            'url.unique' => 'You are already monitoring this URL.',
            'monitoring_config.description.max' => 'Description cannot exceed 500 characters.',
            'monitoring_config.content_expected_strings.*.max' => 'Expected content strings cannot exceed 255 characters.',
            'monitoring_config.content_forbidden_strings.*.max' => 'Forbidden content strings cannot exceed 255 characters.',
            'monitoring_config.content_regex_patterns.*.max' => 'Regex patterns cannot exceed 255 characters.',
            'monitoring_config.javascript_wait_seconds.min' => 'JavaScript wait time must be at least 1 second.',
            'monitoring_config.javascript_wait_seconds.max' => 'JavaScript wait time cannot exceed 30 seconds.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize URL before validation
        if ($this->has('url')) {
            $url = trim($this->url);

            // Convert http:// to https:// or add https:// if missing
            if (str_starts_with($url, 'http://')) {
                $url = 'https://' . substr($url, 7); // Remove http:// and add https://
            } elseif (!str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            // Normalize to lowercase and remove trailing slash
            $url = strtolower(rtrim($url, '/'));

            $this->merge(['url' => $url]);
        }
    }
}
