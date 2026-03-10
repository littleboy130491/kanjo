@props([
    'placement',
    'trackingSetting' => null,
])

@if($trackingSetting)
    @if($placement === 'head' && filled($trackingSetting->head_code ?? null))
        {!! $trackingSetting->head_code !!}
    @endif

    @if($placement === 'body' && filled($trackingSetting->body_code ?? null))
        {!! $trackingSetting->body_code !!}
    @endif
@endif
