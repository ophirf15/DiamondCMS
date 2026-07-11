<script setup lang="ts">
import { KeyRound, LogIn } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'

const brandLogo = '/brand/logo-primary-gold.svg'

type Boot = {
    csrf: string
    status?: string | null
    error?: string | null
    email?: string
    loginAction: string
    forgotPasswordUrl: string
    remember?: boolean
}

defineProps<{ boot: Boot }>()
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-muted/40 px-4 py-10">
        <Card class="w-full max-w-md border-border/80 shadow-sm">
            <CardHeader class="space-y-3">
                <div class="flex items-center gap-2 text-primary">
                    <img :src="brandLogo" alt="" class="size-6 shrink-0">
                    <span class="text-sm font-medium">DiamondCMS</span>
                </div>
                <div class="space-y-1">
                    <CardTitle class="text-2xl tracking-tight">Admin login</CardTitle>
                    <CardDescription>Sign in with an administrator account.</CardDescription>
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <Alert v-if="boot.status">
                    <KeyRound class="size-4" />
                    <AlertTitle>Notice</AlertTitle>
                    <AlertDescription>{{ boot.status }}</AlertDescription>
                </Alert>
                <Alert v-if="boot.error" variant="destructive">
                    <AlertTitle>Could not sign in</AlertTitle>
                    <AlertDescription>{{ boot.error }}</AlertDescription>
                </Alert>
                <form :action="boot.loginAction" method="post" class="space-y-4">
                    <input type="hidden" name="_token" :value="boot.csrf">
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            :default-value="boot.email ?? ''"
                            required
                            autofocus
                            autocomplete="username"
                            class="h-10 bg-background"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="password">Password</Label>
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="h-10 bg-background"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            id="remember"
                            type="checkbox"
                            name="remember"
                            value="1"
                            class="border-input text-primary focus-visible:ring-ring size-4 shrink-0 rounded border shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                            :checked="boot.remember"
                        >
                        <Label for="remember" class="font-normal">Remember me</Label>
                    </div>
                    <Button type="submit" class="h-10 w-full gap-2">
                        <LogIn class="size-4 shrink-0" />
                        <span>Sign in</span>
                    </Button>
                </form>
            </CardContent>
            <CardFooter>
                <a
                    :href="boot.forgotPasswordUrl"
                    class="text-muted-foreground hover:text-foreground text-sm underline-offset-4 hover:underline"
                >
                    Forgot password?
                </a>
            </CardFooter>
        </Card>
    </div>
</template>
