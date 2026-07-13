/**
 * Ambient background effects (Vanta / Aceternity-style).
 * Fixed canvas only — never touches document overflow or content transforms.
 */

type AtmosphereMode = 'none' | 'aurora' | 'mesh' | 'particles' | 'waves' | 'fog'

type Rgb = { r: number, g: number, b: number }

function prefersReducedMotion(): boolean {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

function cssVar(name: string): string {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim()
}

function parseColor(input: string): Rgb | null {
    const value = input.trim()
    if (!value || value.startsWith('var(')) return null

    const hex = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i)
    if (hex) {
        let h = hex[1]
        if (h.length === 3) h = h.split('').map((c) => c + c).join('')
        return {
            r: Number.parseInt(h.slice(0, 2), 16),
            g: Number.parseInt(h.slice(2, 4), 16),
            b: Number.parseInt(h.slice(4, 6), 16),
        }
    }

    const rgb = value.match(/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)/i)
    if (rgb) {
        return { r: Number(rgb[1]), g: Number(rgb[2]), b: Number(rgb[3]) }
    }

    return null
}

function tokenColor(...candidates: string[]): Rgb {
    for (const candidate of candidates) {
        const parsed = parseColor(candidate.startsWith('--') ? cssVar(candidate) : candidate)
        if (parsed) return parsed
    }
    return { r: 13, g: 92, b: 77 }
}

function resolveColors(): { a: Rgb, b: Rgb, intensity: number } {
    const a = tokenColor('--dc-atmosphere-a', '--dc-primary', '#0d5c4d')
    const b = tokenColor('--dc-atmosphere-b', '--dc-accent', '#a67c3d')
    const intensity = Number.parseFloat(cssVar('--dc-atmosphere-intensity') || '0.78')
    return { a, b, intensity: Number.isFinite(intensity) ? Math.max(0.4, intensity) : 0.78 }
}

function modeFromDataset(): AtmosphereMode {
    const raw = (document.body.dataset.dcAtmosphereAnim || 'aurora').toLowerCase()
    const aliases: Record<string, AtmosphereMode> = {
        drift: 'aurora',
        orbs: 'mesh',
        grain: 'particles',
    }
    const mapped = aliases[raw] || raw
    if (mapped === 'aurora' || mapped === 'mesh' || mapped === 'particles' || mapped === 'waves' || mapped === 'fog' || mapped === 'none') {
        return mapped
    }
    return 'aurora'
}

function rgba(c: Rgb, a: number): string {
    return `rgba(${c.r},${c.g},${c.b},${Math.max(0, Math.min(1, a))})`
}

