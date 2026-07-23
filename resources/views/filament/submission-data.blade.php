<div class="space-y-2">
    @foreach ($data as $key => $value)
        <div>
            <span class="font-semibold">{{ $key }}</span>:
            <span>{{ is_array($value) ? implode(', ', $value) : $value }}</span>
        </div>
    @endforeach
</div>
