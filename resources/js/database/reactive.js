/**
 * Lightweight reactive state management library
 * Based on ES6 Proxy for reactive data binding
 *
 * This is a general-purpose library intended for use across multiple modules.
 * Some methods (computed, setByPath, batch, DOM utilities) are not currently
 * used by Database Manager but are kept for future functionality.
 */

export class Reactive {
    constructor(data = {}) {
        this.subscribers = new Map()
        this.computedCache = new Map()
        this.computedDeps = new Map()

        this.state = this.createReactive(data, [])
    }

    /**
     * Create reactive proxy for object/array
     */
    createReactive(target, path = []) {
        if (!this.isObject(target)) {
            return target
        }

        // Handle arrays
        if (Array.isArray(target)) {
            return this.createReactiveArray(target, path)
        }

        // Handle objects
        return new Proxy(target, {
            get: (obj, prop) => {
                // Track dependency for computed properties
                this.trackDependency(path.concat(prop))

                const value = obj[prop]

                // Deep reactivity - wrap nested objects
                if (this.isObject(value)) {
                    return this.createReactive(value, path.concat(prop))
                }

                return value
            },

            set: (obj, prop, value) => {
                const oldValue = obj[prop]

                if (oldValue === value) {
                    return true
                }

                obj[prop] = value

                // Notify subscribers
                this.notify(path.concat(prop))

                return true
            },

            deleteProperty: (obj, prop) => {
                if (prop in obj) {
                    delete obj[prop]
                    this.notify(path.concat(prop))
                }
                return true
            }
        })
    }

    /**
     * Create reactive array with method interception
     */
    createReactiveArray(target, path) {
        const reactive = this

        const arrayMethods = ['push', 'pop', 'shift', 'unshift', 'splice', 'sort', 'reverse']

        const proxyArray = new Proxy(target, {
            get(arr, prop) {
                reactive.trackDependency(path.concat(prop))

                // Intercept array methods
                if (arrayMethods.includes(prop)) {
                    return function(...args) {
                        const result = Array.prototype[prop].apply(arr, args)
                        reactive.notify(path)
                        return result
                    }
                }

                const value = arr[prop]

                if (reactive.isObject(value)) {
                    return reactive.createReactive(value, path.concat(prop))
                }

                return value
            },

            set(arr, prop, value) {
                const oldValue = arr[prop]

                if (oldValue === value) {
                    return true
                }

                arr[prop] = value
                reactive.notify(path.concat(prop))

                return true
            },

            deleteProperty(arr, prop) {
                if (prop in arr) {
                    delete arr[prop]
                    reactive.notify(path)
                }
                return true
            }
        })

        return proxyArray
    }

    /**
     * Subscribe to state changes
     */
    watch(pathOrCallback, callback = null) {
        if (typeof pathOrCallback === 'function') {
            // Watch entire state
            callback = pathOrCallback
            pathOrCallback = []
        }

        const path = Array.isArray(pathOrCallback) ? pathOrCallback : pathOrCallback.split('.')
        const key = this.pathToKey(path)

        if (!this.subscribers.has(key)) {
            this.subscribers.set(key, new Set())
        }

        this.subscribers.get(key).add(callback)

        // Return unsubscribe function
        return () => {
            const subs = this.subscribers.get(key)
            if (subs) {
                subs.delete(callback)
            }
        }
    }

    /**
     * Create computed property
     */
    computed(fn) {
        const computedId = Symbol('computed')

        return () => {
            if (this.computedCache.has(computedId)) {
                return this.computedCache.get(computedId)
            }

            // Track dependencies during computation
            this.currentComputed = computedId
            const value = fn()
            this.currentComputed = null

            this.computedCache.set(computedId, value)

            return value
        }
    }

    /**
     * Track dependency for computed properties
     */
    trackDependency(path) {
        if (this.currentComputed) {
            if (!this.computedDeps.has(this.currentComputed)) {
                this.computedDeps.set(this.currentComputed, new Set())
            }
            this.computedDeps.get(this.currentComputed).add(this.pathToKey(path))
        }
    }

    /**
     * Notify subscribers about changes
     */
    notify(path) {
        const key = this.pathToKey(path)

        // Notify exact path subscribers
        const exactSubs = this.subscribers.get(key)
        if (exactSubs) {
            exactSubs.forEach(callback => callback(this.getByPath(path), path))
        }

        // Notify parent path subscribers (e.g., 'columns' when 'columns.0.name' changes)
        for (let i = path.length - 1; i >= 0; i--) {
            const parentPath = path.slice(0, i)
            const parentKey = this.pathToKey(parentPath)
            const parentSubs = this.subscribers.get(parentKey)

            if (parentSubs) {
                parentSubs.forEach(callback => callback(this.getByPath(parentPath), parentPath))
            }
        }

        // Notify root subscribers (watching entire state)
        const rootSubs = this.subscribers.get('')
        if (rootSubs) {
            rootSubs.forEach(callback => callback(this.state, []))
        }

        // Invalidate computed cache
        this.invalidateComputed(key)
    }

