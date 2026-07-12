import type { BuilderBlock } from './BuilderBlockView.vue'

function id(): string {
    return crypto.randomUUID()
}

function block(type: string, props: Record<string, unknown> = {}, children?: BuilderBlock[]): BuilderBlock {
    return { id: id(), type, props, children }
}

export type SectionKit = {
    id: string
    label: string
    description: string
    build: () => BuilderBlock
}

export const SECTION_KITS: SectionKit[] = [
    {
        id: 'hero-centered',
        label: 'Hero',
        description: 'Big headline, supporting text, and a call-to-action.',
        build: () => block('section', { padding: '5rem 1.5rem' }, [
            block('heading', { level: 1, text: 'Build a site that feels like you' }),
            block('text', { text: 'Share your work, story, and résumé in one polished personal website — no code required.' }),
            block('button', { text: 'See my work', url: '/projects' }),
        ]),
    },
    {
        id: 'hero-split',
        label: 'About split hero',
        description: 'Portrait with name overlay beside bio and socials.',
        build: () => block('section', { padding: '4rem 1.5rem' }, [
            block('columns', { columns: 2 }, [
                block('section', { padding: '0.5rem' }, [
                    block('image', { src: '', alt: 'Portrait' }),
                    block('heading', { level: 2, text: 'Your Name' }),
                    block('text', { text: 'Designer & Developer' }),
                ]),
                block('section', { padding: '0.5rem' }, [
                    block('heading', { level: 2, text: 'About Me' }),
                    block('text', { text: 'A short introduction about your background, values, and the kind of work you love doing.' }),
                    block('heading', { level: 3, text: 'Connect With Me' }),
                    block('social-links', {
                        variant: 'icons-labels',
                        items: [
                            { label: 'X', url: '#', icon: 'x' },
                            { label: 'LinkedIn', url: '#', icon: 'linkedin' },
                            { label: 'Email', url: 'mailto:hello@example.com', icon: 'email' },
                        ],
                    }),
                ]),
            ]),
        ]),
    },
    {
        id: 'hero-availability',
        label: 'Availability hero',
        description: 'Badge, role accent, stats, dual CTAs, and portrait.',
        build: () => block('section', { padding: '5rem 1.5rem' }, [
            block('columns', { columns: 2 }, [
                block('section', { padding: '0.5rem' }, [
                    block('text', { text: '● Available for new projects' }),
                    block('heading', { level: 1, text: 'Hello, I’m Your Name' }),
                    block('text', { text: 'Applied AI Engineer & Software Developer' }),
                    block('text', { text: 'Designing and building production-ready systems that solve real business problems.' }),
                    block('stats-row', {
                        items: [
                            { value: '10+', label: 'Years' },
                            { value: '4+', label: 'Applied AI' },
                            { value: '100+', label: 'Projects' },
                        ],
                    }),
                    block('button', { text: 'View My Work', url: '/projects' }),
                    block('button', { text: "Let's Talk", url: '/contact' }),
                ]),
                block('section', { padding: '0.5rem' }, [
                    block('image', { src: '', alt: 'Portrait' }),
                ]),
            ]),
        ]),
    },
    {
        id: 'features-3',
        label: 'Feature grid',
        description: 'Three-column highlights for skills or services.',
        build: () => block('section', { padding: '4rem 1.5rem' }, [
            block('heading', { level: 2, text: 'What I bring' }),
            block('columns', { columns: 3 }, [
                block('section', { padding: '1rem' }, [
                    block('heading', { level: 3, text: 'Strategy' }),
                    block('text', { text: 'Clear goals, practical plans, and measurable outcomes.' }),
                ]),
                block('section', { padding: '1rem' }, [
                    block('heading', { level: 3, text: 'Craft' }),
                    block('text', { text: 'Thoughtful design and reliable technical execution.' }),
                ]),
                block('section', { padding: '1rem' }, [
                    block('heading', { level: 3, text: 'Delivery' }),
                    block('text', { text: 'Ship polished work on time without the chaos.' }),
                ]),
            ]),
        ]),
    },
    {
        id: 'cta-band',
        label: 'Call to action',
        description: 'Short band that pushes visitors to contact you.',
        build: () => block('section', { padding: '4rem 1.5rem' }, [
            block('heading', { level: 2, text: 'Let’s work together' }),
            block('text', { text: 'Have a project in mind? I’d love to hear what you’re building.' }),
            block('button', { text: 'Contact me', url: '/contact' }),
        ]),
    },
    {
        id: 'about-split',
        label: 'About split',
        description: 'Two-column about layout with photo placeholder.',
        build: () => block('section', { padding: '4rem 1.5rem' }, [
            block('columns', { columns: 2 }, [
                block('section', { padding: '0.5rem' }, [
                    block('image', { src: '', alt: 'Portrait' }),
                ]),
                block('section', { padding: '0.5rem' }, [
                    block('heading', { level: 2, text: 'About me' }),
                    block('text', { text: 'A short introduction about your background, values, and the kind of work you love doing.' }),
                    block('button', { text: 'Download résumé', url: '/resume' }),
                ]),
            ]),
        ]),
    },
    {
        id: 'portfolio-band',
        label: 'Projects band',
        description: 'Headline plus featured project grid block.',
        build: () => block('section', { padding: '4rem 1.5rem' }, [
            block('heading', { level: 2, text: 'Selected work' }),
            block('text', { text: 'A few projects I’m proud of.' }),
            block('portfolio-featured-grid', { limit: 6 }),
        ]),
    },
    {
        id: 'contact-simple',
        label: 'Contact',
        description: 'Contact intro with form placeholder.',
        build: () => block('section', { padding: '4rem 1.5rem' }, [
            block('heading', { level: 2, text: 'Get in touch' }),
            block('text', { text: 'Send a note and I’ll get back to you soon.' }),
            block('form', { slug: 'contact' }),
        ]),
    },
]
