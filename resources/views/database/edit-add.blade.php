@extends('ave::layouts.master')

@section('page_header')
<div class="page-header">
    <h1 class="page-title">
        <i class="voyager-data"></i>
        @if($db->action === 'update')
            {{ __('ave::database.editing_table', ['table' => $db->table->getName()]) }}
        @else
            {{ __('ave::database.creating_table') }}
        @endif
    </h1>
    <div class="page-header-actions">
        <a href="{{ route('ave.database.index') }}" class="btn btn-default">
            <i class="voyager-list"></i> {{ __('ave::database.title') }}
        </a>
    </div>
</div>
@endsection

@section('notifications')
    @if(session('error'))
        <div class="alert alert-danger">
            <i class="voyager-x"></i>
            <strong>Error:</strong> {{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">
            <i class="voyager-check"></i>
            <strong>Success:</strong> {{ session('success') }}
        </div>
    @endif
@endsection

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-md-12">
            <form id="database-form" method="POST" action="{{ $db->formAction }}">
                @csrf
                @if($db->action === 'update')
                    @method('PUT')
                @endif

                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="voyager-edit"></i> {{ __('ave::database.table_name') }}
                        </h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="table-name">{{ __('ave::database.table_name') }}</label>
                                    <input type="text"
                                           class="form-control"
                                           id="table-name"
                                           name="name"
                                           value="{{ $db->table->getName() }}"
                                           pattern="{{ $db->identifierRegex }}"
                                           required
                                           @if($db->action === 'update') readonly @endif>
                                    @if($db->action === 'update')
                                        <p class="help-block">
                                            <i class="voyager-info-circled"></i>
                                            Table name cannot be changed when editing
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if($db->action === 'create')
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="create-model-checkbox" name="create_model" value="1">
                                        {{ __('ave::database.create_model') }}
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="model-name"
                                           name="model_name"
                                           value=""
                                           placeholder="App\Models\YourModel"
                                           disabled>
                                    <p class="help-block">
                                        <i class="voyager-info-circled"></i>
                                        Full model class name with namespace
                                    </p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="voyager-list"></i> {{ __('ave::database.columns') }}
                        </h3>
                    </div>
                    <div class="panel-body">
                        <p class="text-muted text-center" id="no-columns-message" style="display: none;">
                            {{ __('ave::database.table_no_columns') }}
                        </p>
                        <table class="table table-bordered" id="columns-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>{{ __('ave::database.name') }}</th>
                                    <th>{{ __('ave::database.type') }}</th>
                                    <th>{{ __('ave::database.length') }}</th>
                                    <th>{{ __('ave::database.not_null') }}</th>
                                    <th>{{ __('ave::database.unsigned') }}</th>
                                    <th>{{ __('ave::database.auto_increment') }}</th>
                                    <th>{{ __('ave::database.index') }}</th>
                                    <th>{{ __('ave::database.default') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="columns-container">
                                {{-- Columns will be rendered here by JavaScript --}}
                            </tbody>
                        </table>

                        <div class="panel-actions" style="margin-top: 15px; text-align: center;">
                            <button type="button" class="btn btn-primary" id="btn-add-column">
                                <i class="voyager-plus"></i> {{ __('ave::database.add_column') }}
                            </button>
                            <button type="button" class="btn btn-success" id="btn-add-timestamps">
                                <i class="voyager-clock"></i> {{ __('ave::database.add_timestamps') }}
                            </button>
                            <button type="button" class="btn btn-info" id="btn-add-softdeletes">
                                <i class="voyager-trash"></i> {{ __('ave::database.add_softdeletes') }}
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Hidden input that will contain the serialized table JSON --}}
                <input type="hidden" name="table" id="table-data">

                <div class="panel">
                    <div class="panel-body">
                        <button type="submit" class="btn btn-primary">
                            <i class="voyager-check"></i>
                            @if($db->action === 'update')
                                {{ __('ave::database.update_table') }}
                            @else
                                {{ __('ave::database.create_table') }}
                            @endif
                        </button>
                        <a href="{{ route('ave.database.index') }}" class="btn btn-default">
                            <i class="voyager-x"></i> {{ __('ave::common.cancel') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
/* Database table editor styles */
.db-column-row {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 10px;
    position: relative;
}

.db-column-row:hover {
    background: #e9ecef;
}

.db-column-row.has-error {
    border-color: #dc3545;
    background: #fff5f5;
}

.db-column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dee2e6;
}

.db-column-title {
    font-weight: 600;
    font-size: 14px;
    color: #333;
}

.db-column-actions {
    display: flex;
    gap: 5px;
}

.db-column-body {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-start;
}

.db-field-group {
    display: flex;
    flex-direction: column;
    flex: 0 0 auto;
}

.db-field-group label {
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 3px;
    color: #666;
    white-space: nowrap;
}

.db-field-group input,
.db-field-group select {
    font-size: 13px;
    padding: 5px 8px;
    height: 32px;
}

/* Field widths */
.db-field-group input[type="text"] {
    min-width: 100px;
}

.db-field-group select {
    min-width: 120px;
}

/* Checkbox styling */
.db-field-group.checkbox {
    flex-direction: row;
    align-items: center;
    min-width: auto;
    padding-top: 18px; /* Align with inputs */
}

.db-field-group.checkbox input {
    margin-right: 5px;
    margin-bottom: 0;
}

.db-field-group.checkbox label {
    margin-bottom: 0;
    font-size: 12px;
}

.db-column-warning {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 4px;
    padding: 8px 12px;
    margin-top: 10px;
    font-size: 12px;
    color: #856404;
}

.db-column-warning i {
    margin-right: 5px;
}

/* Button styles */
.btn-remove-column {
    background: #dc3545;
    color: white;
    border: none;
    padding: 4px 8px;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
}

.btn-remove-column:hover {
    background: #c82333;
}

/* Panel actions */
.panel-actions {
    display: flex;
    gap: 5px;
}

.panel-actions .btn {
    margin: 0;
}

/* Type badge */
.type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    background: #6c757d;
    color: white;
}

.type-badge.numbers { background: #007bff; }
.type-badge.strings { background: #28a745; }
.type-badge.datetime { background: #ffc107; color: #333; }
.type-badge.other { background: #6c757d; }
</style>
@endsection

@section('javascript')
{{-- Database manager configuration --}}
<script>
window.dbConfig = {
    action: '{{ $db->action }}',
    platform: '{{ $db->platform }}',
    identifierRegex: {!! json_encode($db->identifierRegex) !!},
    types: @json($db->types),
    table: @json($db->table->toArray()),
    oldTable: @json($db->oldTable),
    translations: {
        field: '{{ __('ave::database.field') }}',
        type: '{{ __('ave::database.type') }}',
        length: '{{ __('ave::database.length') }}',
        notNull: '{{ __('ave::database.not_null') }}',
        unsigned: '{{ __('ave::database.unsigned') }}',
        autoIncrement: '{{ __('ave::database.auto_increment') }}',
        index: '{{ __('ave::database.index') }}',
        default: '{{ __('ave::database.default') }}',
        primary: '{{ __('ave::database.primary') }}',
        unique: '{{ __('ave::database.unique') }}',
        none: '-',
        nameWarning: '{{ __('ave::database.name_warning') }}',
        columnAlreadyExists: '{{ __('ave::database.column_already_exists') }}',
        tableHasIndex: '{{ __('ave::database.table_has_index') }}',
        compositeWarning: '{{ __('ave::database.composite_warning') }}',
        typeNotSupported: '{{ __('ave::database.type_not_supported') }}'
    }
};

// Model creation logic
@if($db->action === 'create')
document.addEventListener('DOMContentLoaded', function() {
    const tableNameInput = document.getElementById('table-name');
    const createModelCheckbox = document.getElementById('create-model-checkbox');
    const modelNameInput = document.getElementById('model-name');

    // Convert table name to model name (snake_case to StudlyCase)
    function tableNameToModelName(tableName) {
        // Remove table prefix if exists
        const prefix = '{{ DB::getTablePrefix() }}';
        if (tableName.startsWith(prefix)) {
            tableName = tableName.substring(prefix.length);
        }

        // Convert to singular StudlyCase
        return 'App\\Models\\' + tableName
            .split('_')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join('');
    }

    // Update model name when table name changes
    tableNameInput.addEventListener('input', function() {
        if (this.value && createModelCheckbox.checked) {
            modelNameInput.value = tableNameToModelName(this.value);
        }
    });

    // Enable/disable model name input based on checkbox
    createModelCheckbox.addEventListener('change', function() {
        modelNameInput.disabled = !this.checked;
        if (this.checked && tableNameInput.value) {
            modelNameInput.value = tableNameToModelName(tableNameInput.value);
        } else if (!this.checked) {
            modelNameInput.value = '';
        }
    });
});
@endif
</script>

@endsection
