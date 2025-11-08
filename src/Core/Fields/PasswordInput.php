<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * PasswordInput Field
 *
 * A secure password input field with optional visibility toggle.
 *
 * Features:
 * - HTML input type: password
 * - Optional eye icon button to toggle password visibility (JavaScript-powered)
 * - Supports minimum length validation
 * - Auto-complete attributes for better UX
 * - Can require confirmation with another password field
 * - Uses custom styled input with password toggle
 *
 * Example (Basic):
 *   PasswordInput::make('password')
 *       ->label('Password')
 *       ->required()
 *       ->minLength(8)
 *
 * Example (With confirmation):
 *   PasswordInput::make('new_password')
 *       ->label('New Password')
 *       ->required()
 *       ->minLength(8)
 *       ->showToggle(true)
 *
 * Example (Without toggle):
 *   PasswordInput::make('pin')
 *       ->label('PIN')
 *       ->minLength(4)
 *       ->maxLength(4)
 *       ->showToggle(false)
 */
class PasswordInput extends TextInput
{
    /**
     * Whether to show password visibility toggle button
     */
    protected bool $showVisibilityToggle = true;

    /**
     * Whether this is a confirmation field (for password repeat)
     */
    protected bool $isConfirmation = false;

    /**
     * Enable or disable password visibility toggle button
     *
     * @param bool $show Whether to show the toggle button
     * @return static
     */
    public function showToggle(bool $show = true): static
    {
        $this->showVisibilityToggle = $show;
        return $this;
    }

    /**
     * Check if visibility toggle is enabled
     *
     * @return bool
     */
    public function hasVisibilityToggle(): bool
    {
        return $this->showVisibilityToggle;
    }

    /**
     * Mark this as a confirmation field (password repeat)
     *
     * @return static
     */
    public function confirmation(): static
    {
        $this->isConfirmation = true;
        return $this;
    }

    /**
     * Check if this is a confirmation field
     *
     * @return bool
     */
    public function isConfirmationField(): bool
    {
        return $this->isConfirmation;
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['showToggle'] = $this->showVisibilityToggle;
        $array['isConfirmation'] = $this->isConfirmation;

        return $array;
    }
}
