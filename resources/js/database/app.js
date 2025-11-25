/**
 * Database Table Editor - Main Application
 * Manages table structure editing with reactive state
 */

class DatabaseTableEditor {
    constructor(config) {
        this.config = config
        this.translations = config.translations

        console.log('DatabaseTableEditor init')
        console.log('config.table:', config.table)
        console.log('config.oldTable:', config.oldTable)

        // Initialize reactive state
        this.state = new Reactive({
            table: config.oldTable || config.table,
            errors: {},
            isDirty: false
        })

        console.log('Reactive state initialized')
        console.log('state.table:', this.state.state.table)
        console.log('state.table.columns:', this.state.state.table.columns)

        // Watch for changes
        this.state.watch('table', () => {
            this.state.state.isDirty = true
            this.validateTable()
            this.render()
        })

        // Sync state with global config for form submission
        window.dbConfig.table = this.state.state.table

        this.init()
    }

    init() {
        this.render()
        this.attachEventListeners()

        // Initial validation
        this.validateTable()
    }

    /**
     * Attach event listeners to buttons
     */
    attachEventListeners() {
        // Add column button
        document.getElementById('btn-add-column')?.addEventListener('click', () => {
            this.addColumn()
        })

        // Add timestamps button
        document.getElementById('btn-add-timestamps')?.addEventListener('click', () => {
            this.addTimestamps()
        })

        // Add soft deletes button
        document.getElementById('btn-add-softdeletes')?.addEventListener('click', () => {
            this.addSoftDeletes()
        })

        // Form submit - sync table data to hidden input
        document.getElementById('database-form')?.addEventListener('submit', (e) => {
            if (!this.validateTable()) {
                e.preventDefault()
                toastr.error('Please fix validation errors before saving')
                return false
            }

            // Sync state to hidden input
            document.getElementById('table-data').value = JSON.stringify(this.state.state.table)
            window.dbConfig.table = this.state.state.table
        })
    }

    /**
     * Render columns list
     */
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

        // Clear container
        container.innerHTML = ''

        // Render each column
        columns.forEach((column, index) => {
            const columnElement = this.renderColumn(column, index)
            container.appendChild(columnElement)
        })
    }

    /**
     * Render single column row
     */
    renderColumn(column, index) {
        const row = document.createElement('div')
        row.className = 'db-column-row'
        row.dataset.index = index

        // Add error class if validation failed
        const columnErrors = this.state.state.errors[`columns.${index}`] || {}
        if (Object.keys(columnErrors).length > 0) {
            row.classList.add('has-error')
        }

        // Column header
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

        // Column body with fields
        const body = document.createElement('div')
        body.className = 'db-column-body'

        // Field: Name
        body.appendChild(this.createField('text', 'name', this.translations.field, column.name, index, {
            required: true,
            pattern: this.config.identifierRegex
        }))

        // Field: Type
        body.appendChild(this.createTypeSelect(column, index))

        // Field: Length
        const type = this.getTypeInfo(column.type)
        if (type && type.requiresLength) {
            body.appendChild(this.createField('text', 'length', this.translations.length, column.length, index, {
                placeholder: type.defaultLength || ''
            }))
        }

        // Field: Default value
        body.appendChild(this.createField('text', 'default', this.translations.default, column.default, index))

        // Not Null checkbox
        body.appendChild(this.createCheckbox('notnull', this.translations.notNull, column.notnull, index))

        // Unsigned checkbox (only for numeric types)
        if (type && type.category === 'numbers') {
            body.appendChild(this.createCheckbox('unsigned', this.translations.unsigned, column.unsigned, index))
        }

        // Auto Increment checkbox (only for integer types)
        if (type && (type.name === 'integer' || type.name === 'bigint' || type.name === 'smallint')) {
            body.appendChild(this.createCheckbox('autoincrement', this.translations.autoIncrement, column.autoincrement, index))
        }

        // Index selector
        body.appendChild(this.createIndexSelect(column, index))

        // Composite index warning
        if (column.composite) {
            const warning = document.createElement('div')
            warning.className = 'db-column-warning'
            warning.innerHTML = `<i class="voyager-warning"></i> ${this.translations.compositeWarning}`
            body.appendChild(warning)
        }

        // Validation errors
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

    /**
     * Create text/number input field
     */
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

    /**
     * Create checkbox field
     */
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

    /**
     * Create type select dropdown
     */
    createTypeSelect(column, columnIndex) {
        const group = document.createElement('div')
        group.className = 'db-field-group'

        const label = document.createElement('label')
        label.textContent = this.translations.type
        group.appendChild(label)

        const select = document.createElement('select')
        select.className = 'form-control'

        // Group types by category
        Object.keys(this.config.types).forEach(category => {
            const optgroup = document.createElement('optgroup')
            optgroup.label = category

            this.config.types[category].forEach(type => {
                const option = document.createElement('option')
                option.value = type.name
                option.textContent = type.name
                option.selected = column.type === type.name

                // Mark unsupported types
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

    /**
     * Create index select dropdown
     */
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

    /**
     * Get type information from config
     */
    getTypeInfo(typeName) {
        for (const category in this.config.types) {
            const type = this.config.types[category].find(t => t.name === typeName)
            if (type) {
                return { ...type, category }
            }
        }
        return null
    }

    /**
     * Update column property
     */
    updateColumn(index, property, value) {
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

        columns[index][property] = value

        // Trigger re-render
        this.state.notify(['table', 'columns', index, property])
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

        // Trigger re-render
        this.state.notify(['table', 'columns', columnIndex, 'index'])
    }

    /**
     * Add new column
     */
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

    /**
     * Remove column
     */
    removeColumn(index) {
        if (!confirm('Are you sure you want to remove this column?')) {
            return
        }

        const columns = this.state.state.table.columns
        columns.splice(index, 1)

        this.state.notify(['table', 'columns'])
    }

    /**
     * Add timestamps (created_at, updated_at)
     */
    addTimestamps() {
        const columns = this.state.state.table.columns || []

        // Check if timestamps already exist
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

    /**
     * Add soft deletes (deleted_at)
     */
    addSoftDeletes() {
        const columns = this.state.state.table.columns || []

        // Check if soft deletes already exist
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

    /**
     * Validate entire table
     */
    validateTable() {
        const errors = {}
        const columns = this.state.state.table.columns || []

        // Check for duplicate column names
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

        // Check for multiple primary keys
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
