@props(['steps' => []])

{{-- Horizontal progress tracker for an order's delivery journey. --}}
<ol class="flex items-center w-full overflow-x-auto">
    @foreach($steps as $i => $step)
        <li class="flex items-center {{ $i < count($steps) - 1 ? 'flex-1' : '' }} min-w-max">
            <div class="flex flex-col items-center text-center px-2">
                <span @class([
                    'flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold',
                    'bg-emerald-600 text-white' => $step['done'],
                    'bg-indigo-600 text-white ring-4 ring-indigo-100' => $step['current'],
                    'bg-gray-200 text-gray-500' => ! $step['done'] && ! $step['current'],
                ])>
                    @if($step['done'])
                        &checkmark;
                    @else
                        {{ $i + 1 }}
                    @endif
                </span>
                <span @class([
                    'mt-1 text-[11px] leading-tight max-w-[5.5rem]',
                    'text-gray-900 font-medium' => $step['done'] || $step['current'],
                    'text-gray-400' => ! $step['done'] && ! $step['current'],
                ])>{{ $step['label'] }}</span>
            </div>
            @if($i < count($steps) - 1)
                <div @class([
                    'h-0.5 flex-1 mx-1',
                    'bg-emerald-600' => $step['done'],
                    'bg-gray-200' => ! $step['done'],
                ])></div>
            @endif
        </li>
    @endforeach
</ol>
