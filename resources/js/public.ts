export function prefersReducedMotion(): boolean {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

export function motionEnabled(): boolean {
    if (document.body.dataset.dcMotion === 'off') return false
    if (prefersReducedMotion()) return false
    return true
}

export function observeReveals(): void {
    if (!motionEnabled()) {
        document.querySelectorAll('[data-dc-animate]').forEach((el) => el.classList.add('dc-in-view'))
        return
    }

    const observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('dc-in-view')
                    observer.unobserve(entry.target)
                }
            }
        },
        { threshold: 0.12, rootMargin: '0px 0px -8% 0px' },
    )

    document.querySelectorAll('[data-dc-animate]').forEach((el) => observer.observe(el))
}

export function bindParallax(): void {
    if (!motionEnabled()) return

    const images = Array.from(document.querySelectorAll<HTMLElement>('.dc-image[data-dc-animate="parallax"]'))
    if (!images.length) return

    const onScroll = (): void => {
        const mid = window.innerHeight / 2
        for (const image of images) {
            const rect = image.getBoundingClientRect()
            const offset = (rect.top + rect.height / 2 - mid) * -0.04
            image.style.transform = `translateY(${offset.toFixed(2)}px)`
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true })
    onScroll()
}

const THEME_KEY = 'diamondcms.theme'

export function systemTheme(): 'light' | 'dark' {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

export function applyTheme(theme: 'light' | 'dark' | 'auto'): void {
    const root = document.documentElement
    const resolved = theme === 'auto' ? systemTheme() : theme
    if (theme === 'auto') {
        root.removeAttribute('data-theme')
    } else {
        root.setAttribute('data-theme', theme)
    }
    root.style.colorScheme = resolved
    document.querySelectorAll('[data-dc-theme-toggle-btn]').forEach((btn) => {
        btn.textContent = resolved === 'dark' ? 'Light' : 'Dark'
    })
}

export function initThemeToggle(): void {
    const locked = document.body.dataset.dcThemeLock === '1'
    const toggleEnabled = document.body.dataset.dcThemeToggle === '1'
    const fallback = (document.body.dataset.dcThemeDefault || 'auto') as 'light' | 'dark' | 'auto'

    if (locked || !toggleEnabled) {
        if (fallback !== 'auto') {
            applyTheme(fallback)
        }
        return
    }

    const stored = localStorage.getItem(THEME_KEY) as 'light' | 'dark' | 'auto' | null
    applyTheme(stored || fallback)

    document.querySelectorAll('[data-dc-theme-toggle-btn]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const current = (document.documentElement.getAttribute('data-theme') as 'light' | 'dark' | null)
                || systemTheme()
            const next = current === 'dark' ? 'light' : 'dark'
            localStorage.setItem(THEME_KEY, next)
            applyTheme(next)
        })
    })
}

export function initLightbox(): void {
    const triggers = Array.from(document.querySelectorAll<HTMLElement>('[data-dc-lightbox]'))
    if (!triggers.length) return

    let overlay = document.querySelector<HTMLElement>('.dc-lightbox')
    if (!overlay) {
        overlay = document.createElement('div')
        overlay.className = 'dc-lightbox'
        overlay.innerHTML = '<button type="button" class="dc-lightbox-close" aria-label="Close">×</button><img alt="">'
        document.body.appendChild(overlay)
    }

    const image = overlay.querySelector('img')
    const closeBtn = overlay.querySelector('.dc-lightbox-close')
    if (!(image instanceof HTMLImageElement)) return

    const close = (): void => {
        overlay?.classList.remove('is-open')
        image.removeAttribute('src')
        image.alt = ''
    }

    const open = (src: string, alt: string): void => {
        image.src = src
        image.alt = alt
        overlay?.classList.add('is-open')
    }

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const src = trigger.dataset.dcLightboxSrc || ''
            if (!src) return
            open(src, trigger.dataset.dcLightboxAlt || '')
        })
    })

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) close()
    })
    closeBtn?.addEventListener('click', close)
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close()
    })
}

