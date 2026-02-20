<script setup>
    import { Link, router, useForm, usePage } from '@inertiajs/vue3';

    defineProps({
        arr: {
            type: Object,
            default: () => ({}),
        },
    });
</script>

<template>
    <ul class="flex items-center justify-end -space-x-px h-8 text-sm" v-if="arr.data.length > 0">
        <li v-for="(link, index) in arr.links" :key="index">
            <template v-if="index == '0'">
                <Link :href="link.url || '#'" :key="link.label" v-html="link.label" class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700" :class="{ 'text-gray-500 pointer-events-none': !link.url }" />
            </template>
            <template v-else-if="index == arr.links.length - 1">
                <Link :href="link.url || '#'" :key="link.label" v-html="link.label" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700" :class="{ 'text-gray-500 pointer-events-none': !link.url }" />
            </template>
            <template v-else>
                <Link :href="link.url || '#'" :key="link.label" v-html="link.label" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700" :class="{ 'text-gray-500 pointer-events-none': !link.url }" />
            </template>
        </li>
    </ul>
</template>