import { Reactive } from './reactive'

const INDEX_OPTIONS = [
    { value: '', label: '' },
    { value: 'index', label: 'INDEX' },
    { value: 'unique', label: 'UNIQUE' },
    { value: 'primary', label: 'PRIMARY' },
]

export class DatabaseTableEditor {
    constructor(config) {
        this.config = config
        this.translations = config.translations

        const tableData = config.oldTable || config.table

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
        const tableNameInput = document.getElementById('table-name')
        if (tableNameInput) {
            tableNameInput.addEventListener('input', (e) => {
                this.state.state.table.name = e.target.value
            })
        }

        document.getElementById('btn-add-column')?.addEventListener('click', () => this.addColumn())
        document.getElementById('btn-add-timestamps')?.addEventListener('click', () => this.addTimestamps())
        document.getElementById('btn-add-softdeletes')?.addEventListener('click', () => this.addSoftDeletes())

        document.getElementById('database-form')?.addEventListener('submit', (event) => {
            if (!this.validateTable()) {
                event.preventDefault()
                toastr.error(this.translations.fixValidationErrors || 'Please fix validation errors before saving')
                return false
            }

            const payload = JSON.stringify(this.state.state.table)
            document.getElementById('table-data').value = payload
            window.dbConfig.table = this.state.state.table

            return true
        })
    }

    render() {
        const container = document.getElementById('columns-container')
        const emptyState = document.getElementById('no-columns-message')

        if (!container) {
            return
        }

        const columns = this.state.state.table.columns || []

        if (columns.length === 0) {
            emptyState?.classList.add('is-visible')
            container.innerHTML = ''
            return
        }

        emptyState?.classList.remove('is-visible')
        container.innerHTML = ''

        columns.forEach((column, index) => {
            container.appendChild(this.renderColumnCard(column, index))
        })
    }

    renderColumnCard(column, index) {
        const card = document.createElement('div')
        card.className = 'dbm-column'
        card.dataset.index = index

        if (this.state.state.errors[`columns.${index}`]) {
            card.classList.add('has-error')
        }

        const header = document.createElement('div')
        header.className = 'dbm-column__header'

        const title = document.createElement('h4')
        title.textContent = column.name || `Column ${index + 1}`

        const actions = document.createElement('div')
        actions.appendChild(this.createRemoveButton(column, index))

        header.appendChild(title)
        header.appendChild(actions)

        const body = document.createElement('div')
        body.className = 'dbm-column__body'

        body.appendChild(this.createInputField(this.translations.field, column.name || '', (value) => this.updateColumn(index, 'name', value), {
            required: true,
            pattern: this.config.identifierRegex,
        }))

        body.appendChild(this.createTypeSelect(column, index))

        body.appendChild(this.createInputField(this.translations.length, column.length ?? '', (value) => this.updateColumn(index, 'length', value ? parseInt(value, 10) || null : null), {
            type: 'number',
            min: '1',
        }))

        body.appendChild(this.createCheckboxField(this.translations.not_null, !!column.notnull, (value) => this.updateColumn(index, 'notnull', value)))
        body.appendChild(this.createCheckboxField(this.translations.unsigned, !!column.unsigned, (value) => this.updateColumn(index, 'unsigned', value)))
        body.appendChild(this.createCheckboxField(this.translations.auto_increment, !!column.autoincrement, (value) => this.updateColumn(index, 'autoincrement', value)))

        body.appendChild(this.createIndexSelect(column, index))

        body.appendChild(this.createInputField(this.translations.default, column.default ?? '', (value) => this.updateColumn(index, 'default', value)))

        if (column.composite) {
            const warning = document.createElement('div')
            warning.className = 'dbm-warning'
            warning.innerHTML = `<i class="voyager-warning"></i> ${this.translations.compositeWarning || ''}`
            body.appendChild(warning)
        }

        card.appendChild(header)
        card.appendChild(body)

        return card
    }

