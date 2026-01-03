<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SocialIcon from '@/Components/SocialIcon.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

            <h2 class="text-lg font-semibold text-slate-100 mb-1">Log in</h2>
        <p class="text-sm text-slate-400 mb-4">Sign in to manage your bookings and equipment</p>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-500">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600"
                        >Remember me</span
                    >
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end gap-3">
                <Link
                    :href="route('register')"
                    class="inline-flex items-center rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-slate-100 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Register
                </Link>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-slate-400 underline hover:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>

            <div class="mt-6">
                <div class="text-center text-sm text-slate-400 mb-3">Or continue with</div>
                <div class="auth-social-grid">
                    <a :href="route('social.redirect', 'github')" class="social-btn social-btn--github" aria-label="Sign in with GitHub">
                      <SocialIcon provider="github" class="w-4 h-4" />
                      <span class="label">GitHub</span>
                    </a>
                    <a :href="route('social.redirect', 'google')" class="social-btn social-btn--google" aria-label="Sign in with Google">
                      <SocialIcon provider="google" class="w-4 h-4" />
                      <span class="label">Google</span>
                    </a>
                    <a :href="route('social.redirect', 'facebook')" class="social-btn social-btn--facebook" aria-label="Sign in with Facebook">
                      <SocialIcon provider="facebook" class="w-4 h-4" />
                      <span class="label">Facebook</span>
                    </a>
                    <a :href="route('social.redirect', 'twitter')" class="social-btn social-btn--twitter" aria-label="Sign in with Twitter">
                      <SocialIcon provider="twitter" class="w-4 h-4" />
                      <span class="label">Twitter</span>
                    </a>
                    <a :href="route('social.redirect', 'apple')" class="social-btn social-btn--apple" aria-label="Sign in with Apple">
                      <SocialIcon provider="apple" class="w-4 h-4" />
                      <span class="label">Apple</span>
                    </a>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
