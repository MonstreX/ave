import initTabs from './tabs';
import initAlerts from './alerts';
import initModals from './modals';
import initAccordion from './accordion';
import initCopyToClipboard from './copy';
import initToastSystem from './toast';

export default function initUI() {
    initToastSystem();
    initTabs();
    initAlerts();
    initModals();
    initAccordion();
    initCopyToClipboard();
}
