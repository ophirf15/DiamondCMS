@php
    $footerStyle = $footerStyle ?? 'branded';
    $chrome = $chrome ?? [];
    $showLogo = (bool) ($chrome['footerShowLogo'] ?? true);
    $showName = (bool) ($chrome['footerShowSiteName'] ?? true);
    $tagline = trim((string) ($chrome['footerTagline'] ?? ''));
    $showCredit = (bool) ($chrome['footerShowCredit'] ?? true);
    $creditText = trim((string) ($chrome['footerCreditText'] ?? 'Powered by DiamondCMS'));
    if ($creditText === '') {
        $creditText = 'Powered by DiamondCMS';
    }
    $creditUrl = trim((string) ($chrome['footerCreditUrl'] ?? ''));
    $footerSocials = \App\Domains\Design\Support\DesignManager::footerSocialItems();
    $footerSocialStyle = (string) ($chrome['footerSocialStyle'] ?? \App\Domains\Design\Support\DesignManager::socialStyle());
    if (! array_key_exists($footerSocialStyle, \App\Domains\Design\Support\DesignManager::socialStyles())) {
        $footerSocialStyle = 'icons';
    }
@endphp
<footer class="dc-footer dc-footer--{{ $footerStyle }}">
    @if ($footerStyle === 'branded' || $footerStyle === 'split' || $footerStyle === 'centered')
        <div class="dc-footer-brand">
            @if ($showLogo)
                <img src="{{ diamondcms_logo_url() }}" alt="" class="dc-footer-logo">
            @endif
            @if ($showName)
                <strong>{{ diamondcms_site_name() }}</strong>
            @endif
            @if ($tagline !== '')
                <p class="dc-footer-tagline">{{ $tagline }}</p>
            @endif
        </div>
    @endif
    <nav aria-label="Footer">
        @foreach (diamondcms_menu('footer') as $item)
            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
        @endforeach
    </nav>
    @if ($footerSocials !== [])
        {!! \App\Domains\Design\Support\SocialIcons::groupHtml(array_values($footerSocials), $footerSocialStyle, 'dc-footer-socials') !!}
    @endif
    @if ($showCredit)
        @if ($creditUrl !== '')
            <a class="dc-footer-credit" href="{{ $creditUrl }}" rel="noopener">{{ $creditText }}</a>
        @else
            <span class="dc-footer-credit">{{ $creditText }}</span>
        @endif
    @endif
</footer>
