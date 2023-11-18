@props([
    'disabled' => false,
    'required' => false,
    'placeholder' => '',
    'value' => '',
    'name' => '',
    'type' => 'text',
    'class' => '',
    'aria_label' => '',
        'model' => '',

])

<input   @if($model) wire:model="{{ $model }}" @endif type="{{ $type }}" value="{{ $value }}" {{ $disabled ? 'disabled' : '' }}
    {{ $required ? 'required' : '' }} placeholder="{{ $placeholder }}" name="{{ $name }}"
    {!! $attributes->merge([
        'class' => 'form-control ' . $class,
    ]) !!}  @if($aria_label) aria-label="{{$aria_label}}" @endif
       @if($aria_label) aria-label="{{$aria_label}}" @endif
>
