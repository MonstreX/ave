import { Reactive } from './reactive'

class DatabaseTableEditor {
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

            const tableJson = JSON.stringify(this.state.state.table)
            document.getElementById('table-data').value = tableJson
            window.dbConfig.table = this.state.state.table
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
        row.dataset.index = index
        row.className = 'dbm-row'

        const columnErrors = this.state.state.errors[`columns.${index}`] || {}
        if (Object.keys(columnErrors).length > 0) {
            row.classList.add('has-error')
        }

        const nameCell = this.renderTextInput(column.name || '', (value) => this.updateColumn(index, 'name', value), {
            required: true,
            pattern: this.config.identifierRegex,
            maxlength: '63',
        })

        if (column.composite) {
            const warning = document.createElement('div')
            warning.className = 'dbm-index-warning'
            warning.innerHTML = `<i class="voyager-warning"></i> ${this.translations.compositeWarning}`
            nameCell.appendChild(warning)
        }

        row.appendChild(nameCell)
        row.appendChild(this.renderTypeSelect(column, index))
        row.appendChild(
            this.renderTextInput(
                column.length ?? '',
                (value) => this.updateColumn(index, 'length', value ? parseInt(value, 10) || null : null),
                {
                    type: 'number',
                    min: '1',
                }
            )
        )
        row.appendChild(this.renderCheckbox(!!column.notnull, (value) => this.updateColumn(index, 'notnull', value)))
        row.appendChild(this.renderCheckbox(!!column.unsigned, (value) => this.updateColumn(index, 'unsigned', value)))
        row.appendChild(
            this.renderCheckbox(!!column.autoincrement, (value) => this.updateColumn(index, 'autoincrement', value))
        )
        row.appendChild(this.createIndexSelectSimple(column, index))
        row.appendChild(this.renderTextInput(column.default ?? '', (value) => this.updateColumn(index, 'default', value)))
        row.appendChild(this.renderRemoveButton(index))

        return row
    }

    renderTextInput(value, onChange, attrs = {}) {
        const cell = document.createElement('td')
        const input = document.createElement('input')
        input.className = 'form-control'
        input.value = value ?? ''
        input.type = attrs.type || 'text'
        Object.entries(attrs).forEach(([key, attrValue]) => {
            if (key !== 'type') {
                input.setAttribute(key, attrValue)
            }
        })

        input.addEventListener('input', (event) => onChange(event.target.value))

        cell.appendChild(input)
        return cell
    }

    renderCheckbox(value, onChange) {
        const cell = document.createElement('td')
        const input = document.createElement('input')
        input.type = 'checkbox'
        input.checked = !!value
        input.addEventListener('change', (event) => onChange(event.target.checked))
        cell.appendChild(input)
        return cell
    }

    renderTypeSelect(column, index) {
        const cell = document.createElement('td')
        const select = document.createElement('select')
        select.className = 'form-control'

        // Handle both string and object type
        const columnTypeName = typeof column.type === 'object' ? column.type.name : column.type

        Object.entries(this.config.types || {}).forEach(([category, typeList]) => {
            const optGroup = document.createElement('optgroup')
            optGroup.label = category

            typeList.forEach((type) => {
                const option = document.createElement('option')
                option.value = type.name
                option.textContent = type.name
                option.selected = columnTypeName === type.name

                if (!type.supported) {
                    option.disabled = true
                    option.textContent += ` (${this.translations.typeNotSupported})`
                }

                optGroup.appendChild(option)
            })

            select.appendChild(optGroup)
        })

        select.addEventListener('change', (event) => this.updateColumn(index, 'type', event.target.value))
        cell.appendChild(select)
        return cell
    }

    createIndexSelectSimple(column, columnIndex) {
        const cell = document.createElement('td')
        const select = document.createElement('select')
        select.className = 'form-control'

        // Get type name and check if it supports indexes
        const typeName = typeof column.type === 'object' ? column.type.name : column.type
        let notSupportIndex = false

        // Check in config.types if this type supports indexes
        if (typeof column.type === 'object') {
            notSupportIndex = column.type.notSupportIndex || false
        } else {
            // Find type in config to check notSupportIndex
            for (const category in this.config.types) {
                const typeObj = this.config.types[category].find(t => t.name === typeName)
                if (typeObj) {
                    notSupportIndex = typeObj.notSupportIndex || false
                    break
                }
            }
        }

        if (notSupportIndex) {
            select.disabled = true
        }

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

        cell.appendChild(select)
        return cell
    }

    renderRemoveButton(index) {
        const cell = document.createElement('td')
        const button = document.createElement('div')
        button.className = 'btn btn-danger btn-sm btn-square delete-row'
        button.innerHTML = '<i class="voyager-trash"></i>'
        button.addEventListener('click', () => this.removeColumn(index))
        cell.appendChild(button)
        return cell
    }

    getColumnsIndex(columnName) {
        const columns = Array.isArray(columnName) ? columnName : [columnName]
        const indexes = this.state.state.table.indexes || []

        for (let i = 0; i < indexes.length; i++) {
            const indexColumns = indexes[i].columns || []
            if (indexColumns.length === columns.length &&
                indexColumns.every(col => columns.includes(col))) {
                return indexes[i]
            }
        }

        return this.emptyIndex
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

        if (!normalizedType || normalizedType === '') {
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
        this.state.state.isDirty = true

        // If type changed, trigger full re-render to update index select state
        if (property === 'type') {
            this.state.notify(['table', 'columns'])
        }

        this.validateTable()
        window.dbConfig.table = this.state.state.table
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
                    const idx = columns.indexOf(col)
                    errors[`columns.${idx}`] = errors[`columns.${idx}`] || {}
                    errors[`columns.${idx}`].primary = this.translations.tableHasIndex
                }
            })
        }

        this.state.state.errors = errors

        return Object.keys(errors).length === 0
    }
}

export { DatabaseTableEditor }
