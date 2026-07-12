/** Turn API failure bodies into a short toast-safe string (never dump stacks). */
export function apiErrorMessage(body: string, status: number, fallback = 'Request failed'): string {
    const raw = body.trim()
    let message = raw || `${fallback} (${status})`

    if (raw.startsWith('{') || raw.startsWith('[')) {
        try {
            const parsed = JSON.parse(raw) as { message?: unknown }
            if (typeof parsed.message === 'string' && parsed.message.trim() !== '') {
                message = parsed.message.trim()
            }
        } catch {
            // keep raw
        }
    }

    // Laravel Http client / debug pages often embed the useful line before a huge JSON dump.
    const httpMatch = message.match(/^HTTP request returned status code (\d+):\s*(\{.*)/s)
    if (httpMatch) {
        try {
            const nested = JSON.parse(httpMatch[2].replace(/\s*\(truncated\.\.\.\)\s*$/, '')) as { message?: unknown }
            if (typeof nested.message === 'string' && nested.message.trim() !== '') {
                message = nested.message.trim()
            } else {
                message = `GitHub request failed (HTTP ${httpMatch[1]})`
            }
        } catch {
            message = `GitHub request failed (HTTP ${httpMatch[1]})`
        }
    }

    if (message.includes('"exception"') || message.includes('"trace"')) {
        const firstLine = message.split(/\n/)[0]?.trim() ?? message
        message = firstLine.length > 0 && firstLine.length < 200
            ? firstLine
            : `${fallback} (${status})`
    }

    if (message.length > 180) {
        message = `${message.slice(0, 177).trimEnd()}…`
    }

    return message
}
