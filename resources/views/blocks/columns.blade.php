<div class="xms-block xms-block--columns">
    @foreach($data['columns'] ?? [] as $column)
        <div class="xms-block__column">
            @if(!empty($column['title']))
                <h3 class="xms-block__column-title">{{ $column['title'] }}</h3>
            @endif
            @if(!empty($column['content']))
                <div class="xms-block__column-content">{!! Str::markdown($column['content']) !!}</div>
            @endif
        </div>
    @endforeach
</div>