export function initGalleryCarousels(): void {
    document.querySelectorAll<HTMLElement>('[data-dc-carousel]').forEach((root) => {
        const track = root.querySelector<HTMLElement>('[data-dc-carousel-track]')
        const slides = Array.from(root.querySelectorAll<HTMLElement>('[data-dc-carousel-slide]'))
        if (!track || slides.length < 2) return

        let index = 0
        let pointerX = 0
        let dragging = false

        const dots = Array.from(root.querySelectorAll<HTMLElement>('[data-dc-carousel-dot]'))
        const prev = root.querySelector<HTMLElement>('[data-dc-carousel-prev]')
        const next = root.querySelector<HTMLElement>('[data-dc-carousel-next]')

        const render = (): void => {
            track.style.transform = `translateX(-${index * 100}%)`
            dots.forEach((dot) => {
                const i = Number(dot.dataset.dcCarouselDot || '0')
                dot.classList.toggle('is-active', i === index)
            })
        }

        const go = (nextIndex: number): void => {
            index = (nextIndex + slides.length) % slides.length
            render()
        }

        prev?.addEventListener('click', () => go(index - 1))
        next?.addEventListener('click', () => go(index + 1))
        dots.forEach((dot) => {
            dot.addEventListener('click', () => go(Number(dot.dataset.dcCarouselDot || '0')))
        })

        root.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault()
                go(index - 1)
            }
            if (event.key === 'ArrowRight') {
                event.preventDefault()
                go(index + 1)
            }
        })

        const viewport = root.querySelector<HTMLElement>('.dc-carousel-viewport')
        viewport?.addEventListener('pointerdown', (event) => {
            dragging = true
            pointerX = event.clientX
            viewport.setPointerCapture(event.pointerId)
        })
        viewport?.addEventListener('pointerup', (event) => {
            if (!dragging) return
            dragging = false
            const delta = event.clientX - pointerX
            if (Math.abs(delta) > 40) {
                go(delta < 0 ? index + 1 : index - 1)
            }
        })

        render()
    })
}

export function initResumeDownloads(): void {
    document.querySelectorAll<HTMLElement>('[data-dc-resume-download]').forEach((root) => {
        const trigger = root.querySelector<HTMLButtonElement>('[data-dc-resume-download-trigger]')
        const menu = root.querySelector<HTMLElement>('[data-dc-resume-download-menu]')
        if (!trigger || !menu) return

        const close = (): void => {
            menu.hidden = true
            root.classList.remove('is-open')
            trigger.setAttribute('aria-expanded', 'false')
        }

        const open = (): void => {
            menu.hidden = false
            root.classList.add('is-open')
            trigger.setAttribute('aria-expanded', 'true')
        }

        trigger.addEventListener('click', (event) => {
            event.preventDefault()
            event.stopPropagation()
            if (menu.hidden) open()
            else close()
        })

        menu.addEventListener('click', (event) => event.stopPropagation())
    })

    document.addEventListener('click', () => {
        document.querySelectorAll<HTMLElement>('[data-dc-resume-download].is-open').forEach((root) => {
            const trigger = root.querySelector<HTMLButtonElement>('[data-dc-resume-download-trigger]')
            const menu = root.querySelector<HTMLElement>('[data-dc-resume-download-menu]')
            if (menu) menu.hidden = true
            root.classList.remove('is-open')
            trigger?.setAttribute('aria-expanded', 'false')
        })
    })

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return
        document.querySelectorAll<HTMLElement>('[data-dc-resume-download].is-open').forEach((root) => {
            const trigger = root.querySelector<HTMLButtonElement>('[data-dc-resume-download-trigger]')
            const menu = root.querySelector<HTMLElement>('[data-dc-resume-download-menu]')
            if (menu) menu.hidden = true
            root.classList.remove('is-open')
            trigger?.setAttribute('aria-expanded', 'false')
        })
    })
}

export function initMobileNav(): void {
    const mode = document.body.dataset.dcMobileNav || 'hamburger'
    if (mode !== 'hamburger') return

    document.querySelectorAll<HTMLElement>('[data-dc-nav-root]').forEach((root) => {
        const toggle = root.querySelector<HTMLButtonElement>('[data-dc-nav-toggle]')
        const nav = root.querySelector<HTMLElement>('[data-dc-primary-nav]')
        if (!toggle || !nav) return

        const setOpen = (open: boolean): void => {
            root.classList.toggle('is-nav-open', open)
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false')
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu')
        }

        toggle.addEventListener('click', (event) => {
            event.preventDefault()
            event.stopPropagation()
            setOpen(!root.classList.contains('is-nav-open'))
        })

        nav.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setOpen(false))
        })
    })

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return
        document.querySelectorAll<HTMLElement>('[data-dc-nav-root].is-nav-open').forEach((root) => {
            const toggle = root.querySelector<HTMLButtonElement>('[data-dc-nav-toggle]')
            root.classList.remove('is-nav-open')
            toggle?.setAttribute('aria-expanded', 'false')
            toggle?.setAttribute('aria-label', 'Open menu')
        })
    })

    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 801px)').matches) {
            document.querySelectorAll<HTMLElement>('[data-dc-nav-root].is-nav-open').forEach((root) => {
                const toggle = root.querySelector<HTMLButtonElement>('[data-dc-nav-toggle]')
                root.classList.remove('is-nav-open')
                toggle?.setAttribute('aria-expanded', 'false')
                toggle?.setAttribute('aria-label', 'Open menu')
            })
        }
    })
}

export function initPublicSite(): void {
    initThemeToggle()
    observeReveals()
    bindParallax()
    initLightbox()
    initGalleryCarousels()
    initResumeDownloads()
    initMobileNav()
}

function boot(): void {
    initPublicSite()
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot)
} else {
    boot()
}
