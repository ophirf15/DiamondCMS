<?php

declare(strict_types=1);

namespace App\Domains\Builder\Support;

use Illuminate\Support\Str;

final class StarterTemplates
{
    /**
     * @return array<int, array{
     *     name: string,
     *     category: string,
     *     blurb: string,
     *     preview_theme: string,
     *     document: array<string, mixed>
     * }>
     */
    public static function definitions(): array
    {
        return [
            [
                'name' => 'Ophir professional',
                'category' => 'page',
                'blurb' => 'Dark sidebar personal site — About, Resume timeline, Portfolio, Contact.',
                'preview_theme' => 'dark-teal',
                'document' => self::doc('Ophir professional', 'sidebar-dark', 'dark-teal', [
                    self::section('3rem 1.5rem', [
                        self::heading(2, 'About'),
                        self::columns(2, [
                            self::section('0.5rem', [
                                self::block('image', ['src' => '/brand/logo-gold-on-charcoal.svg', 'alt' => 'Portrait']),
                            ]),
                            self::section('0.5rem', [
                                self::heading(1, 'Ophir Yahalom'),
                                self::text('Property manager, operator, and builder focused on calm resident experiences and reliable site ops.'),
                                self::block('social-links', [
                                    'variant' => 'icons-labels',
                                    'items' => [
                                        ['label' => 'Email', 'url' => 'mailto:contact@ophiryahalom.com', 'icon' => 'email'],
                                        ['label' => 'Phone', 'url' => 'tel:14158606090', 'icon' => 'phone'],
                                    ],
                                ]),
                            ]),
                        ]),
                    ]),
                    self::section('3rem 1.5rem', [
                        self::heading(2, 'Resume'),
                        self::block('button', ['text' => 'Download Resume', 'url' => '/resume']),
                        self::block('timeline', [
                            'items' => [
                                [
                                    'date' => 'June 2019 — Current',
                                    'title' => 'Multi-Site Property Manager',
                                    'organization' => 'Woodmont Real Estate Services',
                                    'bullets' => [
                                        'Train and manage leasing and maintenance staff across Mill Valley & Sausalito.',
                                        'Oversee capital improvements, budgeting, and NOI performance.',
                                    ],
                                ],
                                [
                                    'date' => 'March 2019 — June 2019',
                                    'title' => 'Assistant Community Manager',
                                    'organization' => 'Greystar, Larkspur',
                                    'bullets' => [
                                        'Led leasing staff, notices, and vendor coordination for 248 units.',
                                    ],
                                ],
                            ],
                        ]),
                        self::heading(3, 'Skills'),
                        self::text('Realpage · Yardi CRM · Outstanding customer service · HTML/CSS/JS · Image editing'),
                    ]),
                    self::section('3rem 1.5rem', [
                        self::heading(2, 'Portfolio'),
                        self::block('portfolio-featured-grid', ['limit' => 4]),
                    ]),
                    self::section('3rem 1.5rem', [
                        self::heading(2, 'Contact'),
                        self::text('Please contact me for any inquiries.'),
                        self::block('form', ['slug' => 'contact']),
                    ]),
                ]),
            ],
            [
                'name' => 'Dark applied-AI hero',
                'category' => 'page',
                'blurb' => 'Navy hero with availability badge, stats, dual CTAs, and portrait.',
                'preview_theme' => 'dark-navy',
                'document' => self::doc('Dark applied-AI hero', 'default', 'dark-navy', [
                    self::section('5rem 1.5rem', [
                        self::columns(2, [
                            self::section('0.75rem', [
                                self::text('● Available for new projects'),
                                self::heading(1, 'Hello, I’m Alex Chen'),
                                self::text('Applied AI Engineer & Software Developer'),
                                self::text('Designing production-ready AI systems, agentic workflows, and automation that solve real business problems.'),
                                self::block('stats-row', [
                                    'items' => [
                                        ['value' => '12+', 'label' => 'Years Software'],
                                        ['value' => '4+', 'label' => 'Applied AI'],
                                        ['value' => '100+', 'label' => 'Projects'],
                                    ],
                                ]),
                                self::button('View My Work', '/projects'),
                                self::button("Let's Talk", '/contact'),
                            ]),
                            self::section('0.75rem', [
                                self::block('image', ['src' => '/brand/logo-white.svg', 'alt' => 'Portrait']),
                            ]),
                        ]),
                    ]),
                    self::section('4rem 1.5rem', [
                        self::heading(2, 'Selected work'),
                        self::block('portfolio-featured-grid', ['limit' => 3]),
                    ]),
                ]),
            ],
            [
                'name' => 'Light floating-tech',
                'category' => 'page',
                'blurb' => 'Airy light hero with accent role, dual CTAs, and partner strip.',
                'preview_theme' => 'light',
                'document' => self::doc('Light floating-tech', 'default', 'light', [
                    self::section('5rem 1.5rem', [
                        self::columns(2, [
                            self::section('0.75rem', [
                                self::text('Hello Mate 👋'),
                                self::heading(1, "I'm Michle Smith a Web Developer"),
                                self::text('I build polished web products with React, Laravel, and careful UX craft.'),
                                self::button('Book a Call', '/contact'),
                                self::button('Download CV', '/resume'),
                            ]),
                            self::section('0.75rem', [
                                self::block('image', ['src' => '/brand/logo-primary-gold.svg', 'alt' => 'Portrait']),
                            ]),
                        ]),
                    ]),
                    self::section('3rem 1.5rem', [
                        self::heading(3, 'Proud marketing partners with'),
                        self::block('stats-row', [
                            'items' => [
                                ['value' => 'Google', 'label' => 'Partner'],
                                ['value' => 'Amazon', 'label' => 'Partner'],
                                ['value' => 'Spotify', 'label' => 'Partner'],
                            ],
                        ]),
                    ]),
                    self::section('4rem 1.5rem', [
                        self::heading(2, 'Projects'),
                        self::block('portfolio-featured-grid', ['limit' => 4]),
                    ]),
                ]),
            ],
            [
                'name' => 'Split portrait',
                'category' => 'page',
                'blurb' => 'Half color panel + full-bleed portrait with vertical socials.',
                'preview_theme' => 'split-teal',
                'document' => self::doc('Split portrait', 'default', 'split-teal', [
                    self::section('0', [
                        self::columns(2, [
                            self::section('4rem 2rem', [
                                self::heading(3, 'Daniel Martinez'),
                                self::text('Bio · Video · Photos · Contact'),
                                self::heading(1, "Hey, I'm Daniel Martinez"),
                                self::text('Actor / Performer / Model'),
                                self::block('social-links', [
                                    'variant' => 'icons-labels',
                                    'items' => [
                                        ['label' => 'Instagram', 'url' => '#', 'icon' => 'instagram'],
                                        ['label' => 'YouTube', 'url' => '#', 'icon' => 'youtube'],
                                        ['label' => 'Email', 'url' => 'mailto:hello@example.com', 'icon' => 'email'],
                                    ],
                                ]),
                            ]),
                            self::section('0', [
                                self::block('image', ['src' => '/brand/logo-gold-on-charcoal.svg', 'alt' => 'Portrait']),
                            ]),
                        ]),
                    ]),
                    self::section('4rem 1.5rem', [
                        self::heading(2, 'About'),
                        self::text('A bold minimalist split layout for performers and creatives who lead with presence.'),
                    ]),
                ]),
            ],
            [
                'name' => 'Dark mosaic agency',
                'category' => 'page',
                'blurb' => 'Asymmetric dark cards, big metrics, and neon accents.',
                'preview_theme' => 'dark-neon',
                'document' => self::doc('Dark mosaic agency', 'default', 'dark-neon', [
                    self::section('5rem 1.5rem', [
                        self::heading(1, 'We Create World Advancing Software With Vision and Passion'),
                        self::text('Premium product engineering for teams that need speed without sacrificing craft.'),
                        self::button('Get started now', '/contact'),
                        self::block('stats-row', [
                            'items' => [
                                ['value' => '500+', 'label' => 'Projects'],
                                ['value' => '60%', 'label' => 'Faster launches'],
                                ['value' => '120%', 'label' => 'Avg ROI'],
                            ],
                        ]),
                    ]),
                    self::section('3rem 1.5rem', [
                        self::columns(3, [
                            self::section('1rem', [
                                self::heading(3, 'Mobile Applications'),
                                self::text('Native-feel products shipped on tight timelines.'),
                            ]),
                            self::section('1rem', [
                                self::heading(3, 'Integrations'),
                                self::text('Devices, billing, and data pipelines that stay reliable.'),
                            ]),
                            self::section('1rem', [
                                self::heading(3, 'Privacy + Performance'),
                                self::text('Secure by default without slowing the product down.'),
                            ]),
                        ]),
                    ]),
                    self::section('3rem 1.5rem', [
                        self::block('portfolio-featured-grid', ['limit' => 4]),
                    ]),
                ]),
            ],
            [
                'name' => 'Photography portfolio',
                'category' => 'page',
                'blurb' => 'Image-forward gallery with sparse typography.',
                'preview_theme' => 'dark-gallery',
                'document' => self::doc('Photography portfolio', 'default', 'dark-gallery', [
                    self::section('4rem 1.5rem', [
                        self::heading(1, 'Frames that feel honest'),
                        self::text('Editorial and lifestyle photography for brands and people.'),
                    ]),
                    self::section('2rem 1.5rem', [
                        self::block('gallery-grid', [
                            'images' => [
                                ['src' => '/brand/logo-primary-gold.svg', 'alt' => 'Frame 1'],
                                ['src' => '/brand/logo-gold-on-charcoal.svg', 'alt' => 'Frame 2'],
                                ['src' => '/brand/diamond-icon-gold.svg', 'alt' => 'Frame 3'],
                                ['src' => '/brand/logo-white.svg', 'alt' => 'Frame 4'],
                                ['src' => '/brand/favicon.svg', 'alt' => 'Frame 5'],
                                ['src' => '/brand/logo-primary-gold.svg', 'alt' => 'Frame 6'],
                            ],
                        ]),
                    ]),
                    self::section('4rem 1.5rem', [
                        self::heading(2, 'Book a session'),
                        self::button('Get in touch', '/contact'),
                    ]),
                ]),
            ],
            [
                'name' => 'Dark technical resume',
                'category' => 'resume',
                'blurb' => 'Dark résumé with timeline experience and skills column.',
                'preview_theme' => 'dark-teal',
                'document' => self::doc('Dark technical resume', 'default', 'dark-teal', [
                    self::section('4rem 1.5rem', [
                        self::heading(1, 'Alex Rivera'),
                        self::text('Senior software engineer building reliable systems and calm UX.'),
                        self::block('resume-download', ['text' => 'Download PDF']),
                        self::columns(2, [
                            self::section('0', [
                                self::heading(2, 'Experience'),
                                self::block('timeline', [
                                    'items' => [
                                        [
                                            'date' => '2021 — Current',
                                            'title' => 'Staff Engineer',
                                            'organization' => 'Meridian Labs',
                                            'bullets' => ['Led platform migration and mentoring.'],
                                        ],
                                        [
                                            'date' => '2018 — 2021',
                                            'title' => 'Senior Engineer',
                                            'organization' => 'Northwind',
                                            'bullets' => ['Owned payments reliability.'],
                                        ],
                                    ],
                                ]),
                            ]),
                            self::section('0', [
                                self::heading(2, 'Skills'),
                                self::text('Laravel · TypeScript · PostgreSQL · Redis · AWS'),
                                self::heading(2, 'Education'),
                                self::text('B.S. Computer Science'),
                            ]),
                        ]),
                    ]),
                ]),
            ],
            [
                'name' => 'Minimal professional resume',
                'category' => 'resume',
                'blurb' => 'Clean one-column résumé for traditional roles.',
                'preview_theme' => 'light',
                'document' => self::doc('Minimal professional resume', 'default', 'light', [
                    self::section('5rem 1.5rem', [
                        self::heading(1, 'Jordan Lee'),
                        self::text('Operations leader focused on clarity, service quality, and accountable teams.'),
                        self::divider(),
                        self::heading(2, 'Professional summary'),
                        self::text('Ten years improving processes across property and facilities operations.'),
                        self::heading(2, 'Experience'),
                        self::block('timeline', [
                            'items' => [
                                [
                                    'date' => '2019 — Current',
                                    'title' => 'Director of Operations',
                                    'organization' => 'Harbor Living',
                                    'bullets' => ['Built playbooks that cut turnaround time 30%.'],
                                ],
                            ],
                        ]),
                        self::button('Contact', '/contact'),
                    ]),
                ]),
            ],
            [
                'name' => 'Split-screen resume',
                'category' => 'resume',
                'blurb' => 'Split profile rail and experience column.',
                'preview_theme' => 'dark-navy',
                'document' => self::doc('Split-screen resume', 'default', 'dark-navy', [
                    self::section('4rem 1.5rem', [
                        self::columns(2, [
                            self::section('1rem', [
                                self::block('image', ['src' => '/brand/diamond-icon-gold.svg', 'alt' => 'Profile']),
                                self::heading(1, 'Sam Okonkwo'),
                                self::text('Product designer · Accessibility advocate'),
                                self::text('Bay Area · Open to remote'),
                                self::block('social-links', [
                                    'variant' => 'icons-labels',
                                    'items' => [
                                        ['label' => 'Email', 'url' => 'mailto:hello@example.com', 'icon' => 'email'],
                                        ['label' => 'LinkedIn', 'url' => '#', 'icon' => 'linkedin'],
                                    ],
                                ]),
                            ]),
                            self::section('1rem', [
                                self::heading(2, 'Experience'),
                                self::block('timeline', [
                                    'items' => [
                                        [
                                            'date' => '2020 — Current',
                                            'title' => 'Lead Designer',
                                            'organization' => 'Orbit',
                                            'bullets' => ['Shipped design systems used by 40+ teams.'],
                                        ],
                                    ],
                                ]),
                                self::heading(2, 'Education'),
                                self::text('BFA Interaction Design'),
                            ]),
                        ]),
                    ]),
                ]),
            ],
            [
                'name' => 'Property-management professional',
                'category' => 'page',
                'blurb' => 'Service-focused landing for PM professionals with stats and contact.',
                'preview_theme' => 'dark-teal',
                'document' => self::doc('Property-management professional', 'default', 'dark-teal', [
                    self::section('5rem 1.5rem', [
                        self::heading(1, 'Property care that residents notice'),
                        self::text('Hands-on property management with clear communication and reliable maintenance.'),
                        self::block('stats-row', [
                            'items' => [
                                ['value' => '98%', 'label' => 'Occupancy'],
                                ['value' => '300+', 'label' => 'Units managed'],
                                ['value' => '7+', 'label' => 'Years'],
                            ],
                        ]),
                        self::button('Request a consult', '/contact'),
                    ]),
                    self::section('4rem 1.5rem', [
                        self::heading(2, 'How I help'),
                        self::columns(3, [
                            self::section('1rem', [
                                self::heading(3, 'Leasing'),
                                self::text('Fill units with the right residents, faster.'),
                            ]),
                            self::section('1rem', [
                                self::heading(3, 'Maintenance'),
                                self::text('Track work orders and vendors without chaos.'),
                            ]),
                            self::section('1rem', [
                                self::heading(3, 'Owner reporting'),
                                self::text('Transparent updates owners can trust.'),
                            ]),
                        ]),
                    ]),
                    self::section('3rem 1.5rem', [
                        self::block('form', ['slug' => 'contact']),
                    ]),
                ]),
            ],
            [
                'name' => 'About me bio',
                'category' => 'page',
                'blurb' => 'Polished bio page — portrait, story, skills, socials, and a soft CTA.',
                'preview_theme' => 'light',
                'document' => self::doc('About me', 'default', 'light', [
                    self::section('4rem 1.5rem', [
                        self::columns(2, [
                            self::section('0.75rem', [
                                self::block('image', ['src' => '/brand/logo-gold-on-charcoal.svg', 'alt' => 'Portrait']),
                            ]),
                            self::section('0.75rem', [
                                self::heading(1, 'Hello — I\'m Ophir'),
                                self::text("I spend my days managing multifamily communities and my nights building software, AI tools, and automation that make complicated work simpler.\n\nI believe good technology disappears into the background — letting people spend less time fighting systems and more time solving problems."),
                                self::block('social-links', [
                                    'variant' => 'icons-labels',
                                    'items' => [
                                        ['label' => 'Email', 'url' => 'mailto:hello@example.com', 'icon' => 'email'],
                                        ['label' => 'LinkedIn', 'url' => 'https://linkedin.com', 'icon' => 'linkedin'],
                                        ['label' => 'GitHub', 'url' => 'https://github.com', 'icon' => 'github'],
                                    ],
                                ]),
                            ]),
                        ]),
                    ]),
                    self::section('3.5rem 1.5rem', [
                        self::heading(2, 'What I care about'),
                        self::columns(3, [
                            self::section('1rem', [
                                self::heading(3, 'Operations'),
                                self::text('Clear processes, calm residents, and owners who always know the score.'),
                            ]),
                            self::section('1rem', [
                                self::heading(3, 'Applied AI'),
                                self::text('Practical tools that remove busywork without adding complexity.'),
                            ]),
                            self::section('1rem', [
                                self::heading(3, 'Craft'),
                                self::text('Interfaces that feel intentional — not generic SaaS blue.'),
                            ]),
                        ]),
                    ]),
                    self::section('3rem 1.5rem', [
                        self::block('stats-row', [
                            'items' => [
                                ['value' => '12+', 'label' => 'Years software'],
                                ['value' => '4+', 'label' => 'Applied AI'],
                                ['value' => '100+', 'label' => 'Projects'],
                            ],
                        ]),
                        self::button('Let\'s talk', '/contact'),
                    ]),
                ]),
            ],
            [
                'name' => 'Projects showcase',
                'category' => 'page',
                'blurb' => 'Dedicated projects page with intro, featured grid, timeline, and CTA.',
                'preview_theme' => 'dark-navy',
                'document' => self::doc('Projects', 'default', 'dark-navy', [
                    self::section('4.5rem 1.5rem', [
                        self::heading(1, 'Selected work'),
                        self::text('Case studies and builds across property ops, applied AI, and product craft.'),
                        self::block('stats-row', [
                            'items' => [
                                ['value' => '24', 'label' => 'Shipped'],
                                ['value' => '8', 'label' => 'Open source'],
                                ['value' => '3', 'label' => 'Industries'],
                            ],
                        ]),
                    ]),
                    self::section('3rem 1.5rem', [
                        self::heading(2, 'Featured projects'),
                        self::block('portfolio-featured-grid', ['limit' => 6]),
                    ]),
                    self::section('3.5rem 1.5rem', [
                        self::heading(2, 'Recent highlights'),
                        self::block('timeline', [
                            'items' => [
                                [
                                    'date' => '2025',
                                    'title' => 'Ops automation suite',
                                    'organization' => 'Internal tools',
                                    'bullets' => ['Cut work-order triage time with structured intake and AI summaries.'],
                                ],
                                [
                                    'date' => '2024',
                                    'title' => 'Resident experience portal',
                                    'organization' => 'Multifamily',
                                    'bullets' => ['Self-serve maintenance and clearer status updates for residents.'],
                                ],
                            ],
                        ]),
                    ]),
                    self::section('3rem 1.5rem', [
                        self::heading(2, 'Want something built?'),
                        self::text('Tell me about the problem — I\'ll help shape a practical path forward.'),
                        self::button('Start a conversation', '/contact'),
                    ]),
                ]),
            ],
            [
                'name' => 'Dark bio sidebar',
                'category' => 'page',
                'blurb' => 'Sidebar bio with photo, story, timeline skills, and contact — Ophir-style.',
                'preview_theme' => 'dark-teal',
                'document' => self::doc('About', 'sidebar-dark', 'dark-teal', [
                    self::section('2.5rem 1.5rem', [
                        self::heading(1, 'About'),
                        self::columns(2, [
                            self::section('0.5rem', [
                                self::block('image', ['src' => '/brand/logo-gold-on-charcoal.svg', 'alt' => 'Portrait']),
                            ]),
                            self::section('0.5rem', [
                                self::heading(2, 'Operator · Builder'),
                                self::text('I manage multifamily communities by day and ship software by night. This page is the long version of that story.'),
                                self::block('social-links', [
                                    'variant' => 'icons-labels',
                                    'items' => [
                                        ['label' => 'Email', 'url' => 'mailto:contact@example.com', 'icon' => 'email'],
                                        ['label' => 'LinkedIn', 'url' => 'https://linkedin.com', 'icon' => 'linkedin'],
                                    ],
                                ]),
                            ]),
                        ]),
                    ]),
                    self::section('2.5rem 1.5rem', [
                        self::heading(2, 'Path so far'),
                        self::block('timeline', [
                            'items' => [
                                [
                                    'date' => '2019 — Now',
                                    'title' => 'Multi-site property manager',
                                    'organization' => 'Woodmont',
                                    'bullets' => ['People, capital projects, and resident experience across coastal sites.'],
                                ],
                                [
                                    'date' => 'Earlier',
                                    'title' => 'Builder & experimenter',
                                    'organization' => 'Independent',
                                    'bullets' => ['Web apps, hardware tinkering, and applied AI prototypes.'],
                                ],
                            ],
                        ]),
                    ]),
                    self::section('2.5rem 1.5rem', [
                        self::heading(2, 'Say hello'),
                        self::button('Contact', '/contact'),
                    ]),
                ]),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    private static function doc(string $title, string $shell, string $previewTheme, array $blocks): array
    {
        return [
            'schema' => BuilderDocument::CURRENT_SCHEMA,
            'title' => $title,
            'meta' => [
                'shell' => $shell,
                'preview_theme' => $previewTheme,
            ],
            'blocks' => $blocks,
        ];
    }

    /** @param array<int, array<string, mixed>> $children */
    private static function section(string $padding, array $children): array
    {
        return self::block('section', ['padding' => $padding], $children);
    }

    /** @param array<int, array<string, mixed>> $children */
    private static function columns(int $count, array $children): array
    {
        return self::block('columns', ['columns' => $count], $children);
    }

    private static function heading(int $level, string $text): array
    {
        return self::block('heading', ['level' => $level, 'text' => $text]);
    }

    private static function text(string $text): array
    {
        return self::block('text', ['text' => $text]);
    }

    private static function button(string $text, string $url): array
    {
        return self::block('button', ['text' => $text, 'url' => $url]);
    }

    private static function divider(): array
    {
        return self::block('divider', []);
    }

    /**
     * @param  array<string, mixed>  $props
     * @param  array<int, array<string, mixed>>|null  $children
     * @return array<string, mixed>
     */
    private static function block(string $type, array $props = [], ?array $children = null): array
    {
        $block = [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'props' => $props,
        ];

        if ($children !== null) {
            $block['children'] = $children;
        }

        return $block;
    }
}
