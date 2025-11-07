/**
 * Form Module Constants
 *
 * Centralized constants for timing, animations, and other magic numbers
 * used throughout form-related modules (fieldSet, mediaField, etc.)
 */

// Animation and transition timings (in milliseconds)
export const ANIMATION_DURATIONS = {
    // Main sorting/drag animation duration
    SORTABLE: 400,
    // Collapse/expand animations
    COLLAPSE: 300,
    // General transitions
    TRANSITION: 200,
};

// Animation easing functions
export const ANIMATION_EASING = {
    // Smooth cubic-bezier for drag and drop
    SORTABLE: 'cubic-bezier(0.25, 0.8, 0.25, 1)',
};

// HTTP status codes
export const HTTP_STATUS = {
    OK: 200,
    CREATED: 201,
    BAD_REQUEST: 400,
    UNAUTHORIZED: 401,
    FORBIDDEN: 403,
    NOT_FOUND: 404,
    CONFLICT: 409,
    UNPROCESSABLE_ENTITY: 422,
    INTERNAL_SERVER_ERROR: 500,
};

// File size constants
export const FILE_SIZE = {
    // Base for converting bytes to KB/MB/GB (1024 bytes = 1 KB)
    BYTE_FACTOR: 1024,
    // Decimal places for displaying file sizes
    DECIMAL_PLACES: 1,
};

// Form field limits
export const FORM_FIELD_LIMITS = {
    // Maximum items allowed in a fieldset (0 = unlimited)
    DEFAULT_MAX_ITEMS: 0,
    // Minimum items required in a fieldset
    DEFAULT_MIN_ITEMS: 0,
};

// DOM breakpoints (matching CSS media queries)
export const BREAKPOINTS = {
    // Mobile to tablet breakpoint
    TABLET: 768,
    // Tablet to desktop breakpoint
    DESKTOP: 992,
};
