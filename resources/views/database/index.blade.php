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
                                        <a href="{{ route('ave.database.edit', $table->name) }}" style="font-weight: normal;">
                                            {{ $table->name }}
                                        </a>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('ave.database.edit', $table->name) }}"
                                           class="btn btn-sm btn-primary btn-square"
                                           title="{{ __('ave::database.edit_table') }}">
                                            <i class="voyager-edit"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-danger btn-square btn-delete-table"
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
@endsection

@section('javascript')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete table
    document.querySelectorAll('.btn-delete-table').forEach(btn => {
        btn.addEventListener('click', async function() {
            const tableName = this.dataset.table;

            const confirmed = await Ave.confirm(
                '{{ __('ave::database.delete_table_question_text') }}',
                {
                    title: '{{ __('ave::database.delete_table') }}',
                    bodyParams: [tableName],
                    confirmText: '{{ __('ave::database.delete_table_confirm') }}',
                    cancelText: '{{ __('ave::common.cancel') }}',
                    variant: 'warning'
                }
            );

            if (confirmed) {
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `{{ route('ave.database.index') }}/${tableName}`;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';

                form.appendChild(csrfInput);
                form.appendChild(methodInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
</script>
@endsection
