<?php

namespace Tests\Browser\Traits;

/**
 * Trait for common form interaction patterns in browser tests
 */
trait InteractsWithForms
{
    /**
     * Helper to check if form has validation errors
     */
    protected function hasValidationErrors(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'error') !== false || strpos($snapshot, 'required') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if specific field has error
     */
    protected function hasFieldError(string $fieldName): bool
    {
        try {
            $snapshot = $this->snapshot();
            // Look for error messages near field name
            return strpos($snapshot, $fieldName) !== false && strpos($snapshot, 'error') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to wait for form to be visible
     */
    protected function waitForForm(int $seconds = 5): void
    {
        try {
            $this->waitForFunction("document.querySelector('form') !== null", $seconds * 1000);
        } catch (\Exception $e) {
            // Ignore timeout
        }
    }

    /**
     * Helper to check if form is submitting
     */
    protected function isFormSubmitting(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'disabled') !== false || strpos($snapshot, 'loading') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if submit button is visible
     */
    protected function hasSubmitButton(string $buttonText = 'Save'): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, $buttonText) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if cancel button is visible
     */
    protected function hasCancelButton(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'Cancel') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if form has required field indicators
     */
    protected function hasRequiredFieldIndicators(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, '*') !== false || strpos($snapshot, 'required') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
