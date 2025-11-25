@extends('ave::layouts.master')

@section('page_header')
<div class="page-header">
    <h1 class="page-title">
        <i class="voyager-data"></i> {{ __('ave::database.title') }}
    </h1>
    <div class="page-header-actions">
        <a href="{{ route('ave.database.create') }}" class="btn btn-success">
            <i class="voyager-plus"></i> {{ __('ave::database.create_table') }}
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-body">
                    <table class="table table-hover ave-table">
                        <thead>
                            <tr>
                                <th>{{ __('ave::database.table_name') }}</th>
                                <th class="text-right">{{ __('ave::database.table_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tables as $table)
                                <tr>
                                    <td>
                                        <strong>{{ $table->name }}</strong>
                                    </td>
                                    <td class="text-right">
                                        <button type="button"
                                                class="btn btn-sm btn-info btn-view-table"
                                                data-table="{{ $table->name }}"
                                                title="{{ __('ave::database.view_table') }}">
                                            <i class="voyager-eye"></i>
                                        </button>
                                        <a href="{{ route('ave.database.edit', $table->name) }}"
                                           class="btn btn-sm btn-primary"
                                           title="{{ __('ave::database.edit_table') }}">
                                            <i class="voyager-edit"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-danger btn-delete-table"
                                                data-table="{{ $table->name }}"
                                                title="{{ __('ave::database.delete_table') }}">
                                            <i class="voyager-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">
                                        {{ __('ave::common.no_results') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- View Table Structure Modal --}}
<div id="table-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('ave::common.close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="voyager-data"></i> <span id="table-modal-name"></span>
                </h4>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('ave::database.field') }}</th>
                            <th>{{ __('ave::database.type') }}</th>
                            <th>{{ __('ave::database.null') }}</th>
                            <th>{{ __('ave::database.key') }}</th>
                            <th>{{ __('ave::database.default') }}</th>
                            <th>{{ __('ave::database.extra') }}</th>
                        </tr>
                    </thead>
                    <tbody id="table-modal-tbody"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {{ __('ave::common.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Table Confirmation Modal --}}
<div id="delete-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('ave::common.close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="voyager-trash"></i> {{ __('ave::database.delete_table') }}
                </h4>
            </div>
            <div class="modal-body">
                <p>{{ __('ave::database.delete_table_question', ['table' => '<strong id="delete-table-name"></strong>']) }}</p>
            </div>
            <div class="modal-footer">
                <form id="delete-table-form" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        {{ __('ave::common.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-danger">
                        {{ __('ave::database.delete_table_confirm') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // View table structure
    document.querySelectorAll('.btn-view-table').forEach(btn => {
        btn.addEventListener('click', async function() {
            const tableName = this.dataset.table;

            try {
                const response = await fetch(`{{ route('ave.database.index') }}/${tableName}`);
                const columns = await response.json();

                document.getElementById('table-modal-name').textContent = tableName;

                const tbody = document.getElementById('table-modal-tbody');
                tbody.innerHTML = columns.map(col => `
                    <tr>
                        <td><strong>${col.field}</strong></td>
                        <td>${col.type}</td>
                        <td>${col.null}</td>
                        <td>${col.key || '-'}</td>
                        <td>${col.default || '-'}</td>
                        <td>${col.extra || '-'}</td>
                    </tr>
                `).join('');

                $('#table-modal').modal('show');
            } catch (error) {
                console.error('Error loading table structure:', error);
                toastr.error('{{ __('ave::errors.something_went_wrong') }}');
            }
        });
    });

    // Delete table
    document.querySelectorAll('.btn-delete-table').forEach(btn => {
        btn.addEventListener('click', function() {
            const tableName = this.dataset.table;

            document.getElementById('delete-table-name').textContent = tableName;
            document.getElementById('delete-table-form').action =
                `{{ route('ave.database.index') }}/${tableName}`;

            $('#delete-modal').modal('show');
        });
    });
});
</script>
@endsection
