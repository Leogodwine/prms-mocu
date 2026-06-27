<style>
    .prms-consent-doc {
        max-width: 720px;
        margin: 0 auto;
        font-family: "DejaVu Sans", "Times New Roman", Times, serif;
        font-size: 12pt;
        line-height: 1.65;
        color: #111;
        text-align: center;
    }
    .prms-consent-doc .uni-heading {
        font-size: 13pt;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        margin: 0;
        line-height: 1.4;
    }
    .prms-consent-doc .uni-heading-sw {
        font-size: 12pt;
        font-weight: 700;
        text-transform: uppercase;
        margin: 0.15rem 0 0;
        line-height: 1.4;
    }
    .prms-consent-doc .divider-dots {
        border: none;
        border-top: 1px dotted #333;
        margin: 1.25rem auto 1.5rem;
        max-width: 100%;
    }
    .prms-consent-doc .doc-title {
        font-size: 12pt;
        font-weight: 700;
        text-transform: uppercase;
        margin: 0 0 1.75rem;
        line-height: 1.45;
        text-align: center;
    }
    .prms-consent-doc .consent-body {
        text-align: justify;
        margin: 0 auto;
        max-width: 100%;
    }
    .prms-consent-doc .consent-body p {
        margin-bottom: 1.35rem;
        text-align: justify;
    }
    .prms-consent-doc .consent-accept {
        text-align: center;
        font-weight: 600;
        margin: 1.5rem 0 2rem;
    }
    .prms-consent-doc .sig-field {
        text-align: left;
        margin: 0 0 1.35rem;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11pt;
    }
    .prms-consent-doc .sig-field .sig-value {
        display: inline-block;
        min-width: 14rem;
        border-bottom: 1px solid #111;
        font-weight: 400;
        text-transform: none;
        padding: 0 0.25rem 0.15rem;
        vertical-align: bottom;
        margin-left: 0.35rem;
    }
    .prms-consent-doc .sig-field .sig-value img {
        max-height: 72px;
        max-width: 240px;
        vertical-align: bottom;
    }
    .prms-consent-doc .sig-field .sig-blank {
        display: inline-block;
        min-width: 14rem;
        border-bottom: 1px solid #111;
        font-weight: 400;
        text-transform: none;
        letter-spacing: 0.12em;
        color: #666;
        padding-bottom: 0.15rem;
        margin-left: 0.35rem;
    }
    .prms-consent-doc .sig-date {
        text-align: left;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11pt;
        margin-top: 0.5rem;
    }
    .prms-consent-doc .sig-date .sig-value,
    .prms-consent-doc .sig-date .sig-blank {
        display: inline-block;
        min-width: 8rem;
        border-bottom: 1px solid #111;
        font-weight: 400;
        text-transform: none;
        padding-bottom: 0.15rem;
        margin-left: 0.35rem;
    }
    .prms-consent-doc .prms-consent-letterhead {
        width: 100%;
        border-collapse: collapse;
        margin: 0 0 0.5rem;
    }
    .prms-consent-doc .prms-consent-letterhead td {
        vertical-align: middle;
        padding: 0;
        border: 0;
    }
    .prms-consent-doc .prms-consent-letterhead__logo {
        width: 20%;
        text-align: center;
    }
    .prms-consent-doc .prms-consent-letterhead__logo--national {
        text-align: left;
    }
    .prms-consent-doc .prms-consent-letterhead__logo--mocu {
        text-align: right;
    }
    .prms-consent-doc .prms-consent-letterhead__logo img {
        max-height: 72px;
        max-width: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
    }
    .prms-consent-doc .prms-consent-letterhead__center {
        width: 60%;
        text-align: center;
        padding: 0 0.5rem;
    }
</style>

