export default function initNavbar() {
    const profileDropdown = document.querySelector('.ave-navbar__right .dropdown.profile');
    const profileToggle = profileDropdown?.querySelector('.dropdown-toggle');
    const profileMenu = profileDropdown?.querySelector('.dropdown-menu');

    if (!profileDropdown || !profileToggle || !profileMenu) {
        return;
    }

    const closeProfileMenu = () => {
        profileDropdown.classList.remove('open');
        profileToggle.setAttribute('aria-expanded', 'false');
    };

    profileToggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        const isOpen = profileDropdown.classList.toggle('open');
        profileToggle.setAttribute('aria-expanded', String(isOpen));
    });

    document.addEventListener('click', (event) => {
        if (!profileDropdown.contains(event.target)) {
            closeProfileMenu();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeProfileMenu();
        }
    });
}


