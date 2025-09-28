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
            'monitoring_config.check_interval' => ['integer', 'min:300', 'max:86400'], // 5 minutes to 24 hours
            'monitoring_config.timeout' => ['integer', 'min:10', 'max:90'], // 10 to 90 seconds
            'monitoring_config.description' => ['nullable', 'string', 'max:500'],
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
            'monitoring_config.check_interval.min' => 'Check interval must be at least 5 minutes.',
            'monitoring_config.check_interval.max' => 'Check interval cannot exceed 24 hours.',
            'monitoring_config.timeout.min' => 'Timeout must be at least 10 seconds.',
            'monitoring_config.timeout.max' => 'Timeout cannot exceed 90 seconds.',
            'monitoring_config.description.max' => 'Description cannot exceed 500 characters.',
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
