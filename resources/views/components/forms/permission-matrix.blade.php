@php
    $matrixId = 'permission-matrix-' . substr(md5(spl_object_hash($component)), 0, 8);
    $groups = collect($groups ?? []);
    $selectedIds = collect($selected ?? [])->map(fn ($id) => (int) $id)->all();
    $sectionedGroups = $sectionedGroups ?? [];
    $sectionDefinitions = [
        [
            'key' => 'user',
            'title' => __('User Resources'),
        ],
        [
            'key' => 'system',
            'title' => __('System Resources'),
        ],
    ];
@endphp

<div class="form-field" data-permission-matrix id="{{ $matrixId }}">
    <label class="form-label">
        {{ $label }}
    </label>
    @if($groups->isNotEmpty())
        <div class="help-text">
            <a href="#" data-permission-select-all>{{ __('Select all') }}</a>
            /
            <a href="#" data-permission-deselect-all>{{ __('Deselect all') }}</a>
        </div>
    @endif

    @if($groups->isEmpty())
        <div class="alert alert-warning">
            {{ __('No permissions registered yet. They will appear after resources register their abilities.') }}
        </div>
    @else
        @foreach($sectionDefinitions as $section)
            @php
                $sectionGroups = collect($sectionedGroups[$section['key']] ?? []);
            @endphp
            @if($sectionGroups->isEmpty())
                @continue
            @endif

            <div class="permission-section">
                <h4 class="permission-section__title">{{ $section['title'] }}</h4>
                <div class="matrix-list">
                    @foreach($sectionGroups as $group)
                        <div class="panel-matrix" data-permission-group="{{ $group['slug'] }}">
                            <div class="panel-matrix-heading">
                                <label class="checkbox-label">
                                    <input type="checkbox"
                                           class="checkbox-input"
                                           data-permission-group-toggle="{{ $group['slug'] }}">
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-text">
                                        {{ $group['label'] }}
                                        <small class="text-muted">({{ $group['slug'] }})</small>
                                    </span>
                                </label>
                            </div>
                            <div class="panel-matrix-body">
                                <ul class="list-unstyled" data-permission-items="{{ $group['slug'] }}" style="margin-left: 30px;">
                                    @foreach($group['permissions'] as $permission)
                                        <li>
                                            <label class="checkbox-label">
                                                <input type="checkbox"
                                                       class="checkbox-input"
                                                       name="permissions[{{ $permission['id'] }}]"
                                                       value="{{ $permission['id'] }}"
                                                       data-permission-item
                                                       @checked(in_array($permission['id'], $selectedIds, true))>
                                                <span class="checkbox-custom"></span>
                                                <span class="checkbox-text">
                                                    <strong>{{ $permission['label'] }}</strong>
                                                    <small class="text-muted">({{ $permission['ability'] }})</small>
                                                </span>
                                            </label>
                                            @if(!empty($permission['description']))
                                                <div class="help-text">{{ $permission['description'] }}</div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const updateGroupState = (matrix) => {
                matrix.querySelectorAll('[data-permission-group]').forEach(group => {
                    const slug = group.getAttribute('data-permission-group');
                    const toggle = group.querySelector('[data-permission-group-toggle]');
                    if (!toggle) {
                        return;
                    }

                    const items = matrix.querySelectorAll(`[data-permission-items="${slug}"] [data-permission-item]`);
                    if (!items.length) {
                        toggle.checked = false;
                        toggle.indeterminate = false;
                        return;
                    }

                    const checkedCount = Array.from(items).filter(input => input.checked).length;
                    toggle.checked = checkedCount === items.length;
                    toggle.indeterminate = checkedCount > 0 && checkedCount < items.length;
                });
            };

            document.querySelectorAll('[data-permission-matrix]').forEach(matrix => {
                matrix.addEventListener('change', (event) => {
                    const target = event.target;

                    if (target.matches('[data-permission-group-toggle]')) {
                        const slug = target.getAttribute('data-permission-group-toggle');
                        matrix.querySelectorAll(`[data-permission-items="${slug}"] [data-permission-item]`)
                            .forEach(item => item.checked = target.checked);
                    }

                    if (target.matches('[data-permission-item]')) {
                        updateGroupState(matrix);
                    }
                });

                matrix.querySelectorAll('[data-permission-select-all]').forEach(button => {
                    button.addEventListener('click', (event) => {
                        event.preventDefault();
                        matrix.querySelectorAll('[data-permission-item], [data-permission-group-toggle]')
                            .forEach(input => {
                                input.checked = true;
                                input.indeterminate = false;
                            });
                        updateGroupState(matrix);
                    });
                });

                matrix.querySelectorAll('[data-permission-deselect-all]').forEach(button => {
                    button.addEventListener('click', (event) => {
                        event.preventDefault();
                        matrix.querySelectorAll('[data-permission-item], [data-permission-group-toggle]')
                            .forEach(input => {
                                input.checked = false;
                                input.indeterminate = false;
                            });
                        updateGroupState(matrix);
                    });
                });

                updateGroupState(matrix);
            });
        });
    </script>
@endonce
