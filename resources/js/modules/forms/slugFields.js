import { requestSlug } from '../api/slugService';

const charMap = {
    '\u0430': 'a', '\u0431': 'b', '\u0432': 'v', '\u0433': 'g', '\u0434': 'd',
    '\u0435': 'e', '\u0451': 'e', '\u0436': 'zh', '\u0437': 'z', '\u0438': 'i',
    '\u0439': 'y', '\u043a': 'k', '\u043b': 'l', '\u043c': 'm', '\u043d': 'n',
    '\u043e': 'o', '\u043f': 'p', '\u0440': 'r', '\u0441': 's', '\u0442': 't',
    '\u0443': 'u', '\u0444': 'f', '\u0445': 'h', '\u0446': 'ts', '\u0447': 'ch',
    '\u0448': 'sh', '\u0449': 'sch', '\u044a': '', '\u044b': 'y', '\u044c': '',
    '\u044d': 'e', '\u044e': 'yu', '\u044f': 'ya',
};

const slugifyLocal = (value) => (
    value
        .toString()
        .toLowerCase()
        .split('')
        .map((char) => {
            if (Object.prototype.hasOwnProperty.call(charMap, char)) {
                return charMap[char];
            }

            const normalized = char.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            if (normalized !== char) {
                return normalized;
            }

            return char;
        })
        .join('')
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
);

const escapeAttribute = (value) => value.replace(/(["\\\[\]])/g, '\\');

const findSourceInput = (root, name) => {
    if (!name) {
        return null;
    }

    return root.querySelector('[name="' + escapeAttribute(name) + '"]');
};

const attachSlugBehavior = (slugInput) => {
    if (slugInput.dataset.aveSlugInit === '1') {
        return;
    }

    const sourceName = slugInput.dataset.aveSlugSource;
    if (!sourceName) {
        return;
    }

    const form = slugInput.closest('form') || document;
    const sourceInput = findSourceInput(form, sourceName);

    if (!sourceInput) {
        return;
    }

    const updateAutoFlag = () => {
        slugInput.dataset.aveSlugAuto = slugInput.value.trim() === '' ? '1' : '0';
    };

    const requestOptions = {};

    if (slugInput.dataset.aveSlugSeparator) {
        requestOptions.separator = slugInput.dataset.aveSlugSeparator;
    }

    if (slugInput.dataset.aveSlugLanguage) {
        requestOptions.language = slugInput.dataset.aveSlugLanguage;
    }

    const generateSlugIfNeeded = async () => {
        if (slugInput.dataset.aveSlugAuto !== '1') {
            return;
        }

        const sourceValue = (sourceInput.value || '').trim();

        if (sourceValue === '') {
            return;
        }

        let generated = '';

        try {
            generated = await requestSlug(sourceValue, requestOptions);
        } catch (error) {
            generated = slugifyLocal(sourceValue);
        }

        if (!generated) {
            generated = slugifyLocal(sourceValue);
        }

        if (!generated) {
            return;
        }

        slugInput.value = generated;
        slugInput.dispatchEvent(new Event('input', { bubbles: true }));
        updateAutoFlag();
    };

    slugInput.addEventListener('input', updateAutoFlag);
    slugInput.addEventListener('focus', () => {
        if (slugInput.value.trim() === '') {
            slugInput.dataset.aveSlugAuto = '1';
        }

        void generateSlugIfNeeded();
    });

    updateAutoFlag();
    slugInput.dataset.aveSlugInit = '1';
};

export default function initSlugFields(root = document) {
    const slugInputs = root.querySelectorAll('[data-ave-slug-source]');
    if (!slugInputs.length) {
        return;
    }

    slugInputs.forEach(attachSlugBehavior);
}


