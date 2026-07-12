<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { toast } from 'vue-sonner'

type Account = {
    id: number
    name: string
    email: string
    two_factor_enabled: boolean
    two_factor_pending: boolean
}

type AdminRow = {
    id: number
    name: string
    email: string
    is_disabled: boolean
    two_factor_confirmed_at?: string | null
}

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const account = ref<Account | null>(null)
const admins = ref<AdminRow[]>([])
const name = ref('')
const email = ref('')
const currentPassword = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const twoFactorSecret = ref('')
const twoFactorQr = ref('')
const recoveryCodes = ref<string[]>([])
const twoFactorCode = ref('')
const newAdmin = ref({ name: '', email: '', password: '', password_confirmation: '' })
const disablePassword = ref('')

async function load(): Promise<void> {
    account.value = await props.api<Account>('/account')
    name.value = account.value.name
    email.value = account.value.email
    admins.value = await props.api<AdminRow[]>('/admins')
}

async function saveProfile(): Promise<void> {
    try {
        await props.api('/account', {
            method: 'PUT',
            body: JSON.stringify({
                name: name.value,
                email: email.value,
                current_password: password.value ? currentPassword.value : undefined,
                password: password.value || undefined,
                password_confirmation: password.value ? passwordConfirmation.value : undefined,
            }),
        })
        currentPassword.value = ''
        password.value = ''
        passwordConfirmation.value = ''
        toast.success('Account updated')
        await load()
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not update account')
    }
}

async function enable2fa(): Promise<void> {
    try {
        const result = await props.api<{ secret: string, qr_svg: string, recovery_codes: string[] }>('/two-factor/enable', {
            method: 'POST',
            body: '{}',
        })
        twoFactorSecret.value = result.secret
        twoFactorQr.value = result.qr_svg
        recoveryCodes.value = result.recovery_codes ?? []
        toast.success('Scan the QR code, then confirm with a 6-digit code')
        await load()
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not enable 2FA')
    }
}

async function confirm2fa(): Promise<void> {
    try {
        const result = await props.api<{ ok: boolean, recovery_codes: string[] }>('/two-factor/confirm', {
            method: 'POST',
            body: JSON.stringify({ code: twoFactorCode.value }),
        })
        twoFactorSecret.value = ''
        twoFactorQr.value = ''
        twoFactorCode.value = ''
        recoveryCodes.value = result.recovery_codes ?? recoveryCodes.value
        toast.success('Two-factor authentication enabled — save your recovery codes')
        await load()
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Invalid code')
    }
}

async function disable2fa(): Promise<void> {
    try {
        await props.api('/two-factor/disable', {
            method: 'POST',
            body: JSON.stringify({ current_password: disablePassword.value }),
        })
        disablePassword.value = ''
        recoveryCodes.value = []
        toast.success('Two-factor disabled')
        await load()
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not disable 2FA')
    }
}

async function createAdmin(): Promise<void> {
    try {
        await props.api('/admins', { method: 'POST', body: JSON.stringify(newAdmin.value) })
        newAdmin.value = { name: '', email: '', password: '', password_confirmation: '' }
        toast.success('Admin created')
        await load()
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not create admin')
    }
}

async function toggleDisabled(admin: AdminRow): Promise<void> {
    try {
        await props.api(`/admins/${admin.id}`, {
            method: 'PUT',
            body: JSON.stringify({ is_disabled: !admin.is_disabled }),
        })
        await load()
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not update admin')
    }
}

onMounted(load)
</script>

<template>
    <section class="space-y-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Account</h1>
            <p class="text-muted-foreground text-sm">Password, two-factor authentication, and admin users.</p>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Your profile</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label>Name</Label>
                        <Input v-model="name" />
                    </div>
                    <div class="space-y-2">
                        <Label>Email</Label>
                        <Input v-model="email" type="email" />
                    </div>
                    <div class="space-y-2">
                        <Label>Current password</Label>
                        <Input v-model="currentPassword" type="password" autocomplete="current-password" />
                    </div>
                    <div class="space-y-2">
                        <Label>New password</Label>
                        <Input v-model="password" type="password" autocomplete="new-password" />
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <Label>Confirm new password</Label>
                        <Input v-model="passwordConfirmation" type="password" autocomplete="new-password" />
                    </div>
                </div>
                <Button @click="saveProfile">Save account</Button>
            </CardContent>
        </Card>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Two-factor authentication</CardTitle>
                <CardDescription>
                    <Badge :variant="account?.two_factor_enabled ? 'default' : 'secondary'">
                        {{ account?.two_factor_enabled ? 'Enabled' : 'Off' }}
                    </Badge>
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <template v-if="!account?.two_factor_enabled">
                    <Button variant="outline" @click="enable2fa">Generate authenticator secret</Button>
                    <div v-if="twoFactorSecret" class="space-y-3 rounded-lg border p-3">
                        <div v-if="twoFactorQr" class="bg-background mx-auto max-w-[220px]" v-html="twoFactorQr" />
                        <p class="text-sm">Or enter this secret manually:</p>
                        <code class="bg-muted block break-all rounded px-2 py-1 text-sm">{{ twoFactorSecret }}</code>
                        <div class="flex flex-wrap gap-2">
                            <Input v-model="twoFactorCode" class="max-w-[160px]" placeholder="6-digit code" />
                            <Button @click="confirm2fa">Confirm</Button>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="space-y-2">
                            <Label>Current password to disable</Label>
                            <Input v-model="disablePassword" type="password" />
                        </div>
                        <Button variant="destructive" @click="disable2fa">Disable 2FA</Button>
                    </div>
                </template>
                <div v-if="recoveryCodes.length" class="space-y-2 rounded-lg border border-amber-500/40 bg-amber-500/5 p-3">
                    <p class="text-sm font-medium">Recovery codes (store offline — each works once)</p>
                    <ul class="grid gap-1 font-mono text-sm sm:grid-cols-2">
                        <li v-for="code in recoveryCodes" :key="code">{{ code }}</li>
                    </ul>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Administrators</CardTitle>
                <CardDescription>People who can sign in to DiamondCMS.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="admin in admins" :key="admin.id">
                            <TableCell>{{ admin.name }}</TableCell>
                            <TableCell>{{ admin.email }}</TableCell>
                            <TableCell>
                                <Badge :variant="admin.is_disabled ? 'secondary' : 'default'">
                                    {{ admin.is_disabled ? 'Disabled' : 'Active' }}
                                </Badge>
                                <Badge v-if="admin.two_factor_confirmed_at" class="ml-1" variant="outline">2FA</Badge>
                            </TableCell>
                            <TableCell>
                                <Button size="sm" variant="outline" @click="toggleDisabled(admin)">
                                    {{ admin.is_disabled ? 'Enable' : 'Disable' }}
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <div class="grid gap-2 rounded-lg border p-3 md:grid-cols-2">
                    <Input v-model="newAdmin.name" placeholder="Name" />
                    <Input v-model="newAdmin.email" placeholder="Email" type="email" />
                    <Input v-model="newAdmin.password" placeholder="Password (12+)" type="password" />
                    <Input v-model="newAdmin.password_confirmation" placeholder="Confirm password" type="password" />
                    <Button class="md:col-span-2" @click="createAdmin">Add admin</Button>
                </div>
            </CardContent>
        </Card>
    </section>
</template>
