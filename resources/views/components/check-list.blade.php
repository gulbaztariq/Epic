@props(['items' => [], 'columns' => 2, 'icon' => 'check'])

@if (count($items))
    <ul class="check-list is-{{ $columns }}col">
        @foreach ($items as $item)
            <li>
                {!! icon(is_object($item) && $item->icon ? $item->icon : $icon) !!}
                <div>
                    <strong>{{ is_object($item) ? $item->title : $item }}</strong>
                    @if (is_object($item) && $item->description)
                        <span>{{ $item->description }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
