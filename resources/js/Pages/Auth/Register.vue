<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SocialIcon from '@/Components/SocialIcon.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <h2 class="text-lg font-semibold text-slate-100 mb-1">Register</h2>
    <p class="text-sm text-slate-400 mb-4">Create an account to request equipment bookings</p>

    <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
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
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4 flex items-center justify-between">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-slate-400 underline hover:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register
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
