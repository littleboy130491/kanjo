@php
    $display = fn (?string $value): string => filled($value) ? e($value) : '';
    $displayAddress = fn (?string $value): string => filled($value) ? nl2br(e($value)) : '';
@endphp

<p>{{ __('spk.party_intro', ['subject' => $subjectText, 'date' => $spkDateText]) }}</p>

<table class="spk-party-table">
    <tbody>
        <tr>
            <td>{{ __('spk.label_name') }}</td>
            <td>:</td>
            <td>{!! $display($spk->client_pic_name) !!}</td>
        </tr>
        <tr>
            <td>{{ __('spk.label_role') }}</td>
            <td>:</td>
            <td>{!! $display($spk->client_pic_role) !!}</td>
        </tr>
        <tr>
            <td>{{ __('spk.label_company') }}</td>
            <td>:</td>
            <td>{!! $display($spk->client_company) !!}</td>
        </tr>
        <tr>
            <td>{{ __('spk.label_address') }}</td>
            <td>:</td>
            <td>{!! $displayAddress($spk->client_address) !!}</td>
        </tr>
    </tbody>
</table>

<p>{!! __('spk.first_party', ['party' => __('spk.first_party_name')]) !!}</p>

<table class="spk-party-table">
    <tbody>
        <tr>
            <td>{{ __('spk.label_name') }}</td>
            <td>:</td>
            <td>{!! $display($spk->company_pic_name) !!}</td>
        </tr>
        <tr>
            <td>{{ __('spk.label_role') }}</td>
            <td>:</td>
            <td>{!! $display($spk->company_pic_role) !!}</td>
        </tr>
        <tr>
            <td>{{ __('spk.label_company') }}</td>
            <td>:</td>
            <td>{!! $display($spk->company_name) !!}</td>
        </tr>
        <tr>
            <td>{{ __('spk.label_address') }}</td>
            <td>:</td>
            <td>{!! $displayAddress($spk->company_address) !!}</td>
        </tr>
    </tbody>
</table>

<p>{!! __('spk.second_party', ['party' => __('spk.second_party_name')]) !!}</p>
<p>{{ __('spk.parties_collective') }}</p>
<p>{{ __('spk.agreement_opening') }}</p>
