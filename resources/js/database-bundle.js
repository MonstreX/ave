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

        const tableData = config.oldTable || config.table

        // Initialize empty index structure
        this.emptyIndex = {
            type: '',
            name: '',
            columns: []
        }

        this.state = new Reactive({
            table: tableData,
            errors: {},
            isDirty: false
        })

        console.log('Reactive state initialized')
        console.log('state.table:', this.state.state.table)
        console.log('state.table.columns:', this.state.state.table.columns)

        // Watch only for structural changes (not input changes)
        // Inputs will update data directly without triggering re-render
        this.structuralChangesOnly = false
        this.state.watch('table', () => {
            if (!this.structuralChangesOnly) {
                this.state.state.isDirty = true
                this.validateTable()
                this.render()
            }
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
        // Update table name when input changes
        const tableNameInput = document.getElementById('table-name')
        if (tableNameInput) {
            tableNameInput.addEventListener('input', (e) => {
                this.structuralChangesOnly = true
                this.state.state.table.name = e.target.value
                this.structuralChangesOnly = false
            })
        }

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

            console.log('=== SUBMITTING TABLE ===')
            console.log('Table data:', this.state.state.table)
            console.log('Columns with indexes:', this.state.state.table.columns.map(col => ({
                name: col.name,
                index: col.index,
                key: col.key
            })))
            const tableJson = JSON.stringify(this.state.state.table)
            console.log('Table JSON length:', tableJson.length)
            document.getElementById('table-data').value = tableJson
            window.dbConfig.table = this.state.state.table
            console.log('Form will submit now')
        })
    }

    render() {
        const container = document.getElementById('columns-container')
        const table = document.getElementById('columns-table')
        const noColumnsMsg = document.getElementById('no-columns-message')

        if (!container) return

        const columns = this.state.state.table.columns || []

        if (columns.length === 0) {
            noColumnsMsg.style.display = 'block'
            if (table) table.style.display = 'none'
            return
        }

        noColumnsMsg.style.display = 'none'
        if (table) table.style.display = 'table'
        container.innerHTML = ''

        columns.forEach((column, index) => {
            const columnElement = this.renderColumn(column, index)
            container.appendChild(columnElement)
        })
    }

    renderColumn(column, index) {
        const row = document.createElement('tr')
        row.className = 'newTableRow'
        row.dataset.index = index

        const columnErrors = this.state.state.errors[`columns.${index}`] || {}
        if (Object.keys(columnErrors).length > 0) {
            row.classList.add('has-error')
        }

        // 1. Name
        const tdName = document.createElement('td')
        const inputName = document.createElement('input')
        inputName.type = 'text'
        inputName.className = 'form-control'
        inputName.value = column.name || ''
        inputName.required = true
        inputName.pattern = this.config.identifierRegex
        inputName.addEventListener('input', (e) => this.updateColumn(index, 'name', e.target.value, false))
        tdName.appendChild(inputName)

        // 2. Type
        const tdType = document.createElement('td')
        tdType.appendChild(this.createTypeSelectSimple(column, index))

        // 3. Length
        const tdLength = document.createElement('td')
        const inputLength = document.createElement('input')
        inputLength.type = 'number'
        inputLength.className = 'form-control'
        inputLength.value = column.length || ''
        inputLength.min = '0'
        inputLength.addEventListener('input', (e) => this.updateColumn(index, 'length', e.target.value, false))
        tdLength.appendChild(inputLength)

        // 4. Not Null
        const tdNotNull = document.createElement('td')
        const checkNotNull = document.createElement('input')
        checkNotNull.type = 'checkbox'
        checkNotNull.checked = !!column.notnull
        checkNotNull.addEventListener('change', (e) => this.updateColumn(index, 'notnull', e.target.checked))
        tdNotNull.appendChild(checkNotNull)

        // 5. Unsigned
        const tdUnsigned = document.createElement('td')
        const checkUnsigned = document.createElement('input')
        checkUnsigned.type = 'checkbox'
        checkUnsigned.checked = !!column.unsigned
        checkUnsigned.addEventListener('change', (e) => this.updateColumn(index, 'unsigned', e.target.checked))
        tdUnsigned.appendChild(checkUnsigned)

        // 6. Auto Increment
        const tdAutoInc = document.createElement('td')
        const checkAutoInc = document.createElement('input')
        checkAutoInc.type = 'checkbox'
        checkAutoInc.checked = !!column.autoincrement
        checkAutoInc.addEventListener('change', (e) => this.updateColumn(index, 'autoincrement', e.target.checked))
        tdAutoInc.appendChild(checkAutoInc)

        // 7. Index
        const tdIndex = document.createElement('td')
        tdIndex.appendChild(this.createIndexSelectSimple(column, index))

        // 8. Default
        const tdDefault = document.createElement('td')
        const inputDefault = document.createElement('input')
        inputDefault.type = 'text'
        inputDefault.className = 'form-control'
        inputDefault.value = column.default || ''
        inputDefault.addEventListener('input', (e) => this.updateColumn(index, 'default', e.target.value, false))
        tdDefault.appendChild(inputDefault)

        // 9. Delete button
        const tdDelete = document.createElement('td')
        const btnDelete = document.createElement('div')
        btnDelete.className = 'btn btn-danger btn-sm btn-square delete-row'
        btnDelete.innerHTML = '<i class="voyager-trash"></i>'
        btnDelete.addEventListener('click', () => this.removeColumn(index))
        tdDelete.appendChild(btnDelete)

        row.appendChild(tdName)
        row.appendChild(tdType)
        row.appendChild(tdLength)
        row.appendChild(tdNotNull)
        row.appendChild(tdUnsigned)
        row.appendChild(tdAutoInc)
        row.appendChild(tdIndex)
        row.appendChild(tdDefault)
        row.appendChild(tdDelete)

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

    createTypeSelectSimple(column, columnIndex) {
        const select = document.createElement('select')
        select.className = 'form-control'

        const currentType = typeof column.type === 'object' ? column.type.name : column.type

        Object.keys(this.config.types).forEach(category => {
            const optgroup = document.createElement('optgroup')
            optgroup.label = category

            this.config.types[category].forEach(type => {
                const option = document.createElement('option')
                option.value = type.name
                option.textContent = type.name
                option.selected = currentType === type.name

                if (!type.supported) {
                    option.disabled = true
                    option.textContent += ` (${this.translations.typeNotSupported})`
                }

                optgroup.appendChild(option)
            })

            select.appendChild(optgroup)
        })

        select.addEventListener('change', (e) => {
            // Find the full type object
            let typeObj = null
            for (const category in this.config.types) {
                const found = this.config.types[category].find(t => t.name === e.target.value)
                if (found) {
                    typeObj = found
                    break
                }
            }

            if (typeObj) {
                // If new type doesn't support indexes, remove existing index
                if (typeObj.notSupportIndex) {
                    const existingIndex = this.getColumnsIndex(column.name)
                    if (existingIndex !== this.emptyIndex) {
                        this.deleteIndex(existingIndex)
                    }
                }

                this.updateColumn(columnIndex, 'type', {
                    name: typeObj.name,
                    notSupported: typeObj.supported === false,
                    notSupportIndex: typeObj.notSupportIndex
                }, true) // Re-render for new type
            }
        })

        return select
    }

    createIndexSelectSimple(column, columnIndex) {
        const select = document.createElement('select')
        select.className = 'form-control'

        // Check if column type supports indexes
        const columnType = typeof column.type === 'object' ? column.type : { name: column.type }
        const notSupportIndex = columnType.notSupportIndex || false

        // Disable select for types that don't support indexes
        if (notSupportIndex) {
            select.disabled = true
        }

        // Get current index for this column
        const currentIndex = this.getColumnsIndex(column.name)
        const currentType = currentIndex !== this.emptyIndex ? currentIndex.type.toLowerCase() : ''

        const options = [
            { value: '', label: '' },
            { value: 'index', label: 'INDEX' },
            { value: 'unique', label: 'UNIQUE' },
            { value: 'primary', label: 'PRIMARY' }
        ]

        options.forEach(opt => {
            const option = document.createElement('option')
            option.value = opt.value
            option.textContent = opt.label
            option.selected = currentType === opt.value
            select.appendChild(option)
        })

        select.addEventListener('change', (e) => {
            this.updateColumnIndex(columnIndex, e.target.value)
        })

        return select
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

    updateColumn(index, property, value, shouldRender = true) {
        const columns = this.state.state.table.columns

        if (!columns[index]) {
            console.error('Column not found:', index)
            return
        }

        // Special handling for index changes - sync with table.indexes array
        if (property === 'index') {
            this.updateColumnIndex(index, value)
            return
        }

        // Disable rendering for simple input changes
        if (!shouldRender) {
            this.structuralChangesOnly = true
        }

        columns[index][property] = value

        // Re-enable rendering
        if (!shouldRender) {
            this.structuralChangesOnly = false
        }
    }

    /**
     * Update column index and sync with table.indexes array
     * Based on Voyager's approach: find existing index, update or delete it
     */
    updateColumnIndex(columnIndex, newIndexType) {
        const columns = this.state.state.table.columns
        const column = columns[columnIndex]
        const columnName = column.name

        // Normalize type to uppercase (select values are lowercase, backend expects uppercase)
        const normalizedType = newIndexType ? newIndexType.toUpperCase() : ''

        // Initialize indexes array if not exists
        if (!this.state.state.table.indexes) {
            this.state.state.table.indexes = []
        }

        // Find existing index for this column (single-column index only)
        const existingIndex = this.state.state.table.indexes.find(idx => {
            return idx.columns && idx.columns.length === 1 && idx.columns[0] === columnName
        })

        // Case 1: Remove index (newIndexType is empty or null)
        if (!normalizedType || normalizedType === '') {
            if (existingIndex) {
                // Use findIndex to properly locate the index in reactive array
                const indexPos = this.state.state.table.indexes.findIndex(idx =>
                    idx.columns && idx.columns.length === 1 && idx.columns[0] === columnName
                )
                if (indexPos !== -1) {
                    this.state.state.table.indexes.splice(indexPos, 1)
                }
            }
            column.index = null
        }
        // Case 2: Add new index (no existing index)
        else if (!existingIndex) {
            const indexName = normalizedType === 'PRIMARY' ? 'primary' : ''
            this.state.state.table.indexes.push({
                name: indexName,
                type: normalizedType,
                columns: [columnName]
            })
            column.index = normalizedType
        }
        // Case 3: Update existing index
        else {
            existingIndex.type = normalizedType
            existingIndex.name = normalizedType === 'PRIMARY' ? 'primary' : ''
            column.index = normalizedType
        }

        // Trigger update (without full re-render)
        this.structuralChangesOnly = false
    }

    addColumn() {
        const columns = this.state.state.table.columns || []

        const newColumn = {
            name: `column_${columns.length + 1}`,
            type: {
                name: 'string',
                notSupported: false,
                notSupportIndex: false
            },
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
                type: {
                    name: 'datetime',
                    notSupported: false,
                    notSupportIndex: false
                },
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
                type: {
                    name: 'datetime',
                    notSupported: false,
                    notSupportIndex: false
                },
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
            type: {
                name: 'datetime',
                notSupported: false,
                notSupportIndex: false
            },
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

    getColumnsIndex(columnName) {
        const columns = Array.isArray(columnName) ? columnName : [columnName]
        const indexes = this.state.state.table.indexes || []

        for (let i = 0; i < indexes.length; i++) {
            const indexColumns = indexes[i].columns || []
            // Check if columns match exactly
            if (indexColumns.length === columns.length &&
                indexColumns.every(col => columns.includes(col))) {
                return indexes[i]
            }
        }

        return this.emptyIndex
    }

    onIndexChange(columnIndex, newType) {
        const column = this.state.state.table.columns[columnIndex]
        const oldIndex = this.getColumnsIndex(column.name)

        if (oldIndex === this.emptyIndex && newType) {
            // Add new index
            return this.addIndex({
                columns: [column.name],
                type: newType.toUpperCase()
            })
        }

        if (!newType || newType === '') {
            // Delete index
            return this.deleteIndex(oldIndex)
        }

        // Update existing index
        return this.updateIndex(oldIndex, newType.toUpperCase())
    }

    addIndex(index) {
        if (index.type === 'PRIMARY') {
            if (this.state.state.table.primaryKeyName) {
                toastr.error('Table already has a primary key')
                return
            }
            this.state.state.table.primaryKeyName = 'primary'
        }

        this.setIndexName(index)
        this.state.state.table.indexes.push(index)
        this.state.notify(['table', 'indexes'])
    }

    deleteIndex(index) {
        if (!index || index === this.emptyIndex) {
            return
        }

        const indexes = this.state.state.table.indexes || []
        const indexPos = indexes.indexOf(index)

        if (indexPos !== -1) {
            if (index.type === 'PRIMARY') {
                this.state.state.table.primaryKeyName = false
            }
            indexes.splice(indexPos, 1)
            this.state.notify(['table', 'indexes'])
        }
    }

    updateIndex(index, newType) {
        if (index.type === 'PRIMARY') {
            this.state.state.table.primaryKeyName = false
        } else if (newType === 'PRIMARY') {
            if (this.state.state.table.primaryKeyName) {
                toastr.error('Table already has a primary key')
                return
            }
            this.state.state.table.primaryKeyName = 'primary'
        }

        index.type = newType
        this.setIndexName(index)
        this.state.notify(['table', 'indexes'])
    }

    setIndexName(index) {
        if (index.type === 'PRIMARY') {
            index.name = 'primary'
        } else {
            // Name will be set by PHP on server
            index.name = ''
        }
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
