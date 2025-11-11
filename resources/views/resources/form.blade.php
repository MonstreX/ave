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
    @include('ave::partials.form.toolbar', [
        'formActions' => $formActions ?? [],
    ])
@endsection

@section('content')
    <div class="page-content">
        @include('ave::partials.form.body', [
            'formActions' => $formActions ?? [],
        ])
    </div>
@endsection