    createInputField(label, value, onChange, attrs = {}) {
        const group = document.createElement('div')
        group.className = 'form-group'

        const labelEl = document.createElement('label')
        labelEl.textContent = label
        group.appendChild(labelEl)

        const input = document.createElement('input')
        input.type = attrs.type || 'text'
        input.className = 'form-control'
        input.value = value ?? ''

        Object.entries(attrs).forEach(([key, attrValue]) => {
            if (key !== 'type') {
                input.setAttribute(key, attrValue)
            }
        })

        input.addEventListener('input', (event) => onChange(event.target.value))
        group.appendChild(input)

        return group
    }

    createCheckboxField(label, checked, onChange) {
        const wrapper = document.createElement('div')
        wrapper.className = 'dbm-checkbox'

        const input = document.createElement('input')
        input.type = 'checkbox'
        input.checked = !!checked
        input.addEventListener('change', (event) => onChange(event.target.checked))

        const labelEl = document.createElement('label')
        labelEl.textContent = label

        wrapper.appendChild(input)
        wrapper.appendChild(labelEl)

        return wrapper
    }

    createTypeSelect(column, index) {
        const group = document.createElement('div')
        group.className = 'form-group'

        const label = document.createElement('label')
        label.textContent = this.translations.type
        group.appendChild(label)

        const select = document.createElement('select')
        select.className = 'form-control'

        Object.entries(this.config.types || {}).forEach(([category, typeList]) => {
            const optGroup = document.createElement('optgroup')
            optGroup.label = category

            typeList.forEach((type) => {
                const option = document.createElement('option')
                option.value = type.name
                option.textContent = type.name
                option.selected = column.type === type.name

                if (type.supported === false) {
                    option.disabled = true
                    option.textContent += ` (${this.translations.typeNotSupported || ''})`
                }

                optGroup.appendChild(option)
            })

            select.appendChild(optGroup)
        })

        select.addEventListener('change', (event) => this.updateColumn(index, 'type', event.target.value))
        group.appendChild(select)

        return group
    }

    createIndexSelect(column, index) {
        const group = document.createElement('div')
        group.className = 'form-group'

        const label = document.createElement('label')
        label.textContent = this.translations.index
        group.appendChild(label)

        const select = document.createElement('select')
        select.className = 'form-control'

        const columnType = typeof column.type === 'object' ? column.type : { name: column.type }
        if (columnType.notSupportIndex) {
            select.disabled = true
        }

        const currentIndex = this.getColumnsIndex(column.name)
        const currentType = currentIndex !== this.emptyIndex ? currentIndex.type.toLowerCase() : ''

        INDEX_OPTIONS.forEach(optionData => {
            const option = document.createElement('option')
            option.value = optionData.value
            option.textContent = optionData.label || this.translations.none || ''
            option.selected = currentType === optionData.value
            select.appendChild(option)
        })

        select.addEventListener('change', (event) => this.updateColumnIndex(index, event.target.value))
        group.appendChild(select)

        return group
    }

    createRemoveButton(column, index) {
        const button = document.createElement('button')
        button.type = 'button'
        button.className = 'btn btn-danger btn-sm dbm-remove-column'
        button.innerHTML = `<i class="voyager-trash"></i> ${this.translations.removeColumnConfirm || 'Remove column'}`
        button.addEventListener('click', () => this.handleRemoveColumn(column, index))
        return button
    }

    async handleRemoveColumn(column, index) {
        const confirmed = await this.confirmRemoval(column?.name || `Column ${index + 1}`)
        if (!confirmed) {
            return
        }

        const columns = this.state.state.table.columns
        columns.splice(index, 1)

        this.state.notify(['table', 'columns'])
    }

