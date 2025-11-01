@extends('ave::layouts.master')

@php
    $isEdit = $mode === 'edit';
    $action = $isEdit
        ? route('ave.resource.update', ['slug' => $slug, 'id' => $model->getKey()])
        : route('ave.resource.store', ['slug' => $slug]);
    $titleLabel = $isEdit ? __('Edit') : __('Create');
    $submitLabel = $form->getSubmitLabel() ?? ($isEdit ? __('Update') : __('Create'));
    $cancelUrl = $form->getCancelUrl() ?? route('ave.resource.index', ['slug' => $slug]);
@endphp

@section('page_header')
    <div class="page-header">
        <h1 class="page-title">
            <i class="{{ $isEdit ? 'voyager-edit' : 'voyager-plus' }}"></i>
            {{ $titleLabel }} {{ $resource::getSingularLabel() }}
        </h1>
        <div class="page-header-actions">
            <a href="{{ $cancelUrl }}" class="btn btn-secondary">
                <i class="voyager-angle-left"></i>
                <span>{{ __('Back') }}</span>
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-content">
        <div class="panel panel-bordered">
            <div class="panel-body">
                <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    @foreach($formLayout as $row)
                        <div class="form-row">
                            @foreach($row['columns'] as $column)
                                <div class="form-column" style="grid-column: span {{ $column['span'] }}">
                                    @foreach($column['fields'] as $field)
                                        {!! $field->render($context) !!}
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    <div class="form-actions">
                        <a href="{{ $cancelUrl }}" class="btn btn-secondary">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            {{ $submitLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
