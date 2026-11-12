@php
    /** @var array<string, mixed> $field */
    $inputName = \Workbench\App\Support\ConsoleForms::inputName($name);
    $id = 'f-'.str_replace(['.', '[', ']'], '-', $name);
    $wide = in_array($field['type'], ['textarea'], true);
@endphp

@if ($field['type'] === 'checkbox')
    <label class="check {{ $wide ? 'wide' : '' }}">
        <input type="checkbox" name="{{ $inputName }}" value="1" @checked($value)>
        <span>{{ $field['label'] }}</span>
    </label>
@else
    <label class="field {{ $wide ? 'wide' : '' }}" for="{{ $id }}">
        <span>{{ $field['label'] }}@if ($field['required'])<b class="req">*</b>@endif</span>
        @if ($field['type'] === 'select')
            <select id="{{ $id }}" name="{{ $inputName }}">
                @foreach ($field['options'] as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @elseif ($field['type'] === 'textarea')
            <textarea id="{{ $id }}" name="{{ $inputName }}" rows="2">{{ $value }}</textarea>
        @else
            <input id="{{ $id }}" type="{{ $field['type'] }}" name="{{ $inputName }}" value="{{ $value }}"
                @if ($field['type'] === 'number') step="any" @endif
                @required($field['required'])>
        @endif
    </label>
@endif