export function initAtmosphere(): void {
    if (prefersReducedMotion()) return
    if (document.body.dataset.dcMotion === 'off' || document.body.dataset.dcMotionLevel === 'off') return

    const mode = modeFromDataset()
    const host = document.querySelector<HTMLElement>('.dc-atmosphere-layer')
    if (!host) return

    host.dataset.dcAtmosphereReady = '1'
    host.dataset.dcAtmosphereMode = mode
    if (mode === 'none') return

    let canvas = host.querySelector<HTMLCanvasElement>('.dc-atmosphere-canvas')
    if (!canvas) {
        canvas = document.createElement('canvas')
        canvas.className = 'dc-atmosphere-canvas'
        canvas.setAttribute('aria-hidden', 'true')
        host.appendChild(canvas)
    }

    const ctx = canvas.getContext('2d', { alpha: true })
    if (!ctx) return

    let width = 0
    let height = 0
    let dpr = 1
    let raf = 0
    let running = true
    const start = performance.now()

    const resize = (): void => {
        dpr = Math.min(window.devicePixelRatio || 1, 2)
        width = Math.max(window.innerWidth, 1)
        height = Math.max(window.innerHeight, 1)
        canvas!.width = Math.floor(width * dpr)
        canvas!.height = Math.floor(height * dpr)
        canvas!.style.width = `${width}px`
        canvas!.style.height = `${height}px`
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0)
    }

    const particles = Array.from({ length: 56 }, (_, i) => ({
        x: Math.random(),
        y: Math.random(),
        r: 1.4 + (i % 5) * 0.7,
        s: 0.12 + (i % 7) * 0.045,
        p: Math.random() * Math.PI * 2,
    }))

    // Soft blobs with drifting centers + pulsing radius (Aceternity-style aurora).
    const drawAurora = (t: number, colors: ReturnType<typeof resolveColors>): void => {
        ctx.clearRect(0, 0, width, height)
        const ribbons = [
            { x: 0.2, y: 0.25, sx: 0.22, sy: 0.18, speed: 0.55, phase: 0, c: colors.a, a: 0.5 },
            { x: 0.75, y: 0.3, sx: 0.2, sy: 0.2, speed: 0.42, phase: 1.2, c: colors.b, a: 0.45 },
            { x: 0.45, y: 0.7, sx: 0.28, sy: 0.16, speed: 0.35, phase: 2.1, c: colors.a, a: 0.4 },
            { x: 0.6, y: 0.15, sx: 0.18, sy: 0.22, speed: 0.48, phase: 3.4, c: colors.b, a: 0.38 },
            { x: 0.15, y: 0.65, sx: 0.2, sy: 0.24, speed: 0.38, phase: 4.5, c: colors.a, a: 0.35 },
        ]
        for (const ribbon of ribbons) {
            const px = (ribbon.x + Math.sin(t * ribbon.speed + ribbon.phase) * ribbon.sx) * width
            const py = (ribbon.y + Math.cos(t * ribbon.speed * 0.85 + ribbon.phase) * ribbon.sy) * height
            const pulse = 1 + Math.sin(t * ribbon.speed * 1.4 + ribbon.phase) * 0.18
            const radius = Math.max(width, height) * (0.34 + ribbon.a * 0.2) * pulse
            const g = ctx.createRadialGradient(px, py, 0, px, py, radius)
            g.addColorStop(0, rgba(ribbon.c, ribbon.a * colors.intensity))
            g.addColorStop(0.35, rgba(ribbon.c, ribbon.a * 0.45 * colors.intensity))
            g.addColorStop(1, rgba(ribbon.c, 0))
            ctx.fillStyle = g
            ctx.beginPath()
            ctx.arc(px, py, radius, 0, Math.PI * 2)
            ctx.fill()
        }

        // Sweeping band so motion reads clearly even with huge soft blobs.
        ctx.save()
        ctx.globalCompositeOperation = 'lighter'
        const bandY = height * (0.35 + Math.sin(t * 0.4) * 0.2)
        const band = ctx.createLinearGradient(0, bandY - 80, 0, bandY + 80)
        band.addColorStop(0, rgba(colors.b, 0))
        band.addColorStop(0.5, rgba(colors.b, 0.22 * colors.intensity))
        band.addColorStop(1, rgba(colors.a, 0))
        ctx.fillStyle = band
        ctx.fillRect(0, bandY - 90, width, 180)
        ctx.restore()
    }

    // Mesh points drift farther and pulse so the field clearly morphs.
    const drawMesh = (t: number, colors: ReturnType<typeof resolveColors>): void => {
        ctx.clearRect(0, 0, width, height)
        const cols = 5
        const rows = 4
        for (let y = 0; y <= rows; y += 1) {
            for (let x = 0; x <= cols; x += 1) {
                const phase = x * 0.9 + y * 1.3
                const px = (x / cols) * width + Math.sin(t * 0.55 + phase) * Math.min(width * 0.12, 110)
                const py = (y / rows) * height + Math.cos(t * 0.48 + phase * 0.8) * Math.min(height * 0.12, 90)
                const c = (x + y) % 2 === 0 ? colors.a : colors.b
                const pulse = 1 + Math.sin(t * 0.7 + phase) * 0.25
                const radius = Math.max(width, height) * 0.22 * pulse
                const g = ctx.createRadialGradient(px, py, 0, px, py, radius)
                g.addColorStop(0, rgba(c, 0.38 * colors.intensity))
                g.addColorStop(0.5, rgba(c, 0.14 * colors.intensity))
                g.addColorStop(1, rgba(c, 0))
                ctx.fillStyle = g
                ctx.beginPath()
                ctx.arc(px, py, radius, 0, Math.PI * 2)
                ctx.fill()
            }
        }
    }

    const drawParticles = (t: number, colors: ReturnType<typeof resolveColors>): void => {
        ctx.clearRect(0, 0, width, height)
        for (const p of particles) {
            const x = ((p.x + Math.sin(t * p.s + p.p) * 0.05 + 1) % 1) * width
            const y = ((p.y + t * p.s * 0.025) % 1) * height
            const c = p.r > 2.4 ? colors.b : colors.a
            const glow = ctx.createRadialGradient(x, y, 0, x, y, p.r * 4)
            glow.addColorStop(0, rgba(c, 0.55 * colors.intensity))
            glow.addColorStop(1, rgba(c, 0))
            ctx.fillStyle = glow
            ctx.beginPath()
            ctx.arc(x, y, p.r * 4, 0, Math.PI * 2)
            ctx.fill()
        }
    }

    const drawWaves = (t: number, colors: ReturnType<typeof resolveColors>): void => {
        ctx.clearRect(0, 0, width, height)
        const bands = [
            { amp: 34, freq: 0.007, speed: 0.55, y: 0.32, c: colors.a, a: 0.22 },
            { amp: 42, freq: 0.0055, speed: 0.4, y: 0.52, c: colors.b, a: 0.18 },
            { amp: 26, freq: 0.009, speed: 0.7, y: 0.72, c: colors.a, a: 0.16 },
        ]
        for (const band of bands) {
            ctx.beginPath()
            ctx.moveTo(0, height)
            for (let x = 0; x <= width; x += 6) {
                const y = height * band.y + Math.sin(x * band.freq + t * band.speed) * band.amp
                ctx.lineTo(x, y)
            }
            ctx.lineTo(width, height)
            ctx.closePath()
            ctx.fillStyle = rgba(band.c, band.a * colors.intensity)
            ctx.fill()
        }
    }

    // Fog banks drift across a large path and breathe in size.
    const drawFog = (t: number, colors: ReturnType<typeof resolveColors>): void => {
        ctx.clearRect(0, 0, width, height)
        const banks = [
            { x: 0.15, y: 0.25, dx: 0.28, dy: 0.18, speed: 0.32, phase: 0.2, c: colors.a, s: 0.38 },
            { x: 0.7, y: 0.35, dx: 0.24, dy: 0.22, speed: 0.28, phase: 1.4, c: colors.b, s: 0.42 },
            { x: 0.4, y: 0.7, dx: 0.3, dy: 0.16, speed: 0.36, phase: 2.6, c: colors.a, s: 0.4 },
            { x: 0.85, y: 0.75, dx: 0.2, dy: 0.2, speed: 0.3, phase: 3.8, c: colors.b, s: 0.36 },
            { x: 0.25, y: 0.55, dx: 0.26, dy: 0.2, speed: 0.34, phase: 5.1, c: colors.a, s: 0.34 },
            { x: 0.55, y: 0.2, dx: 0.22, dy: 0.24, speed: 0.26, phase: 6.2, c: colors.b, s: 0.4 },
        ]
        for (const bank of banks) {
            const px = (bank.x + Math.sin(t * bank.speed + bank.phase) * bank.dx) * width
            const py = (bank.y + Math.cos(t * bank.speed * 0.9 + bank.phase) * bank.dy) * height
            const pulse = 1 + Math.sin(t * bank.speed * 1.2 + bank.phase) * 0.22
            const radius = Math.max(width, height) * bank.s * pulse
            const g = ctx.createRadialGradient(px, py, 0, px, py, radius)
            g.addColorStop(0, rgba(bank.c, 0.36 * colors.intensity))
            g.addColorStop(0.45, rgba(bank.c, 0.16 * colors.intensity))
            g.addColorStop(1, rgba(bank.c, 0))
            ctx.fillStyle = g
            ctx.beginPath()
            ctx.arc(px, py, radius, 0, Math.PI * 2)
            ctx.fill()
        }
    }

    const frame = (now: number): void => {
        if (!running) return
        const t = (now - start) / 1000
        const colors = resolveColors()
        if (mode === 'aurora') drawAurora(t, colors)
        else if (mode === 'mesh') drawMesh(t, colors)
        else if (mode === 'particles') drawParticles(t, colors)
        else if (mode === 'waves') drawWaves(t, colors)
        else drawFog(t, colors)
        raf = window.requestAnimationFrame(frame)
    }

    const onVisibility = (): void => {
        if (document.hidden) {
            running = false
            window.cancelAnimationFrame(raf)
            return
        }
        if (!running) {
            running = true
            raf = window.requestAnimationFrame(frame)
        }
    }

    resize()
    window.addEventListener('resize', resize, { passive: true })
    document.addEventListener('visibilitychange', onVisibility)
    raf = window.requestAnimationFrame(frame)
}
