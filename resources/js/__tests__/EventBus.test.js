/**
 * EventBus Tests
 *
 * Tests for the global event bus system used for component reinitialization
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import { EventBus, aveEvents } from '../core/EventBus.js';

describe('EventBus', () => {
    let eventBus;

    beforeEach(() => {
        eventBus = new EventBus();
    });

    describe('on()', () => {
        it('should subscribe to a single event', () => {
            const listener = vi.fn();
            eventBus.on('test:event', listener);

            expect(eventBus.listeners('test:event')).toHaveLength(1);
            expect(eventBus.listeners('test:event')[0]).toBe(listener);
        });

        it('should subscribe to multiple events at once', () => {
            const listener = vi.fn();
            eventBus.on(['event1', 'event2'], listener);

            expect(eventBus.listeners('event1')).toHaveLength(1);
            expect(eventBus.listeners('event2')).toHaveLength(1);
        });

        it('should allow multiple listeners for same event', () => {
            const listener1 = vi.fn();
            const listener2 = vi.fn();

            eventBus.on('test:event', listener1);
            eventBus.on('test:event', listener2);

            expect(eventBus.listeners('test:event')).toHaveLength(2);
        });
    });

    describe('emit()', () => {
        it('should call all listeners for an event', () => {
            const listener1 = vi.fn();
            const listener2 = vi.fn();

            eventBus.on('test:event', listener1);
            eventBus.on('test:event', listener2);

            eventBus.emit('test:event', { data: 'test' });

            expect(listener1).toHaveBeenCalledOnce();
            expect(listener1).toHaveBeenCalledWith({ data: 'test' });
            expect(listener2).toHaveBeenCalledOnce();
            expect(listener2).toHaveBeenCalledWith({ data: 'test' });
        });

        it('should pass data to listeners', () => {
            const listener = vi.fn();
            const testData = { id: 1, name: 'test' };

            eventBus.on('test:event', listener);
            eventBus.emit('test:event', testData);

            expect(listener).toHaveBeenCalledWith(testData);
        });

        it('should not call listeners for unregistered events', () => {
            const listener = vi.fn();

            eventBus.on('test:event', listener);
            eventBus.emit('other:event', {});

            expect(listener).not.toHaveBeenCalled();
        });

        it('should handle missing listeners gracefully', () => {
            expect(() => {
                eventBus.emit('nonexistent:event', {});
            }).not.toThrow();
        });

        it('should catch errors in listeners and continue', () => {
            const listener1 = vi.fn(() => {
                throw new Error('Test error');
            });
            const listener2 = vi.fn();
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

            eventBus.on('test:event', listener1);
            eventBus.on('test:event', listener2);

            eventBus.emit('test:event', {});

            expect(listener1).toHaveBeenCalled();
            expect(listener2).toHaveBeenCalled();
            expect(consoleSpy).toHaveBeenCalled();

            consoleSpy.mockRestore();
        });
    });

    describe('once()', () => {
        it('should call listener only once', () => {
            const listener = vi.fn();

            eventBus.once('test:event', listener);

            eventBus.emit('test:event', { data: 1 });
            eventBus.emit('test:event', { data: 2 });

            expect(listener).toHaveBeenCalledOnce();
            expect(listener).toHaveBeenCalledWith({ data: 1 });
        });

        it('should support multiple events for once', () => {
            const listener = vi.fn();

            eventBus.once(['event1', 'event2'], listener);

            eventBus.emit('event1', {});

            expect(listener).toHaveBeenCalledOnce();
            expect(eventBus.hasListeners('event1')).toBe(false);
        });
    });

    describe('off()', () => {
        it('should unsubscribe from event', () => {
            const listener = vi.fn();

            eventBus.on('test:event', listener);
            expect(eventBus.listeners('test:event')).toHaveLength(1);

            eventBus.off('test:event', listener);
            expect(eventBus.listeners('test:event')).toHaveLength(0);

            eventBus.emit('test:event', {});
            expect(listener).not.toHaveBeenCalled();
        });

        it('should unsubscribe from multiple events', () => {
            const listener = vi.fn();

            eventBus.on(['event1', 'event2'], listener);
            eventBus.off(['event1', 'event2'], listener);

            expect(eventBus.listeners('event1')).toHaveLength(0);
            expect(eventBus.listeners('event2')).toHaveLength(0);
        });

        it('should only remove specified listener', () => {
            const listener1 = vi.fn();
            const listener2 = vi.fn();

            eventBus.on('test:event', listener1);
            eventBus.on('test:event', listener2);

            eventBus.off('test:event', listener1);

            expect(eventBus.listeners('test:event')).toHaveLength(1);
            expect(eventBus.listeners('test:event')[0]).toBe(listener2);
        });
    });

    describe('hasListeners()', () => {
        it('should return true if event has listeners', () => {
            eventBus.on('test:event', () => {});
            expect(eventBus.hasListeners('test:event')).toBe(true);
        });

        it('should return false if event has no listeners', () => {
            expect(eventBus.hasListeners('test:event')).toBe(false);
        });

        it('should return false after unsubscribing all listeners', () => {
            const listener = vi.fn();
            eventBus.on('test:event', listener);
            eventBus.off('test:event', listener);

            expect(eventBus.hasListeners('test:event')).toBe(false);
        });
    });

    describe('flush()', () => {
        it('should remove all listeners for specific event', () => {
            eventBus.on('test:event', () => {});
            eventBus.on('test:event', () => {});
            eventBus.on('other:event', () => {});

            eventBus.flush('test:event');

            expect(eventBus.hasListeners('test:event')).toBe(false);
            expect(eventBus.hasListeners('other:event')).toBe(true);
        });

        it('should remove all listeners for all events', () => {
            eventBus.on('event1', () => {});
            eventBus.on('event2', () => {});
            eventBus.on('event3', () => {});

            eventBus.flush();

            expect(eventBus.hasListeners('event1')).toBe(false);
            expect(eventBus.hasListeners('event2')).toBe(false);
            expect(eventBus.hasListeners('event3')).toBe(false);
        });
    });

    describe('listeners()', () => {
        it('should return array of listeners for event', () => {
            const listener1 = vi.fn();
            const listener2 = vi.fn();

            eventBus.on('test:event', listener1);
            eventBus.on('test:event', listener2);

            const listeners = eventBus.listeners('test:event');

            expect(Array.isArray(listeners)).toBe(true);
            expect(listeners).toHaveLength(2);
            expect(listeners).toContain(listener1);
            expect(listeners).toContain(listener2);
        });

        it('should return empty array for non-existent event', () => {
            const listeners = eventBus.listeners('nonexistent:event');

            expect(Array.isArray(listeners)).toBe(true);
            expect(listeners).toHaveLength(0);
        });
    });
});

describe('Global aveEvents', () => {
    beforeEach(() => {
        aveEvents.flush();
    });

    it('should be a singleton EventBus instance', () => {
        expect(aveEvents).toBeDefined();
        expect(aveEvents).toBeInstanceOf(EventBus);
    });

    it('should support dom:updated event', () => {
        const listener = vi.fn();
        aveEvents.on('dom:updated', listener);

        const container = document.createElement('div');
        aveEvents.emit('dom:updated', container);

        expect(listener).toHaveBeenCalledWith(container);
    });

    it('should expose listeners method', () => {
        aveEvents.on('test:event', () => {});

        const listeners = aveEvents.listeners('test:event');
        expect(Array.isArray(listeners)).toBe(true);
        expect(listeners).toHaveLength(1);
    });
});
