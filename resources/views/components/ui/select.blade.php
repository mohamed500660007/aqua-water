@props(['options' => [], 'placeholder' => 'الكل', 'icon' => 'list-filter', 'selected' => null])

<x-ui.dropdown :icon="$icon" :options="$options" :placeholder="$placeholder" :selected="$selected"
    width="w-auto min-w-[150px]" {{ $attributes }} />
