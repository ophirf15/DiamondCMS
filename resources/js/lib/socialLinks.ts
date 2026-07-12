export type SocialLinkRecord = {
    id: string
    label: string
    url: string
    icon: string
}

export type SocialLinkBlockProps = {
    source?: 'library' | 'custom'
    selection?: 'all' | string[]
    items?: Array<{ label: string, url: string, icon: string }>
    variant?: string
}

export function resolveSocialLinks(
    library: SocialLinkRecord[],
    props: SocialLinkBlockProps,
): Array<{ label: string, url: string, icon: string }> {
    const source = props.source ?? ((props.items?.length ?? 0) > 0 ? 'custom' : 'library')
    if (source === 'custom') {
        return (props.items ?? []).filter((item) => item.label || item.url)
    }

    const selection = props.selection ?? 'all'
    if (selection === 'all') {
        return library.map(({ label, url, icon }) => ({ label, url, icon }))
    }

    if (!Array.isArray(selection)) {
        return []
    }

    const map = new Map(library.map((link) => [link.id, link]))

    return selection
        .map((id) => map.get(id))
        .filter((link): link is SocialLinkRecord => !!link)
        .map(({ label, url, icon }) => ({ label, url, icon }))
}

export function resolveFooterSocialLinks(
    library: SocialLinkRecord[],
    linkIds: string[] | undefined,
    legacy: Array<{ label: string, url: string, icon: string }> | undefined,
): Array<{ label: string, url: string, icon: string }> {
    if (linkIds?.length) {
        const resolved = resolveSocialLinks(library, { source: 'library', selection: linkIds })
        if (resolved.length) {
            return resolved
        }
    }

    return legacy ?? []
}
