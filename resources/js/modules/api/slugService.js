const sanitizePrefix = (prefix) => prefix.replace(/^\/+|\/+$/g, '');

const resolveAdminPrefix = () => {
    const meta = document.head.querySelector('meta[name="ave-route-prefix"]');
    const value = meta?.getAttribute('content') ?? 'admin';

    return sanitizePrefix(value);
};

const resolveCsrfToken = () => {
    const meta = document.head.querySelector('meta[name="csrf-token"]');
    return meta?.getAttribute('content') ?? '';
};

const buildSlugEndpoint = () => {
    const prefix = resolveAdminPrefix();

    if (prefix === '') {
        return '/ave/api/tools/slug';
    }

    return `/${prefix}/ave/api/tools/slug`;
};

export async function requestSlug(source, options = {}) {
    const trimmed = (source ?? '').toString().trim();

    if (trimmed === '') {
        return '';
    }

    const payload = {
        source: trimmed,
        separator: options.separator ?? '-',
    };

    if (options.language) {
        payload.language = options.language;
    }

    const response = await fetch(buildSlugEndpoint(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': resolveCsrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        throw new Error('Unable to fetch slug');
    }

    const data = await response.json();

    if (!data || typeof data.slug !== 'string') {
        throw new Error('Invalid slug payload');
    }

    return data.slug;
}

