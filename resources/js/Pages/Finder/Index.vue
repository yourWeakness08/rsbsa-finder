<script setup>
    import { Head, Link, useForm } from '@inertiajs/vue3';
    import AuthenticationCard from '@/Components/AuthenticationCard.vue';
    import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
    import Checkbox from '@/Components/Checkbox.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';

    defineProps({
        canResetPassword: Boolean,
        status: String,
    });

    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = () => {
        form.transform(data => ({
            ...data,
            remember: form.remember ? 'on' : '',
        })).post(route('login'), {
            onFinish: () => form.reset('password'),
        });
    };
</script>

<template>
    <Head title="Finder" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div class="flex flex-wrap items-center justify-between gap-x-5">
            <div>
                <img src="/storage/images/DA-LOGO-1024x1024.png" alt="logo" class="h-36 w-36 mx-auto">
            </div>

            <div>
                <h4 class="font-semibold uppercase text-center">Registry System for Basic Sectors <br> in Agriculture</h4>
                <h3 class="font-bold uppercase mt-3 text-2xl text-center">RSBSA - Finder</h3>
            </div>
        </div>

        <div class="w-full  md:w-4/12 mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
        </div>
    </div>
</template>
