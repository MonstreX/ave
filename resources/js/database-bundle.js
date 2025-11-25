/**
 * Database Manager Bundle
 * Combined reactive library and table editor
 */

// ===== REACTIVE LIBRARY =====
class Reactive {
    constructor(data = {}) {
        this.subscribers = new Map()
        this.computedCache = new Map()
        this.computedDeps = new Map()

        this.state = this.createReactive(data, [])
    }

    createReactive(target, path = []) {
        if (!this.isObject(target)) {
            return target
        }

        if (Array.isArray(target)) {
            return this.createReactiveArray(target, path)
        }

        return new Proxy(target, {
            get: (obj, prop) => {
                this.trackDependency(path.concat(prop))
                const value = obj[prop]
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

    createReactiveArray(target, path) {
        const reactive = this
        const arrayMethods = ['push', 'pop', 'shift', 'unshift', 'splice', 'sort', 'reverse']

        const proxyArray = new Proxy(target, {
            get(arr, prop) {
                reactive.trackDependency(path.concat(prop))

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

    watch(pathOrCallback, callback = null) {
        if (typeof pathOrCallback === 'function') {
            callback = pathOrCallback
            pathOrCallback = []
        }

        const path = Array.isArray(pathOrCallback) ? pathOrCallback : pathOrCallback.split('.')
        const key = this.pathToKey(path)

        if (!this.subscribers.has(key)) {
            this.subscribers.set(key, new Set())
        }

        this.subscribers.get(key).add(callback)

        return () => {
            const subs = this.subscribers.get(key)
            if (subs) {
                subs.delete(callback)
            }
        }
    }

    notify(path) {
        const key = this.pathToKey(path)
        const exactSubs = this.subscribers.get(key)
        if (exactSubs) {
            exactSubs.forEach(callback => callback(this.getByPath(path), path))
        }

        for (let i = path.length - 1; i >= 0; i--) {
            const parentPath = path.slice(0, i)
            const parentKey = this.pathToKey(parentPath)
            const parentSubs = this.subscribers.get(parentKey)
            if (parentSubs) {
                parentSubs.forEach(callback => callback(this.getByPath(parentPath), parentPath))
            }
        }

        const rootSubs = this.subscribers.get('')
        if (rootSubs) {
            rootSubs.forEach(callback => callback(this.state, []))
        }
    }

    trackDependency(path) {
        // Stub for computed properties support
    }

    getByPath(path) {
        return path.reduce((obj, key) => obj?.[key], this.state)
    }

    pathToKey(path) {
        return Array.isArray(path) ? path.join('.') : path
    }

    isObject(value) {
        return value !== null && (typeof value === 'object' || Array.isArray(value))
    }
}

// Export to window
window.Reactive = Reactive

// ===== DATABASE TABLE EDITOR =====
class DatabaseTableEditor {
    constructor(config) {
        this.config = config
        this.translations = config.translations

        console.log('DatabaseTableEditor init')
        console.log('config.table:', config.table)
        console.log('config.oldTable:', config.oldTable)

        this.state = new Reactive({
            table: config.oldTable || config.table,
            errors: {},
            isDirty: false
        })

        console.log('Reactive state initialized')
        console.log('state.table:', this.state.state.table)
        console.log('state.table.columns:', this.state.state.table.columns)

        this.state.watch('table', () => {
            this.state.state.isDirty = true
            this.validateTable()
            this.render()
        })

        window.dbConfig.table = this.state.state.table
        this.init()
    }

    init() {
        this.render()
        this.attachEventListeners()
        this.validateTable()
    }

    attachEventListeners() {
        document.getElementById('btn-add-column')?.addEventListener('click', () => {
            this.addColumn()
        })

        document.getElementById('btn-add-timestamps')?.addEventListener('click', () => {
            this.addTimestamps()
        })

        document.getElementById('btn-add-softdeletes')?.addEventListener('click', () => {
            this.addSoftDeletes()
        })

        document.getElementById('database-form')?.addEventListener('submit', (e) => {
            if (!this.validateTable()) {
                e.preventDefault()
                toastr.error('Please fix validation errors before saving')
                return false
            }
            document.getElementById('table-data').value = JSON.stringify(this.state.state.table)
            window.dbConfig.table = this.state.state.table
        })
    }

    render() {
        const container = document.getElementById('columns-container')
        if (!container) return

        const columns = this.state.state.table.columns || []
        const noColumnsMsg = document.getElementById('no-columns-message')

        if (columns.length === 0) {
            noColumnsMsg.style.display = 'block'
            return
        }

        noColumnsMsg.style.display = 'none'
        container.innerHTML = ''

        columns.forEach((column, index) => {
            const columnElement = this.renderColumn(column, index)
            container.appendChild(columnElement)
        })
    }

    renderColumn(column, index) {
        const row = document.createElement('div')
        row.className = 'db-column-row'
        row.dataset.index = index

        const columnErrors = this.state.state.errors[`columns.${index}`] || {}
        if (Object.keys(columnErrors).length > 0) {
            row.classList.add('has-error')
        }

        const header = document.createElement('div')
        header.className = 'db-column-header'

        const title = document.createElement('div')
        title.className = 'db-column-title'
        title.textContent = column.name || `Column ${index + 1}`

        const actions = document.createElement('div')
        actions.className = 'db-column-actions'

        const removeBtn = document.createElement('button')
        removeBtn.type = 'button'
        removeBtn.className = 'btn-remove-column'
        removeBtn.innerHTML = '<i class="voyager-trash"></i>'
        removeBtn.title = 'Remove column'
        removeBtn.addEventListener('click', () => this.removeColumn(index))

        actions.appendChild(removeBtn)
        header.appendChild(title)
        header.appendChild(actions)

        const body = document.createElement('div')
        body.className = 'db-column-body'

        body.appendChild(this.createField('text', 'name', this.translations.field, column.name, index, {
            required: true,
            pattern: this.config.identifierRegex
        }))

        body.appendChild(this.createTypeSelect(column, index))

        const type = this.getTypeInfo(column.type)
        if (type && type.requiresLength) {
            body.appendChild(this.createField('text', 'length', this.translations.length, column.length, index, {
                placeholder: type.defaultLength || ''
            }))
        }

        body.appendChild(this.createField('text', 'default', this.translations.default, column.default, index))

        body.appendChild(this.createCheckbox('notnull', this.translations.notNull, column.notnull, index))

        if (type && type.category === 'numbers') {
            body.appendChild(this.createCheckbox('unsigned', this.translations.unsigned, column.unsigned, index))
        }

        if (type && (type.name === 'integer' || type.name === 'bigint' || type.name === 'smallint')) {
            body.appendChild(this.createCheckbox('autoincrement', this.translations.autoIncrement, column.autoincrement, index))
        }

        body.appendChild(this.createIndexSelect(column, index))

        if (column.composite) {
            const warning = document.createElement('div')
            warning.className = 'db-column-warning'
            warning.innerHTML = `<i class="voyager-warning"></i> ${this.translations.compositeWarning}`
            body.appendChild(warning)
        }

        if (Object.keys(columnErrors).length > 0) {
            const errorDiv = document.createElement('div')
            errorDiv.className = 'alert alert-danger'
            errorDiv.style.marginTop = '10px'
            errorDiv.style.gridColumn = '1 / -1'
            errorDiv.innerHTML = Object.values(columnErrors).join('<br>')
            body.appendChild(errorDiv)
        }

        row.appendChild(header)
        row.appendChild(body)

        return row
    }

    createField(type, name, label, value, columnIndex, attrs = {}) {
        const group = document.createElement('div')
        group.className = 'db-field-group'

        const labelEl = document.createElement('label')
        labelEl.textContent = label
        group.appendChild(labelEl)

        const input = document.createElement('input')
        input.type = type
        input.value = value || ''
        input.className = 'form-control'

        Object.keys(attrs).forEach(key => {
            input.setAttribute(key, attrs[key])
        })

        input.addEventListener('input', (e) => {
            this.updateColumn(columnIndex, name, e.target.value)
        })

        group.appendChild(input)

        return group
    }

    createCheckbox(name, label, checked, columnIndex) {
        const group = document.createElement('div')
        group.className = 'db-field-group checkbox'

        const input = document.createElement('input')
        input.type = 'checkbox'
        input.checked = !!checked
        input.id = `col-${columnIndex}-${name}`

        input.addEventListener('change', (e) => {
            this.updateColumn(columnIndex, name, e.target.checked)
        })

        const labelEl = document.createElement('label')
        labelEl.htmlFor = input.id
        labelEl.textContent = label

        group.appendChild(input)
        group.appendChild(labelEl)

        return group
    }

    createTypeSelect(column, columnIndex) {
        const group = document.createElement('div')
        group.className = 'db-field-group'

        const label = document.createElement('label')
        label.textContent = this.translations.type
        group.appendChild(label)

        const select = document.createElement('select')
        select.className = 'form-control'

        Object.keys(this.config.types).forEach(category => {
            const optgroup = document.createElement('optgroup')
            optgroup.label = category

            this.config.types[category].forEach(type => {
                const option = document.createElement('option')
                option.value = type.name
                option.textContent = type.name
                option.selected = column.type === type.name

                if (!type.supported) {
                    option.disabled = true
                    option.textContent += ` (${this.translations.typeNotSupported})`
                }

                optgroup.appendChild(option)
            })

            select.appendChild(optgroup)
        })

        select.addEventListener('change', (e) => {
            this.updateColumn(columnIndex, 'type', e.target.value)
        })

        group.appendChild(select)

        return group
    }

    createIndexSelect(column, columnIndex) {
        const group = document.createElement('div')
        group.className = 'db-field-group'

        const label = document.createElement('label')
        label.textContent = this.translations.index
        group.appendChild(label)

        const select = document.createElement('select')
        select.className = 'form-control'

        const options = [
            { value: '', label: this.translations.none },
            { value: 'primary', label: this.translations.primary },
            { value: 'unique', label: this.translations.unique },
            { value: 'index', label: 'INDEX' }
        ]

        options.forEach(opt => {
            const option = document.createElement('option')
            option.value = opt.value
            option.textContent = opt.label
            option.selected = column.index === opt.value
            select.appendChild(option)
        })

        select.addEventListener('change', (e) => {
            this.updateColumn(columnIndex, 'index', e.target.value)
        })

        group.appendChild(select)

        return group
    }

    getTypeInfo(typeName) {
        for (const category in this.config.types) {
            const type = this.config.types[category].find(t => t.name === typeName)
            if (type) {
                return { ...type, category }
            }
        }
        return null
    }

    updateColumn(index, property, value) {
        const columns = this.state.state.table.columns

        if (!columns[index]) {
            console.error('Column not found:', index)
            return
        }

        columns[index][property] = value
        this.state.notify(['table', 'columns', index, property])
    }

    addColumn() {
        const columns = this.state.state.table.columns || []

        const newColumn = {
            name: `column_${columns.length + 1}`,
            type: 'string',
            length: null,
            default: null,
            notnull: false,
            unsigned: false,
            autoincrement: false,
            index: null
        }

        columns.push(newColumn)
        this.state.notify(['table', 'columns'])
    }

    removeColumn(index) {
        if (!confirm('Are you sure you want to remove this column?')) {
            return
        }

        const columns = this.state.state.table.columns
        columns.splice(index, 1)

        this.state.notify(['table', 'columns'])
    }

    addTimestamps() {
        const columns = this.state.state.table.columns || []

        const hasCreatedAt = columns.some(col => col.name === 'created_at')
        const hasUpdatedAt = columns.some(col => col.name === 'updated_at')

        if (hasCreatedAt && hasUpdatedAt) {
            toastr.info('Timestamps already exist')
            return
        }

        if (!hasCreatedAt) {
            columns.push({
                name: 'created_at',
                type: 'datetime',
                length: null,
                default: null,
                notnull: false,
                unsigned: false,
                autoincrement: false,
                index: null
            })
        }

        if (!hasUpdatedAt) {
            columns.push({
                name: 'updated_at',
                type: 'datetime',
                length: null,
                default: null,
                notnull: false,
                unsigned: false,
                autoincrement: false,
                index: null
            })
        }

        this.state.notify(['table', 'columns'])
        toastr.success('Timestamps added')
    }

    addSoftDeletes() {
        const columns = this.state.state.table.columns || []

        const hasDeletedAt = columns.some(col => col.name === 'deleted_at')

        if (hasDeletedAt) {
            toastr.info('Soft deletes already exist')
            return
        }

        columns.push({
            name: 'deleted_at',
            type: 'datetime',
            length: null,
            default: null,
            notnull: false,
            unsigned: false,
            autoincrement: false,
            index: null
        })

        this.state.notify(['table', 'columns'])
        toastr.success('Soft deletes added')
    }

    validateTable() {
        const errors = {}
        const columns = this.state.state.table.columns || []

        const names = {}
        columns.forEach((col, index) => {
            if (!col.name || col.name.trim() === '') {
                errors[`columns.${index}`] = errors[`columns.${index}`] || {}
                errors[`columns.${index}`].name = this.translations.nameWarning
            }

            if (names[col.name]) {
                errors[`columns.${index}`] = errors[`columns.${index}`] || {}
                errors[`columns.${index}`].duplicate = this.translations.columnAlreadyExists.replace(':column', col.name)
            }

            names[col.name] = true
        })

        const primaryKeys = columns.filter(col => col.index === 'primary')
        if (primaryKeys.length > 1) {
            primaryKeys.forEach((col, i) => {
                if (i > 0) {
                    const index = columns.indexOf(col)
                    errors[`columns.${index}`] = errors[`columns.${index}`] || {}
                    errors[`columns.${index}`].primary = this.translations.tableHasIndex
                }
            })
        }

        this.state.state.errors = errors

        return Object.keys(errors).length === 0
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.dbConfig) {
        window.dbEditor = new DatabaseTableEditor(window.dbConfig)
    }
})
