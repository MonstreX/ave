/**
 * Global EventBus
 *
 * Provides a simple pub/sub event system for decoupling form components
 * and triggering reinitialization across the application.
 *
 * Events:
 * - 'dom:updated' — DOM element added/updated (contains new elements needing initialization)
 * - 'ajax:loaded' — Data loaded via AJAX
 * - 'form:submitted' — Form submitted
 * - 'modal:opened' — Modal dialog opened
 *
 * @example
 * // Subscribe to events
 * aveEvents.on('dom:updated', (container) => {
 *     initSlugFields(container);
 *     initEditors(container);
 * });
 *
 * // Emit events
 * aveEvents.emit('dom:updated', newContainer);
 */

export class EventBus {
    constructor() {
        this.listeners = {};
    }

    /**
     * Subscribe to an event
     * @param {string|string[]} events - Event name(s)
     * @param {Function} listener - Callback function
     */
    on(events, listener) {
        if (typeof events === 'string') {
            events = [events];
        }

        events.forEach(event => {
            if (!this.listeners[event]) {
                this.listeners[event] = [];
            }
            this.listeners[event].push(listener);
        });
    }

    /**
     * Subscribe to event only once
     * @param {string|string[]} events - Event name(s)
     * @param {Function} listener - Callback function
     */
    once(events, listener) {
        if (typeof events === 'string') {
            events = [events];
        }

        const wrapper = (...args) => {
            listener(...args);
            this.off(events, wrapper);
        };

        this.on(events, wrapper);
    }

    /**
     * Unsubscribe from event
     * @param {string|string[]} events - Event name(s)
     * @param {Function} listener - Callback function
     */
    off(events, listener) {
        if (typeof events === 'string') {
            events = [events];
        }

        events.forEach(event => {
            if (!this.listeners[event]) return;
            this.listeners[event] = this.listeners[event].filter(l => l !== listener);
        });
    }

    /**
     * Emit event to all listeners
     * @param {string} event - Event name
     * @param {*} data - Event data passed to listeners
     */
    emit(event, data) {
        if (!this.listeners[event]) return;
        this.listeners[event].forEach(listener => {
            try {
                listener(data);
            } catch (error) {
                console.error(`Error in event listener for '${event}':`, error);
            }
        });
    }

    /**
     * Get all listeners for an event
     * @param {string} event - Event name
     * @returns {Function[]} Array of listener functions
     */
    listeners(event) {
        return this.listeners[event] || [];
    }

    /**
     * Check if event has listeners
     * @param {string} event - Event name
     * @returns {boolean}
     */
    hasListeners(event) {
        return (this.listeners[event] || []).length > 0;
    }

    /**
     * Remove all listeners for an event
     * @param {string} event - Event name
     */
    flush(event) {
        if (!event) {
            this.listeners = {};
        } else {
            this.listeners[event] = [];
        }
    }
}

/**
 * Global event bus instance
 * Exported to window.Ave.events for global access
 */
export const aveEvents = new EventBus();
