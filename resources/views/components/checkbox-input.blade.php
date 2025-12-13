@props([
    'label' => null,
    'name',
    'checked' => false,
])

<div {{ $attributes->only('class') }}>
    <div class="flex items-center">
        <input 
            type="checkbox"
            name="{{ $name }}"
            id="{{ $name }}"
            value="1"
            {{ old($name, $checked) ? 'checked' : '' }}
            {{ $attributes->except(['class', 'label', 'name', 'checked'])->merge(['class' => 'h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500']) }}
        >
        
        @if($label)
            <label for="{{ $name }}" class="ml-2 block text-sm text-gray-700">
                {{ $label }}
            </label>
        @endif
    </div>
    
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