    async confirmRemoval(columnName) {
        const message = (this.translations.removeColumnBody || 'Remove column ":column"?').replace(':column', columnName)
        const title = this.translations.removeColumnTitle || 'Remove column'
        const confirmText = this.translations.removeColumnConfirm || 'Remove column'
        const cancelText = this.translations.cancel || 'Cancel'

        if (window.Ave?.confirm) {
            return window.Ave.confirm(message, {
                title,
                confirmText,
                cancelText,
                variant: 'danger'
            })
        }

        return Promise.resolve(window.confirm(message))
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

    getColumnsIndex(columnName) {
        const columns = Array.isArray(columnName) ? columnName : [columnName]
        const indexes = this.state.state.table.indexes || []

        for (let i = 0; i < indexes.length; i++) {
            const indexColumns = indexes[i].columns || []
            if (indexColumns.length === columns.length && indexColumns.every(col => columns.includes(col))) {
                return indexes[i]
            }
        }

        return this.emptyIndex
    }

    updateColumn(index, property, value) {
        const columns = this.state.state.table.columns
        if (!columns[index]) {
            return
        }

        if (property === 'index') {
            this.updateColumnIndex(index, value)
            return
        }

        columns[index][property] = value
        this.validateTable()
        window.dbConfig.table = this.state.state.table
    }

    updateColumnIndex(columnIndex, newIndexType) {
        const columns = this.state.state.table.columns
        const column = columns[columnIndex]
        const columnName = column.name
        const normalizedType = newIndexType ? newIndexType.toUpperCase() : ''

        if (!this.state.state.table.indexes) {
            this.state.state.table.indexes = []
        }

        const existingIndex = this.state.state.table.indexes.find(idx => {
            return idx.columns && idx.columns.length === 1 && idx.columns[0] === columnName
        })

        if (!normalizedType) {
            if (existingIndex) {
                const indexPos = this.state.state.table.indexes.findIndex(idx =>
                    idx.columns && idx.columns.length === 1 && idx.columns[0] === columnName
                )
                if (indexPos !== -1) {
                    this.state.state.table.indexes.splice(indexPos, 1)
                }
            }
            column.index = null
        } else if (!existingIndex) {
            const indexName = normalizedType === 'PRIMARY' ? 'primary' : ''
            this.state.state.table.indexes.push({
                name: indexName,
                type: normalizedType,
                columns: [columnName]
            })
            column.index = normalizedType
        } else {
            existingIndex.type = normalizedType
            existingIndex.name = normalizedType === 'PRIMARY' ? 'primary' : ''
            column.index = normalizedType
        }

        this.state.notify(['table', 'columns', columnIndex, 'index'])
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
            columns.push(this.makeDateColumn('created_at'))
        }

        if (!hasUpdatedAt) {
            columns.push(this.makeDateColumn('updated_at'))
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

        columns.push(this.makeDateColumn('deleted_at'))
        this.state.notify(['table', 'columns'])
        toastr.success('Soft deletes added')
    }

    makeDateColumn(name) {
        return {
            name,
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
        }
    }

    validateTable() {
        const errors = {}
        const columns = this.state.state.table.columns || []
        const names = {}

        columns.forEach((column, index) => {
            const key = `columns.${index}`

            if (!column.name || !column.name.trim()) {
                errors[key] = errors[key] || {}
                errors[key].name = this.translations.nameWarning || 'Column name cannot be empty'
            }

            if (column.name && names[column.name]) {
                errors[key] = errors[key] || {}
                errors[key].duplicate = (this.translations.columnAlreadyExists || 'Column :column already exists').replace(':column', column.name)
            }

            names[column.name] = true
        })

        const primaryKeys = columns.filter(col => col.index === 'primary')
        if (primaryKeys.length > 1) {
            primaryKeys.forEach((col, idx) => {
                if (idx > 0) {
                    const columnIndex = columns.indexOf(col)
                    const key = `columns.${columnIndex}`
                    errors[key] = errors[key] || {}
                    errors[key].primary = this.translations.tableHasIndex || 'Table already has a primary key'
                }
            })
        }

        this.state.state.errors = errors
        return Object.keys(errors).length === 0
    }
}
