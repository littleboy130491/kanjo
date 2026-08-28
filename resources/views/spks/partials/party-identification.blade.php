@php
    $display = fn (?string $value): string => filled($value) ? e($value) : '';
    $displayAddress = fn (?string $value): string => filled($value) ? nl2br(e($value)) : '';
@endphp

<p>{{ __('spk.party_intro', ['subject' => $subjectText, 'date' => $spkDateText]) }}</p>

{!! \App\Services\SpkTemplateRenderer::tipTapPartyTable([
    [e(__('spk.label_name')), ':', $display($spk->client_pic_name)],
    [e(__('spk.label_role')), ':', $display($spk->client_pic_role)],
    [e(__('spk.label_company')), ':', $display($spk->client_company)],
    [e(__('spk.label_address')), ':', $displayAddress($spk->client_address)],
]) !!}

<p>{!! __('spk.first_party', ['party' => __('spk.first_party_name')]) !!}</p>

{!! \App\Services\SpkTemplateRenderer::tipTapPartyTable([
    [e(__('spk.label_name')), ':', $display($spk->company_pic_name)],
    [e(__('spk.label_role')), ':', $display($spk->company_pic_role)],
    [e(__('spk.label_company')), ':', $display($spk->company_name)],
    [e(__('spk.label_address')), ':', $displayAddress($spk->company_address)],
]) !!}

<p>{!! __('spk.second_party', ['party' => __('spk.second_party_name')]) !!}</p>
<p>{{ __('spk.parties_collective') }}</p>
<p>{{ __('spk.agreement_opening') }}</p>
