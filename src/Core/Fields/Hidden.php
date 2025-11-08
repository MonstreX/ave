<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * Hidden Field
 *
 * A hidden input field for storing data that should not be displayed to the user.
 *
 * Features:
 * - No visual output on the form
 * - Commonly used for IDs, tokens, or system values
 * - Data is submitted with the form but not editable by users
 * - HTML input type: hidden
 *
 * Example:
 *   Hidden::make('user_id')
 *       ->default($currentUserId)
 *
 *   Hidden::make('csrf_token')
 *       ->default(csrf_token())
 */
class Hidden extends AbstractField
{
    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data
     */
    public function toArray(): array
    {
        return parent::toArray();
    }
}
