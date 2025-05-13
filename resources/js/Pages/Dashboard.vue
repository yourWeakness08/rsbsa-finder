<script setup>
    import { Head, Link, useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import Welcome from '@/Components/Welcome.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import SearchDropdown from '@/Components/SearchDropdown.vue'
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="w-full mx-auto">
                <div class="flex flex-wrap justify-end">
                    <div class="w-3/12">
                        <SearchDropdown />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
