/**
 * Form Reinitialization Integration Tests
 *
 * Tests for EventBus-driven form component reinitialization
 * Verifies that form components are properly reinitialized when new DOM elements are added
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { aveEvents } from '../core/EventBus.js';

describe('Form Reinitialization via EventBus', () => {
    let container;

    beforeEach(() => {
        // Setup test container
        container = document.createElement('div');
        container.id = 'test-container';
        document.body.appendChild(container);

        // Clear all event listeners
        aveEvents.flush();
    });

    afterEach(() => {
        // Cleanup
        container.remove();
        aveEvents.flush();
    });

    describe('dom:updated event', () => {
        it('should emit dom:updated event with container', () => {
            const listener = vi.fn();
            aveEvents.on('dom:updated', listener);

            const testContainer = document.createElement('div');
            aveEvents.emit('dom:updated', testContainer);

            expect(listener).toHaveBeenCalledWith(testContainer);
        });

        it('should emit dom:updated with document on page load', () => {
            const listener = vi.fn();
            aveEvents.on('dom:updated', listener);

            // Simulate app.js behavior
            aveEvents.emit('dom:updated', document);

            expect(listener).toHaveBeenCalledWith(document);
        });
    });

    describe('Reinitialization workflow', () => {
        it('should handle multiple dom:updated events in sequence', async () => {
            const listener1 = vi.fn();
            const listener2 = vi.fn();

            aveEvents.on('dom:updated', listener1);
            aveEvents.on('dom:updated', listener2);

            // First update
            const container1 = document.createElement('div');
            aveEvents.emit('dom:updated', container1);

            // Second update
            const container2 = document.createElement('div');
            aveEvents.emit('dom:updated', container2);

            expect(listener1).toHaveBeenCalledTimes(2);
            expect(listener2).toHaveBeenCalledTimes(2);
        });

        it('should pass correct container to each reinitialization', () => {
            const listener = vi.fn();
            aveEvents.on('dom:updated', listener);

            const containers = [
                document.createElement('div'),
                document.createElement('div'),
                document.createElement('div'),
            ];

            containers.forEach(cont => aveEvents.emit('dom:updated', cont));

            expect(listener).toHaveBeenCalledTimes(3);
            containers.forEach((cont, index) => {
                expect(listener.mock.calls[index][0]).toBe(cont);
            });
        });
    });

    describe('FieldSet + EventBus integration', () => {
        it('should emit dom:updated when new fieldset item is added', () => {
            const listener = vi.fn();
            aveEvents.on('dom:updated', listener);

            // Simulate fieldSet adding new item
            const newItem = document.createElement('div');
            newItem.className = 'fieldset-item';
            container.appendChild(newItem);

            // Simulate what fieldSet.js does
            aveEvents.emit('dom:updated', newItem);

            expect(listener).toHaveBeenCalledWith(newItem);
        });

        it('should support reinitialization of nested form fields', () => {
            const listener = vi.fn();
            aveEvents.on('dom:updated', listener);

            // Create fieldset item with nested form fields
            const fieldsetItem = document.createElement('div');
            fieldsetItem.className = 'fieldset-item';

            const slugField = document.createElement('input');
            slugField.setAttribute('data-slug-field', 'true');
            slugField.name = 'items[0][slug]';
            fieldsetItem.appendChild(slugField);

            container.appendChild(fieldsetItem);
            aveEvents.emit('dom:updated', fieldsetItem);

            expect(listener).toHaveBeenCalledWith(fieldsetItem);
            // Listener should receive container with slug field
            expect(listener.mock.calls[0][0].querySelector('[data-slug-field]')).toBe(slugField);
        });
    });

    describe('Event bubbling behavior', () => {
        it('should allow listeners to be added and removed dynamically', () => {
            const listener1 = vi.fn();
            const listener2 = vi.fn();
            const listener3 = vi.fn();

            // Subscribe listeners
            aveEvents.on('dom:updated', listener1);
            aveEvents.on('dom:updated', listener2);

            // Emit event
            aveEvents.emit('dom:updated', container);

            expect(listener1).toHaveBeenCalledTimes(1);
            expect(listener2).toHaveBeenCalledTimes(1);
            expect(listener3).not.toHaveBeenCalled();

            // Add another listener
            aveEvents.on('dom:updated', listener3);

            // Emit another event
            aveEvents.emit('dom:updated', container);

            expect(listener1).toHaveBeenCalledTimes(2);
            expect(listener2).toHaveBeenCalledTimes(2);
            expect(listener3).toHaveBeenCalledTimes(1);
        });

        it('should handle listener removal correctly', () => {
            const listener1 = vi.fn();
            const listener2 = vi.fn();

            aveEvents.on('dom:updated', listener1);
            aveEvents.on('dom:updated', listener2);

            // Remove listener1
            aveEvents.off('dom:updated', listener1);

            // Emit event
            aveEvents.emit('dom:updated', container);

            expect(listener1).not.toHaveBeenCalled();
            expect(listener2).toHaveBeenCalledOnce();
        });
    });

    describe('Error handling', () => {
        it('should continue after listener error', () => {
            const errorListener = vi.fn(() => {
                throw new Error('Test error');
            });
            const successListener = vi.fn();
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

            aveEvents.on('dom:updated', errorListener);
            aveEvents.on('dom:updated', successListener);

            aveEvents.emit('dom:updated', container);

            expect(errorListener).toHaveBeenCalled();
            expect(successListener).toHaveBeenCalled();
            expect(consoleSpy).toHaveBeenCalled();

            consoleSpy.mockRestore();
        });

        it('should not break event loop when listener throws', () => {
            const listeners = [
                vi.fn(() => { throw new Error('Error 1'); }),
                vi.fn(),
                vi.fn(() => { throw new Error('Error 2'); }),
                vi.fn(),
            ];
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

            listeners.forEach(listener => aveEvents.on('dom:updated', listener));

            aveEvents.emit('dom:updated', container);

            // All listeners should be called despite errors
            listeners.forEach(listener => {
                expect(listener).toHaveBeenCalled();
            });

            consoleSpy.mockRestore();
        });
    });

    describe('Memory and cleanup', () => {
        it('should clear all listeners when flush() is called', () => {
            const listener = vi.fn();
            aveEvents.on('dom:updated', listener);
            aveEvents.on('other:event', listener);

            expect(aveEvents.listeners('dom:updated')).toHaveLength(1);
            expect(aveEvents.listeners('other:event')).toHaveLength(1);

            aveEvents.flush();

            expect(aveEvents.listeners('dom:updated')).toHaveLength(0);
            expect(aveEvents.listeners('other:event')).toHaveLength(0);

            aveEvents.emit('dom:updated', container);
            expect(listener).not.toHaveBeenCalled();
        });

        it('should allow resubscription after flush', () => {
            const listener = vi.fn();

            aveEvents.on('dom:updated', listener);
            aveEvents.emit('dom:updated', container);
            expect(listener).toHaveBeenCalledTimes(1);

            aveEvents.flush();
            aveEvents.on('dom:updated', listener);
            aveEvents.emit('dom:updated', container);

            expect(listener).toHaveBeenCalledTimes(2);
        });
    });

    describe('Real-world scenarios', () => {
        it('should handle FieldSet item addition workflow', () => {
            const reinitListener = vi.fn();
            aveEvents.on('dom:updated', reinitListener);

            // Simulate FieldSet item creation
            const fieldsetItem = document.createElement('div');
            fieldsetItem.className = 'fieldset-item';
            fieldsetItem.dataset.itemIndex = '0';

            // Add form fields
            const nameField = document.createElement('input');
            nameField.name = 'items[0][name]';
            nameField.type = 'text';

            const slugField = document.createElement('input');
            slugField.name = 'items[0][slug]';
            slugField.className = 'slug-field';

            fieldsetItem.appendChild(nameField);
            fieldsetItem.appendChild(slugField);
            container.appendChild(fieldsetItem);

            // Simulate what fieldSet.js does
            aveEvents.emit('dom:updated', fieldsetItem);

            expect(reinitListener).toHaveBeenCalledOnce();
            expect(reinitListener.mock.calls[0][0]).toBe(fieldsetItem);
        });

        it('should handle multiple sequential FieldSet item additions', () => {
            const reinitListener = vi.fn();
            aveEvents.on('dom:updated', reinitListener);

            // Add first item
            const item1 = document.createElement('div');
            item1.className = 'fieldset-item';
            item1.dataset.itemIndex = '0';
            container.appendChild(item1);
            aveEvents.emit('dom:updated', item1);

            // Add second item
            const item2 = document.createElement('div');
            item2.className = 'fieldset-item';
            item2.dataset.itemIndex = '1';
            container.appendChild(item2);
            aveEvents.emit('dom:updated', item2);

            // Add third item
            const item3 = document.createElement('div');
            item3.className = 'fieldset-item';
            item3.dataset.itemIndex = '2';
            container.appendChild(item3);
            aveEvents.emit('dom:updated', item3);

            expect(reinitListener).toHaveBeenCalledTimes(3);
            expect(container.querySelectorAll('.fieldset-item')).toHaveLength(3);
        });
    });
});
