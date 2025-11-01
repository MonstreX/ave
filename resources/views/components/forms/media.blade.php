<div class="form-group">
    <label>{{ $field->getLabel() }}</label>
    <input type="file" name="{{ $field->key() }}" class="form-control">
    @if($field->getHelpText())
        <small class="help-text">{{ $field->getHelpText() }}</small>
    @endif
</div>
