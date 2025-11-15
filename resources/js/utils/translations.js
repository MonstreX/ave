/**
 * Get translation by key with optional replacements
 *
 * @param {string} key - Translation key (e.g., 'toast.titles.success')
 * @param {object} replacements - Key-value pairs for replacements (e.g., {min: 5, max: 10})
 * @returns {string}
 */
export function trans(key, replacements = {}) {
    if (!window.AveTranslations) {
        console.warn('AveTranslations not loaded')
        return key
    }

    // Split key by dots to traverse nested object
    const keys = key.split('.')
    let value = window.AveTranslations

    for (const k of keys) {
        if (value && typeof value === 'object' && k in value) {
            value = value[k]
        } else {
            console.warn(`Translation key not found: ${key}`)
            return key
        }
    }

    // If value is not a string, return the key
    if (typeof value !== 'string') {
        console.warn(`Translation value is not a string: ${key}`)
        return key
    }

    // Replace placeholders like :min, :max, etc.
    let result = value
    for (const [placeholder, replacement] of Object.entries(replacements)) {
        result = result.replace(`:${placeholder}`, replacement)
    }

    return result
}

/**
 * Get current locale
 *
 * @returns {string}
 */
export function getLocale() {
    return window.AveLocale || 'en'
}

/**
 * Check if current locale is RTL
 *
 * @returns {boolean}
 */
export function isRTL() {
    const rtlLocales = ['ar', 'he', 'fa', 'ur']
    return rtlLocales.includes(getLocale())
}
