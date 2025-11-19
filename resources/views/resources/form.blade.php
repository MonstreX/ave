@extends('ave::layouts.master')

@php
    $isEdit = $mode === 'edit';
    $action = $isEdit
        ? route('ave.resource.update', ['slug' => $slug, 'id' => $model->getKey()])
        : route('ave.resource.store', ['slug' => $slug]);
    $titleLabel = $isEdit ? __('Edit') : __('Create');
    $formButtonActions = $formButtonActions ?? [];
    $ajaxFormActions = $ajaxFormActions ?? [];
@endphp

@section('page_header')
    @include('ave::partials.form.toolbar', [
        'formButtonActions' => $formButtonActions,
        'ajaxFormActions' => $ajaxFormActions,
        'stickyActions' => $stickyActions ?? false,
    ])
@endsection

@section('content')
    <div class="page-content">
        @include('ave::partials.form.body', [
            'formButtonActions' => $formButtonActions,
            'ajaxFormActions' => $ajaxFormActions,
            'stickyActions' => $stickyActions ?? false,
        ])
    </div>
@endsection
