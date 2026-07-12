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

export function initPublicSite(): void {
    initThemeToggle()
    observeReveals()
    bindParallax()
}

function boot(): void {
    initPublicSite()
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot)
} else {
    boot()
}
