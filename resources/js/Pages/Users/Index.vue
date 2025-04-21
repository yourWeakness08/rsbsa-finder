<script setup>
    import { ref, reactive } from 'vue';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import DialogModal from '@/Components/DialogModal.vue';

    import ActionMessage from '@/Components/ActionMessage.vue';

    import TextInput from '@/Components/TextInput.vue';
    import SelectInput from '@/Components/SelectInput.vue';
    import TextAreaInput from '@/Components/TextAreaInput.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';

    import SecondaryButton from '@/Components/SecondaryButton.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';

    import Select2 from 'vue3-select2-component';

    import { Link, router } from '@inertiajs/vue3';
    import axios from 'axios';

    import $ from 'jquery';

    const props = defineProps({
        users: {
            type: Object,
            default: () => ({}),
        },
        filter: String,
    });

    const pageValue = ref(null);
    const searchValue = ref(null);

    const pages = ref([ 25, 50, 100, 200, 'All']);

    const submitFilter = () => {
        const { value } = pageValue;
        let formData = {};
        if(value){ formData.paginate = value; }
        if(searchValue.value){ formData.search = searchValue.value; }
        
        if(Object.keys(formData).length > 0){
            router.visit('/users', {
                method: 'get',
                data: formData,
                only: ['users', 'filter']
            });
        }
    }

    const resetFilter = () => {
        let formData = {};
        router.visit('/users', {
            method: 'get',
            data: formData,
            only: ['users']
        });
    }
</script>

<template>
    <AppLayout title="Users">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Users
            </h2>
        </template>

        <div class="py-8">
            <div class="w-full mx-auto">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
                        <div class="flex flex-row">
                            <div class="basis-1/12 text-center">
                                <SelectInput placeholder="Show" v-model="pageValue" :model-options="pages" class="block w-full" />
                            </div>
                            <div class="ms-2 basis-4/12">
                                <TextInput v-model="searchValue" type="text" class="block w-full uppercase" placeholder="Search" autocomplete="off" />
                            </div>
                            <div class="ms-2 basis-auto text-right">
                                <PrimaryButton class="bg-blue-500 hover:bg-blue-700 text-white px-2 py-3 mr-3" @click="submitFilter">Apply Filter</PrimaryButton>
                                <PrimaryButton class="bg-blue-500 hover:bg-blue-700 text-white px-2 py-3" @click="resetFilter">Reset Filter</PrimaryButton>
                            </div>
                        </div>

                        <div class="mt-6">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead
                                    class="text-xs text-gray-700 uppercase">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 w-12"></th>
                                        <th scope="col" class="px-6 py-3">Name</th>
                                        <th scope="col" class="px-6 py-3">Email</th>
                                        <th scope="col" class="px-6 py-3">Role</th>
                                        <th scope="col" class="px-6 py-3">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="users.total > 0">
                                        <tr class="bg-white border-b" v-for="users in users.data" :key="users.id">
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                <div class="border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300">
                                                    <img :src="users.profile_photo_url" :alt="users.name" class="h-12 w-12 rounded-full object-cover">
                                                </div>
                                            </td>
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ users.name }}
                                            </td>
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ users.email }}
                                            </td>
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 uppercase">
                                                Administrator
                                            </td>
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 uppercase">
                                                
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else-if="filter.paginate == 'All' && users.length > 0">
                                        <tr class="bg-white border-b" v-for="users in users.data" :key="users.id">
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                <img :src="users.profile_photo_url" :alt="users.name">
                                            </td>
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ users.name }}
                                            </td>
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ users.email }}
                                            </td>
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 uppercase">
                                                Administrator
                                            </td>
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 uppercase">
                                                
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else>
                                        <tr class="bg-white border-b">
                                            <th colspan="5" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap text-center uppercase">No Data Found!</th>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div class="mt-6">
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="place-content-end">
                                        <ul class="flex items-center -space-x-px h-8 text-sm">
                                            <li v-for="(link , index) in users.links" :key="index">
                                                <template v-if="index == '0'">
                                                    <Link :href="link.url" v-html="link.label" class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700" />
                                                </template>
                                                <template v-else-if="index == users.links.length - 1">
                                                    <Link :href="link.url" v-html="link.label" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700" />
                                                </template>
                                                <template v-else>
                                                    <Link :href="link.url" v-html="link.label" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700" />
                                                </template>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
