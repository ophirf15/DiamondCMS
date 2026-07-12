@php
    use App\Domains\Design\Support\DesignManager;

    $gallery = is_array($gallery ?? null) ? $gallery : [];
    $display = DesignManager::portfolioAttr('galleryDisplay', DesignManager::portfolioGalleryDisplays(), 'carousel');
    $fit = DesignManager::portfolioAttr('galleryFit', DesignManager::portfolioMediaFits(), 'contain');
    $slides = [];
    foreach ($gallery as $image) {
        $src = trim((string) ($image['src'] ?? ''));
        if ($src === '') {
            continue;
        }
        $slides[] = [
            'src' => $src,
            'alt' => (string) ($image['alt'] ?? $project->title),
        ];
    }
@endphp
@if ($slides !== [])
    <section
        class="dc-project-gallery dc-project-gallery--{{ $display }} dc-project-gallery--fit-{{ $fit }}"
        aria-label="Project gallery"
        data-dc-gallery-display="{{ $display }}"
    >
        <h2>Gallery</h2>

        @if ($display === 'carousel')
            <div
                class="dc-gallery-carousel"
                data-dc-carousel
                @if (count($slides) > 1) tabindex="0" @endif
            >
                @if (count($slides) > 1)
                    <button type="button" class="dc-carousel-nav dc-carousel-nav--prev" data-dc-carousel-prev aria-label="Previous image">‹</button>
                @endif
                <div class="dc-carousel-viewport">
                    <div class="dc-carousel-track" data-dc-carousel-track>
                        @foreach ($slides as $index => $slide)
                            <figure class="dc-carousel-slide" data-dc-carousel-slide>
                                <button
                                    type="button"
                                    class="dc-gallery-trigger"
                                    data-dc-lightbox
                                    data-dc-lightbox-src="{{ $slide['src'] }}"
                                    data-dc-lightbox-alt="{{ $slide['alt'] }}"
                                >
                                    <span class="dc-media-frame dc-media-frame--{{ $fit }}">
                                        <img class="dc-media-fit" src="{{ $slide['src'] }}" alt="{{ $slide['alt'] }}" loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                                    </span>
                                </button>
                                @if (trim($slide['alt']) !== '' && $slide['alt'] !== $project->title)
                                    <figcaption>{{ $slide['alt'] }}</figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                </div>
                @if (count($slides) > 1)
                    <button type="button" class="dc-carousel-nav dc-carousel-nav--next" data-dc-carousel-next aria-label="Next image">›</button>
                @endif
                @if (count($slides) > 1)
                    <div class="dc-carousel-dots" data-dc-carousel-dots role="tablist" aria-label="Gallery slides">
                        @foreach ($slides as $index => $slide)
                            <button
                                type="button"
                                class="dc-carousel-dot{{ $index === 0 ? ' is-active' : '' }}"
                                data-dc-carousel-dot="{{ $index }}"
                                aria-label="Show image {{ $index + 1 }}"
                            ></button>
                        @endforeach
                    </div>
                    <div class="dc-carousel-thumbs" data-dc-carousel-thumbs aria-hidden="true">
                        @foreach ($slides as $index => $slide)
                            <button
                                type="button"
                                class="dc-carousel-thumb{{ $index === 0 ? ' is-active' : '' }}"
                                data-dc-carousel-dot="{{ $index }}"
                            >
                                <span class="dc-media-frame dc-media-frame--contain dc-media-frame--thumb">
                                    <img class="dc-media-fit" src="{{ $slide['src'] }}" alt="" loading="lazy">
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="dc-gallery-grid dc-gallery-grid--framed" data-dc-animate="stagger">
                @foreach ($slides as $slide)
                    <figure class="dc-gallery-item" data-dc-animate="rise">
                        <button
                            type="button"
                            class="dc-gallery-trigger"
                            data-dc-lightbox
                            data-dc-lightbox-src="{{ $slide['src'] }}"
                            data-dc-lightbox-alt="{{ $slide['alt'] }}"
                        >
                            <span class="dc-media-frame dc-media-frame--{{ $fit }}">
                                <img class="dc-media-fit" src="{{ $slide['src'] }}" alt="{{ $slide['alt'] }}" loading="lazy">
                            </span>
                        </button>
                        @if (trim($slide['alt']) !== '' && $slide['alt'] !== $project->title)
                            <figcaption>{{ $slide['alt'] }}</figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        @endif
    </section>
@endif
