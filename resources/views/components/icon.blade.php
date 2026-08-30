@props(['name'])

{{-- أيقونة من نظام Lucide: خطوط حديثة، سماكة موحّدة، نهايات دائرية --}}
<i data-lucide="{{ $name }}" {{ $attributes->merge(['class' => 'shrink-0']) }}></i>
