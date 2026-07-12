import * as simpleIcons from 'simple-icons'

export type BrandIcon = {
    slug: string
    title: string
    hex: string
    path: string
    source: 'simple-icons' | 'local'
}

/** Brands removed from Simple Icons (trademark) or not brand marks — local SVG paths. */
const LOCAL_FALLBACKS: BrandIcon[] = [
    {
        slug: 'linkedin',
        title: 'LinkedIn',
        hex: '0A66C2',
        path: 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
        source: 'local',
    },
    {
        slug: 'email',
        title: 'Email',
        hex: 'EA4335',
        path: 'M1.5 4.5A2.5 2.5 0 014 2h16a2.5 2.5 0 012.5 2.5v15A2.5 2.5 0 0120 22H4a2.5 2.5 0 01-2.5-2.5v-15zm2.1.5 8.4 6.3L20.4 5H3.6zm17.4 1.9-8.1 6.08a1.5 1.5 0 01-1.8 0L2.999 6.9V19.5c0 .28.22.5.5.5h17a.5.5 0 00.5-.5V6.9z',
        source: 'local',
    },
    {
        slug: 'phone',
        title: 'Phone',
        hex: '34A853',
        path: 'M6.62 10.79a15.15 15.15 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.02l-2.2 2.19z',
        source: 'local',
    },
    {
        slug: 'website',
        title: 'Website',
        hex: '6366F1',
        path: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z',
        source: 'local',
    },
]

export const POPULAR_SOCIAL_SLUGS = [
    'linkedin',
    'instagram',
    'github',
    'x',
    'youtube',
    'facebook',
    'tiktok',
    'threads',
    'bluesky',
    'discord',
    'telegram',
    'whatsapp',
    'spotify',
    'medium',
    'behance',
    'dribbble',
    'mastodon',
    'gmail',
    'email',
    'phone',
    'website',
] as const

type SimpleIconLike = {
    title: string
    slug: string
    hex: string
    path: string
}

function isSimpleIcon(value: unknown): value is SimpleIconLike {
    if (!value || typeof value !== 'object') return false
    const row = value as Record<string, unknown>
    return typeof row.slug === 'string' && typeof row.title === 'string' && typeof row.path === 'string' && typeof row.hex === 'string'
}

let cachedCatalog: BrandIcon[] | null = null
let cachedBySlug: Map<string, BrandIcon> | null = null

export function brandIconCatalog(): BrandIcon[] {
    if (cachedCatalog) return cachedCatalog

    const fromPackage = Object.values(simpleIcons)
        .filter(isSimpleIcon)
        .map((icon): BrandIcon => ({
            slug: icon.slug,
            title: icon.title,
            hex: icon.hex,
            path: icon.path,
            source: 'simple-icons',
        }))

    const localOnly = LOCAL_FALLBACKS.filter((local) => !fromPackage.some((icon) => icon.slug === local.slug))
    cachedCatalog = [...localOnly, ...fromPackage].sort((a, b) => a.title.localeCompare(b.title))
    cachedBySlug = new Map(cachedCatalog.map((icon) => [icon.slug, icon]))
    return cachedCatalog
}

export function getBrandIcon(slug: string | null | undefined): BrandIcon | null {
    if (!slug) return null
    if (!cachedBySlug) brandIconCatalog()
    return cachedBySlug?.get(slug) ?? null
}

export function popularBrandIcons(): BrandIcon[] {
    return POPULAR_SOCIAL_SLUGS
        .map((slug) => getBrandIcon(slug))
        .filter((icon): icon is BrandIcon => !!icon)
}

export function searchBrandIcons(query: string, limit = 80): BrandIcon[] {
    const q = query.trim().toLowerCase()
    const catalog = brandIconCatalog()
    if (!q) return popularBrandIcons()
    const scored = catalog
        .map((icon) => {
            const title = icon.title.toLowerCase()
            const slug = icon.slug.toLowerCase()
            let score = 0
            if (slug === q || title === q) score = 100
            else if (slug.startsWith(q) || title.startsWith(q)) score = 80
            else if (slug.includes(q) || title.includes(q)) score = 40
            return { icon, score }
        })
        .filter((row) => row.score > 0)
        .sort((a, b) => b.score - a.score || a.icon.title.localeCompare(b.icon.title))
        .slice(0, limit)
    return scored.map((row) => row.icon)
}

export function iconSvgMarkup(icon: BrandIcon, colored = true): string {
    const fill = colored ? `#${icon.hex}` : 'currentColor'
    return `<svg role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><title>${escapeXml(icon.title)}</title><path fill="${fill}" d="${icon.path}"/></svg>`
}

export function cdnIconUrl(slug: string, hex?: string): string {
    const base = `https://cdn.simpleicons.org/${encodeURIComponent(slug)}`
    return hex ? `${base}/${encodeURIComponent(hex)}` : base
}

export function guessIconSlug(label: string, url: string): string {
    const hay = `${label} ${url}`.toLowerCase()
    const rules: Array<[RegExp, string]> = [
        [/linkedin/, 'linkedin'],
        [/instagram|\binsta\b/, 'instagram'],
        [/github/, 'github'],
        [/\bx\.com\b|\btwitter\b|\bx\b/, 'x'],
        [/youtube|youtu\.be/, 'youtube'],
        [/facebook|fb\.com/, 'facebook'],
        [/tiktok/, 'tiktok'],
        [/threads\.net/, 'threads'],
        [/bsky\.app|bluesky/, 'bluesky'],
        [/discord/, 'discord'],
        [/telegram|t\.me/, 'telegram'],
        [/whatsapp|wa\.me/, 'whatsapp'],
        [/spotify/, 'spotify'],
        [/medium\.com/, 'medium'],
        [/behance/, 'behance'],
        [/dribbble/, 'dribbble'],
        [/mastodon/, 'mastodon'],
        [/mailto:|@|email|gmail/, 'email'],
        [/^tel:|phone|call/, 'phone'],
    ]
    for (const [re, slug] of rules) {
        if (re.test(hay)) return slug
    }
    return 'website'
}

function escapeXml(value: string): string {
    return value.replace(/[<>&"']/g, (ch) => ({
        '<': '&lt;',
        '>': '&gt;',
        '&': '&amp;',
        '"': '&quot;',
        "'": '&apos;',
    }[ch] || ch))
}
