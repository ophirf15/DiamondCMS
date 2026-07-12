<script setup lang="ts">
import { computed } from 'vue'
import SocialBrandIcon from '@/components/builder/SocialBrandIcon.vue'

export type MenuItem = { label: string, url: string }

export type SocialLinkItem = { label: string, url: string, icon?: string }

export type ChromeConfig = {
    headerStyle: string
    footerStyle: string
    buttonStyle: string
    footerShowLogo: boolean
    footerShowSiteName: boolean
    footerTagline: string
    footerShowCredit?: boolean
    footerCreditText?: string
    footerCreditUrl?: string
    footerSocials?: SocialLinkItem[]
    footerSocialStyle?: string
}

const props = withDefaults(defineProps<{
    shell?: string
    siteName: string
    logoUrl: string
    menuItems: MenuItem[]
    footerItems?: MenuItem[]
    chrome: ChromeConfig
    visitorToggle?: boolean
    publicUrl?: string
}>(), {
    shell: 'default',
    footerItems: () => [],
    visitorToggle: false,
    publicUrl: '/',
})

const footerSocials = computed(() => props.chrome.footerSocials || [])
const footerSocialStyle = computed(() => props.chrome.footerSocialStyle || 'icons')
</script>

<template>
    <div
        class="dc-site-preview"
        :class="[
            shell === 'sidebar-dark' ? 'dc-shell-sidebar-dark' : 'dc-shell-default',
            `dc-header-${chrome.headerStyle}`,
            `dc-footer-${chrome.footerStyle}`,
            `dc-btn-${chrome.buttonStyle}`,
        ]"
        :data-dc-button="chrome.buttonStyle"
    >
        <template v-if="shell === 'sidebar-dark'">
            <div class="dc-sidebar-shell">
                <aside class="dc-sidebar-rail">
                    <a class="dc-sidebar-brand" :href="publicUrl">
                        <img :src="logoUrl" alt="" class="dc-sidebar-photo">
                        <strong>{{ siteName }}</strong>
                    </a>
                    <nav class="dc-sidebar-nav" aria-label="Primary">
                        <a v-for="item in menuItems" :key="item.label + item.url" :href="item.url">{{ item.label }}</a>
                    </nav>
                    <button
                        v-if="visitorToggle"
                        type="button"
                        class="dc-theme-toggle"
                        data-dc-theme-toggle-btn
                        aria-label="Toggle light and dark mode"
                    >
                        Theme
                    </button>
                </aside>
                <div class="dc-sidebar-main">
                    <main class="dc-live-canvas">
                        <slot />
                    </main>
                    <footer class="dc-footer" :class="`dc-footer--${chrome.footerStyle}`">
                        <div v-if="chrome.footerStyle !== 'minimal'" class="dc-footer-brand">
                            <img v-if="chrome.footerShowLogo" :src="logoUrl" alt="" class="dc-footer-logo">
                            <strong v-if="chrome.footerShowSiteName">{{ siteName }}</strong>
                            <p v-if="chrome.footerTagline" class="dc-footer-tagline">{{ chrome.footerTagline }}</p>
                        </div>
                        <nav aria-label="Footer">
                            <a v-for="item in footerItems" :key="item.label + item.url" :href="item.url">{{ item.label }}</a>
                        </nav>
                        <div
                            v-if="footerSocials.length"
                            class="dc-social-links dc-footer-socials"
                            :class="`dc-social-links--${footerSocialStyle}`"
                        >
                            <a
                                v-for="(item, index) in footerSocials"
                                :key="index"
                                class="dc-social-link"
                                :class="{
                                    'dc-social-link--icon': footerSocialStyle === 'icons',
                                    'dc-social-link--pill': footerSocialStyle === 'pills',
                                    'dc-social-link--list': footerSocialStyle === 'list',
                                    'dc-social-link--labeled': footerSocialStyle === 'icons-labels',
                                }"
                                :href="item.url || '#'"
                                :title="item.label"
                            >
                                <template v-if="footerSocialStyle === 'list'">
                                    <span class="dc-social-dot" aria-hidden="true" />
                                    <span>{{ item.label }}</span>
                                </template>
                                <template v-else>
                                    <SocialBrandIcon :slug="item.icon" :label="item.label" :url="item.url" :size="16" />
                                    <span v-if="footerSocialStyle !== 'icons'">{{ item.label }}</span>
                                    <span v-else class="sr-only">{{ item.label }}</span>
                                </template>
                            </a>
                        </div>
                        <a
                            v-if="chrome.footerShowCredit !== false && chrome.footerCreditUrl"
                            class="dc-footer-credit"
                            :href="chrome.footerCreditUrl"
                            rel="noopener"
                        >{{ chrome.footerCreditText || 'Powered by DiamondCMS' }}</a>
                        <span
                            v-else-if="chrome.footerShowCredit !== false"
                            class="dc-footer-credit"
                        >{{ chrome.footerCreditText || 'Powered by DiamondCMS' }}</span>
                    </footer>
                </div>
            </div>
        </template>

        <template v-else>
            <header class="dc-header" :class="`dc-header--${chrome.headerStyle}`">
                <template v-if="chrome.headerStyle === 'centered'">
                    <a class="dc-logo dc-logo--center" :href="publicUrl">
                        <img :src="logoUrl" alt="" class="h-7 w-auto">
                        <span>{{ siteName }}</span>
                    </a>
                    <nav class="dc-header-nav dc-header-nav--center" aria-label="Primary">
                        <a v-for="item in menuItems" :key="item.label + item.url" :href="item.url">{{ item.label }}</a>
                    </nav>
                </template>
                <template v-else-if="chrome.headerStyle === 'pill'">
                    <a class="dc-logo" :href="publicUrl">
                        <img :src="logoUrl" alt="" class="h-7 w-auto">
                        <span>{{ siteName }}</span>
                    </a>
                    <nav class="dc-header-nav dc-header-nav--pill" aria-label="Primary">
                        <a v-for="item in menuItems" :key="item.label + item.url" :href="item.url">{{ item.label }}</a>
                    </nav>
                </template>
                <template v-else-if="chrome.headerStyle === 'split'">
                    <nav class="dc-header-nav" aria-label="Primary">
                        <a v-for="item in menuItems" :key="item.label + item.url" :href="item.url">{{ item.label }}</a>
                    </nav>
                    <a class="dc-logo" :href="publicUrl">
                        <img :src="logoUrl" alt="" class="h-7 w-auto">
                        <span>{{ siteName }}</span>
                    </a>
                    <div class="dc-header-actions">
                        <button
                            v-if="visitorToggle"
                            type="button"
                            class="dc-theme-toggle"
                            data-dc-theme-toggle-btn
                            aria-label="Toggle light and dark mode"
                        >
                            Theme
                        </button>
                        <a class="dc-button" href="/contact">Contact</a>
                    </div>
                </template>
                <template v-else>
                    <a class="dc-logo inline-flex items-center gap-2" :href="publicUrl">
                        <img :src="logoUrl" alt="" class="h-7 w-auto">
                        <span>{{ siteName }}</span>
                    </a>
                    <div class="dc-header-right">
                        <nav
                            class="dc-header-nav"
                            :class="chrome.headerStyle === 'minimal' ? 'dc-header-nav--minimal' : ''"
                            aria-label="Primary"
                        >
                            <a v-for="item in menuItems" :key="item.label + item.url" :href="item.url">{{ item.label }}</a>
                        </nav>
                        <button
                            v-if="visitorToggle"
                            type="button"
                            class="dc-theme-toggle"
                            data-dc-theme-toggle-btn
                            aria-label="Toggle light and dark mode"
                        >
                            Theme
                        </button>
                    </div>
                </template>
                <button
                    v-if="visitorToggle && (chrome.headerStyle === 'centered' || chrome.headerStyle === 'pill')"
                    type="button"
                    class="dc-theme-toggle dc-theme-toggle--corner"
                    data-dc-theme-toggle-btn
                    aria-label="Toggle light and dark mode"
                >
                    Theme
                </button>
            </header>
            <main class="dc-live-canvas">
                <slot />
            </main>
            <footer class="dc-footer" :class="`dc-footer--${chrome.footerStyle}`">
                <div v-if="chrome.footerStyle !== 'minimal'" class="dc-footer-brand">
                    <img v-if="chrome.footerShowLogo" :src="logoUrl" alt="" class="dc-footer-logo">
                    <strong v-if="chrome.footerShowSiteName">{{ siteName }}</strong>
                    <p v-if="chrome.footerTagline" class="dc-footer-tagline">{{ chrome.footerTagline }}</p>
                </div>
                <nav aria-label="Footer">
                    <a v-for="item in footerItems" :key="item.label + item.url" :href="item.url">{{ item.label }}</a>
                </nav>
                <div
                    v-if="footerSocials.length"
                    class="dc-social-links dc-footer-socials"
                    :class="`dc-social-links--${footerSocialStyle}`"
                >
                    <a
                        v-for="(item, index) in footerSocials"
                        :key="index"
                        class="dc-social-link"
                        :class="{
                            'dc-social-link--icon': footerSocialStyle === 'icons',
                            'dc-social-link--pill': footerSocialStyle === 'pills',
                            'dc-social-link--list': footerSocialStyle === 'list',
                            'dc-social-link--labeled': footerSocialStyle === 'icons-labels',
                        }"
                        :href="item.url || '#'"
                        :title="item.label"
                    >
                        <template v-if="footerSocialStyle === 'list'">
                            <span class="dc-social-dot" aria-hidden="true" />
                            <span>{{ item.label }}</span>
                        </template>
                        <template v-else>
                            <SocialBrandIcon :slug="item.icon" :label="item.label" :url="item.url" :size="16" />
                            <span v-if="footerSocialStyle !== 'icons'">{{ item.label }}</span>
                            <span v-else class="sr-only">{{ item.label }}</span>
                        </template>
                    </a>
                </div>
                <a
                    v-if="chrome.footerShowCredit !== false && chrome.footerCreditUrl"
                    class="dc-footer-credit"
                    :href="chrome.footerCreditUrl"
                    rel="noopener"
                >{{ chrome.footerCreditText || 'Powered by DiamondCMS' }}</a>
                <span
                    v-else-if="chrome.footerShowCredit !== false"
                    class="dc-footer-credit"
                >{{ chrome.footerCreditText || 'Powered by DiamondCMS' }}</span>
            </footer>
        </template>
    </div>
</template>
