import PerfectScrollbar from 'perfect-scrollbar';
import 'perfect-scrollbar/css/perfect-scrollbar.css';

const STORAGE_KEY = 'ave.stickySidebar';

const getLocalStorage = () => {
    try {
        return window.localStorage;
    } catch (error) {
        return null;
    }
};

const preserveTransitions = (elements, callback) => {
    const cached = elements.map((element) => ({
        element,
        transition: element?.style.transition,
        webkit: element?.style.WebkitTransition,
        moz: element?.style.MozTransition,
    }));

    cached.forEach(({ element }) => {
        if (!element) {
            return;
        }
        element.style.WebkitTransition = 'none';
        element.style.MozTransition = 'none';
        element.style.transition = 'none';
    });

    callback();

    cached.forEach(({ element, transition, webkit, moz }) => {
        if (!element) {
            return;
        }
        element.style.WebkitTransition = webkit || '';
        element.style.MozTransition = moz || '';
        element.style.transition = transition || '';
    });
};

export default function initSidebar() {
    const appContainer = document.querySelector('.app');
    const sidebar = document.querySelector('.side-menu');
    const navbar = document.querySelector('.ave-navbar');
    const loader = document.getElementById('ave-loader');
    const hamburger = document.querySelector('.hamburger');
    const overlay = document.querySelector('.fadetoblack');
    const sidebarMenu =
        sidebar?.querySelector('[data-ave-menu="container"]') ||
        document.querySelector('.ave-sidebar__menu');

    if (!appContainer || !sidebar || !navbar || !hamburger) {
        return;
    }

    let sidebarScrollbar = null;

    const refreshScrollbar = () => {
        if (!sidebarScrollbar) {
            return;
        }

        window.requestAnimationFrame(() => {
            sidebarScrollbar?.update();
        });
    };

    const updateStickyPreference = (expanded) => {
        const storage = getLocalStorage();

        if (!storage) {
            return;
        }

        if (expanded) {
            storage.setItem(STORAGE_KEY, 'true');
            return;
        }

        storage.removeItem(STORAGE_KEY);
    };

    const setSidebarExpanded = (expanded, options = { suppressPersist: false }) => {
        appContainer.classList.toggle('expanded', expanded);
        hamburger.classList.toggle('is-active', expanded);

        if (!options.suppressPersist) {
            updateStickyPreference(expanded);
        }

        refreshScrollbar();
    };

    preserveTransitions([sidebar, appContainer, navbar], () => {
        const shouldExpand =
            window.innerWidth > 768 &&
            getLocalStorage()?.getItem(STORAGE_KEY) === 'true';

        if (!shouldExpand) {
            setSidebarExpanded(false, { suppressPersist: true });
            return;
        }

        appContainer.classList.add('expanded', 'no-animation');
        hamburger.classList.add('is-active', 'no-animation');

        if (loader) {
            loader.style.left = `${sidebar.clientWidth / 2}px`;
        }
    });

    if (overlay) {
        overlay.addEventListener('click', () => {
            setSidebarExpanded(false);
        });
    }

    hamburger.addEventListener('click', (event) => {
        event.preventDefault();
        const willExpand = !appContainer.classList.contains('expanded');
        setSidebarExpanded(willExpand);
    });

    if (sidebarMenu) {
        sidebarScrollbar = new PerfectScrollbar(sidebarMenu, {
            wheelPropagation: false,
            suppressScrollX: true,
        });

        window.addEventListener(
            'resize',
            () => {
                refreshScrollbar();
            },
            { passive: true }
        );
    }

    const submenuToggles = document.querySelectorAll('.ave-sidebar__toggle');

    const animateSubmenu = (submenu, shouldOpen) => {
        if (!submenu) {
            return;
        }

        submenu.removeAttribute('data-ave-animating');

        if (shouldOpen) {
            submenu.classList.add('ave-sidebar__submenu--open');
            submenu.style.maxHeight = '0px';

            requestAnimationFrame(() => {
                const targetHeight = submenu.scrollHeight;
                submenu.style.maxHeight = `${targetHeight}px`;
                refreshScrollbar();
            });

            const handleEnter = (event) => {
                if (event.propertyName !== 'max-height') {
                    return;
                }

                submenu.style.maxHeight = 'none';
                submenu.removeEventListener('transitionend', handleEnter);
                refreshScrollbar();
            };

            submenu.addEventListener('transitionend', handleEnter);

            return;
        }

        const currentHeight = submenu.scrollHeight;
        submenu.style.maxHeight = `${currentHeight}px`;

        requestAnimationFrame(() => {
            submenu.classList.remove('ave-sidebar__submenu--open');
            submenu.style.maxHeight = '0px';
            refreshScrollbar();
        });

        const handleLeave = (event) => {
            if (event.propertyName !== 'max-height') {
                return;
            }

            submenu.style.maxHeight = '0px';
            submenu.removeEventListener('transitionend', handleLeave);
            refreshScrollbar();
        };

        submenu.addEventListener('transitionend', handleLeave);
    };

    submenuToggles.forEach((toggleElement) => {
        const menuItem = toggleElement.closest('[data-ave-menu="item"]');
        const submenuId = toggleElement.getAttribute('data-ave-submenu');
        const submenu = submenuId ? document.getElementById(submenuId) : null;

        if (!menuItem || !submenu) {
            return;
        }

        const isInitiallyExpanded = toggleElement.getAttribute('aria-expanded') === 'true';
        menuItem.classList.toggle('ave-sidebar__item--expanded', isInitiallyExpanded);
        submenu.classList.toggle('ave-sidebar__submenu--open', isInitiallyExpanded);
        submenu.style.maxHeight = isInitiallyExpanded ? 'none' : '0px';
        toggleElement.setAttribute('aria-expanded', String(isInitiallyExpanded));
        refreshScrollbar();

        toggleElement.addEventListener('click', (event) => {
            event.preventDefault();

            const willExpand = toggleElement.getAttribute('aria-expanded') !== 'true';

            toggleElement.setAttribute('aria-expanded', String(willExpand));
            menuItem.classList.toggle('ave-sidebar__item--expanded', willExpand);
            animateSubmenu(submenu, willExpand);
        });
    });
}