@php
    $pdfMode = $pdfMode ?? false;
    $nationalLogoSrc = $pdfMode
        ? (\App\Support\PresentationConsentForm::publicImageDataUri('images/national-logo.jpg') ?? asset('images/national-logo.jpg'))
        : asset('images/national-logo.jpg');
    $mocuLogoSrc = $pdfMode
        ? (\App\Support\PresentationConsentForm::publicImageDataUri('images/mocu_logo.png') ?? asset('images/mocu_logo.png'))
        : asset('images/mocu_logo.png');
    $supervisorName = trim((string) ($supervisor?->name ?? ''));
    $groupNumber = trim((string) ($groupNumber ?? ($projectGroup?->name ?? '')));
    $programmeLabel = strtoupper(trim((string) ($programmeConsentLabel ?? $programme ?? 'PROJECT')));
    $titleText = trim((string) ($projectTitle ?? ''));
    $consentDateValue = optional($coordinatorApprovedAt ?? null)?->format('d/m/Y');
    $isGroup = ($projectGroup && ($members?->count() ?? 0) > 1);
    $subjectPhrase = $isGroup ? 'this group' : 'this student';
@endphp

<article class="prms-consent-doc">
    <table class="prms-consent-letterhead" role="presentation">
        <tr>
            <td class="prms-consent-letterhead__logo prms-consent-letterhead__logo--national">
                <img src="{{ $nationalLogoSrc }}" alt="National emblem of Tanzania">
            </td>
            <td class="prms-consent-letterhead__center">
                <p class="uni-heading">Moshi Co-operative University (MoCU)</p>
                <p class="uni-heading-sw">Chuo Kikuu Cha Ushirika Moshi</p>
            </td>
            <td class="prms-consent-letterhead__logo prms-consent-letterhead__logo--mocu">
                <img src="{{ $mocuLogoSrc }}" alt="Moshi Co-operative University logo">
            </td>
        </tr>
    </table>

    <hr class="divider-dots">

    <h2 class="doc-title">
        Supervisor&rsquo;s confimation form for {{ $programmeLabel }} project final presentation and repository release
    </h2>

    <div class="consent-body">
        <p>
            I
            @if ($supervisorName !== '')
                <strong>{{ $supervisorName }}</strong>
            @else
                <span class="sig-blank" style="display:inline-block;min-width:12rem;border-bottom:1px solid #111;">&nbsp;</span>
            @endif
            the supervisor for group number
            @if ($groupNumber !== '')
                <strong>{{ $groupNumber }}</strong>
            @else
                <span class="sig-blank" style="display:inline-block;min-width:4rem;border-bottom:1px solid #111;">&nbsp;</span>
            @endif
            who undertaking an ICT project solution titled
            @if ($titleText !== '' && ! str_contains($titleText, '____'))
                <strong>{{ $titleText }}</strong>
            @else
                <span class="sig-blank" style="display:inline-block;min-width:16rem;border-bottom:1px solid #111;">&nbsp;</span>
            @endif
        </p>

        <p class="consent-accept">Hereby accept {{ $subjectPhrase }} to present their project.</p>
    </div>

    <div class="sig-field">
        Supervisor&rsquo;s name:
        @if ($supervisorName !== '')
            <span class="sig-value">{{ $supervisorName }}</span>
        @else
            <span class="sig-blank">&nbsp;</span>
        @endif
    </div>

    <div class="sig-field">
        Supervisor&rsquo;s signature:
        @if (! empty($supervisorSignatureDataUri))
            <span class="sig-value"><img src="{{ $supervisorSignatureDataUri }}" alt="Supervisor signature"></span>
        @else
            <span class="sig-blank">&nbsp;</span>
        @endif
    </div>

    <div class="sig-field">
        Coordinator&rsquo;s signature:
        @if (! empty($coordinatorSignatureDataUri))
            <span class="sig-value"><img src="{{ $coordinatorSignatureDataUri }}" alt="Coordinator signature"></span>
        @else
            <span class="sig-blank">&nbsp;</span>
        @endif
    </div>

    <div class="sig-date">
        Date:
        @if ($consentDateValue)
            <span class="sig-value">{{ $consentDateValue }}</span>
        @else
            <span class="sig-blank">&nbsp;</span>
        @endif
    </div>
</article>
