{{-- Tiêu đề cột có sắp xếp. Tham số: $column, $label, $sort, $dir --}}
@php
    $active = $sort === $column;
    $nextDir = $active && $dir === 'asc' ? 'desc' : 'asc';
@endphp
<a class="sort-link" href="{{ request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDir, 'page' => null]) }}">
    {{ $label }}@if ($active) {{ $dir === 'asc' ? '▲' : '▼' }}@endif
</a>
