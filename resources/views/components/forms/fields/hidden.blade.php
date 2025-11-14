@php
    $fieldStatePath = $statePath ?? $field->getStatePath();
    $fieldInputName = $inputName ?? \Monstrex\Ave\Support\FormInputName::nameFromStatePath($fieldStatePath);
@endphp
<input type="hidden" name="{{ $fieldInputName }}" value="{{ old($fieldStatePath, $field->getValue()) }}">