    /**
     * Invalidate computed properties that depend on changed path
     */
    invalidateComputed(changedKey) {
        this.computedDeps.forEach((deps, computedId) => {
            if (deps.has(changedKey)) {
                this.computedCache.delete(computedId)
            }
        })
    }

    /**
     * Get value by path
     */
    getByPath(path) {
        return path.reduce((obj, key) => obj?.[key], this.state)
    }

    /**
     * Set value by path
     */
    setByPath(path, value) {
        if (path.length === 0) {
            this.state = this.createReactive(value, [])
            return
        }

        const lastKey = path[path.length - 1]
        const parent = path.slice(0, -1).reduce((obj, key) => obj[key], this.state)

        if (parent) {
            parent[lastKey] = value
        }
    }

    /**
     * Convert path array to string key
     */
    pathToKey(path) {
        return Array.isArray(path) ? path.join('.') : path
    }

    /**
     * Check if value is object or array
     */
    isObject(value) {
        return value !== null && (typeof value === 'object' || Array.isArray(value))
    }

    /**
     * Batch updates (multiple changes, single notification)
     */
    batch(callback) {
        this.batching = true
        this.batchedPaths = new Set()

        callback()

        this.batching = false

        // Notify all batched paths
        this.batchedPaths.forEach(path => {
            this.notify(path.split('.'))
        })

        this.batchedPaths.clear()
    }
}

/**
 * DOM utilities for rendering
 */
class DOM {
    /**
     * Create element with props and children
     */
    static create(tag, props = {}, ...children) {
        const element = document.createElement(tag)

        // Set props
        Object.keys(props).forEach(key => {
            if (key === 'className') {
                element.className = props[key]
            } else if (key === 'style' && typeof props[key] === 'object') {
                Object.assign(element.style, props[key])
            } else if (key.startsWith('on') && typeof props[key] === 'function') {
                const event = key.substring(2).toLowerCase()
                element.addEventListener(event, props[key])
            } else if (key === 'dataset' && typeof props[key] === 'object') {
                Object.assign(element.dataset, props[key])
            } else {
                element.setAttribute(key, props[key])
            }
        })

        // Append children
        children.flat(Infinity).forEach(child => {
            if (child !== null && child !== undefined && child !== false) {
                if (typeof child === 'string' || typeof child === 'number') {
                    element.appendChild(document.createTextNode(child))
                } else {
                    element.appendChild(child)
                }
            }
        })

        return element
    }

    /**
     * Replace element content
     */
    static render(container, element) {
        if (typeof container === 'string') {
            container = document.querySelector(container)
        }

        if (!container) {
            console.error('Container not found')
            return
        }

        container.innerHTML = ''

        if (Array.isArray(element)) {
            element.forEach(el => container.appendChild(el))
        } else {
            container.appendChild(element)
        }
    }

    /**
     * Update element if different
     */
    static update(oldElement, newElement) {
        if (oldElement.tagName !== newElement.tagName) {
            oldElement.replaceWith(newElement)
            return
        }

        // Update attributes
        Array.from(oldElement.attributes).forEach(attr => {
            if (!newElement.hasAttribute(attr.name)) {
                oldElement.removeAttribute(attr.name)
            }
        })

        Array.from(newElement.attributes).forEach(attr => {
            oldElement.setAttribute(attr.name, attr.value)
        })

        // Update children
        const oldChildren = Array.from(oldElement.childNodes)
        const newChildren = Array.from(newElement.childNodes)

        // Remove extra old children
        oldChildren.slice(newChildren.length).forEach(child => child.remove())

        // Update/add children
        newChildren.forEach((newChild, i) => {
            if (i >= oldChildren.length) {
                oldElement.appendChild(newChild.cloneNode(true))
            } else {
                const oldChild = oldChildren[i]

                if (oldChild.nodeType === Node.TEXT_NODE && newChild.nodeType === Node.TEXT_NODE) {
                    if (oldChild.textContent !== newChild.textContent) {
                        oldChild.textContent = newChild.textContent
                    }
                } else if (oldChild.nodeType === Node.ELEMENT_NODE && newChild.nodeType === Node.ELEMENT_NODE) {
                    DOM.update(oldChild, newChild)
                } else {
                    oldChild.replaceWith(newChild.cloneNode(true))
                }
            }
        })
    }
}

// Export to global window object
window.Reactive = Reactive
window.DOM = DOM
