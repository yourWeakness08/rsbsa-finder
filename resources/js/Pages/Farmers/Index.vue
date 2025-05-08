<script setup>
    import { ref, reactive, computed, getCurrentInstance, watch } from 'vue';
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

    import { Link, router, usePage } from '@inertiajs/vue3';
    import axios from 'axios';
    import moment from 'moment';
    import Swal from 'sweetalert2';
    
    import $ from 'jquery';

    const { proxy } = getCurrentInstance()
    
    const props = defineProps({
        farmer: {
            type: Object,
            default: () => ({}),
        },
        filter: {
            type: Object,
            default: () => ({}),
        },
        auth: {
            type: Object,
            default: () => ({}),
        },
    });

    const pageValue = ref(null);
    const searchValue = ref(null);
    const debouncedSearch = ref('');

    const pages = ref([ 10, 25, 50, 100, 200, 'All']);

    const dateFormat = (date) => {
        return moment(date).format('MMM. DD, YYYY hh:mm A');
    }

    const handleSearch = proxy.$debounce((val) => {
        const { value } = pageValue;

        debouncedSearch.value = val;
        let formData = {};
        if (value) { formData.paginate = value };
        formData.search = debouncedSearch.value ? val : '';
        searchValue.value = debouncedSearch.value ? val : '';
        
        router.visit('/farmers', {
            method: 'get',
            data: formData,
            preserveState: true,
            only: ['farmer', 'filter']
        });
    }, 1000);

    watch(searchValue, (val) => {
        handleSearch(val)
    });

    const tableShow = () => {
        const { value } = pageValue;

        let formData = {};
        if (value) { formData.paginate = value };
        if (searchValue.value) { formData.search = searchValue.value; }

        router.visit('/farmers', {
            method: 'get',
            data: formData,
            preserveState: true,
            only: ['farmer', 'filter']
        });
    }

    const archiveFarmer = (_id) => {
        const { id } = props.auth.user;

        Swal.fire({
            title: 'Are you sure?',
            text: "This will be permanently deleted. You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                axios.put(route('farmers.archive_farmer', _id), { id: id }, {
                    headers: { 'Accept' : 'application/json' }
                }).then(async (response) => {
                    const result = response.data;

                    Swal.fire({
                        icon: result.state ? 'success' : 'error',
                        text: result.message
                    });

                    if (result.state) {
                        router.reload({ only: ['farmer'] });
                    }
                });
            }
        });
    }
</script>

<template>
    <AppLayout title="Farmers">
        <template #header>
            <h2 class="font-semibold text-md text-gray-800 leading-tight">
                FARMERS
            </h2>
        </template>

        <div class="py-8">
            <div class="w-full mx-auto">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
                        <div class="flex flex-row justify-between align-center">
                            <div class="md:w-1/6">
                                <Link class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 bg-blue-500 hover:bg-blue-700 text-white px-2 py-3 mr-3" :href="route('farmers.create')">
                                    <svg class="w-5 h-5 me-2" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" fill="#fff">
                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                        <g id="SVGRepo_iconCarrier"> 
                                            <path fill="#fff" fill-rule="evenodd" d="M9 17a1 1 0 102 0v-6h6a1 1 0 100-2h-6V3a1 1 0 10-2 0v6H3a1 1 0 000 2h6v6z"></path> 
                                        </g>
                                    </svg>
                                    New
                                </Link>
                            </div>
                            <div class="md:w-3/12">
                                <TextInput v-model="searchValue" type="text" class="block w-full h-10" placeholder="Search" autocomplete="off" />
                            </div>
                        </div>

                        <div class="mt-6">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead
                                    class="text-xs text-gray-700 uppercase">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 w-[11%]"></th>
                                        <th scope="col" class="px-6 py-3 w-[8%]">Control #</th>
                                        <th scope="col" class="px-6 py-3 w-[20%]">Farmer Name</th>
                                        <th scope="col" class="px-6 py-3 w-[8%]">Contact #</th>
                                        <th scope="col" class="px-6 py-3 w-[20%]">Created By</th>
                                        <th scope="col" class="px-6 py-3 w-[17%]">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="farmer.total > 0">
                                        <tr class="bg-white border-b" v-for="farmer in farmer.data" :key="farmer.id">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                                <div class="border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300">
                                                    <img :src="farmer.farmer_image" :alt="farmer.firstname" class="h-14 w-14 rounded-full object-cover ml-auto mr-auto">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ farmer.ref_no }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ farmer.name }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ farmer.mobile_no ?? farmer.tel_no }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                <p class="font-semibold">{{ farmer.created_name }}</p>
                                                <small>{{ dateFormat(farmer.created_at) }}</small>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900  text-center">
                                                <Link class="inline-flex items-center bg-yellow-500 rounded-md hover:bg-yellow-700 px-3 text-white mr-1" :href="route('farmers.view', farmer.id)" style="padding: 0.58rem 0.80rem !important">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#fff">
                                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                        <g id="SVGRepo_iconCarrier"> 
                                                            <title></title> 
                                                            <g id="Complete"> 
                                                                <g id="edit">
                                                                     <g> 
                                                                        <path d="M20,16v4a2,2,0,0,1-2,2H4a2,2,0,0,1-2-2V6A2,2,0,0,1,4,4H8" fill="none" stroke="#ffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path> 
                                                                        <polygon fill="none" points="12.5 15.8 22 6.2 17.8 2 8.3 11.5 8 16 12.5 15.8" stroke="#ffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></polygon> 
                                                                    </g> 
                                                                </g> 
                                                            </g> 
                                                        </g>
                                                    </svg>
                                                </Link>
                                                <PrimaryButton class="bg-red-500 hover:bg-red-700 px-3 text-white" @click="archiveFarmer(farmer.id)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#fff">
                                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                        <g id="SVGRepo_iconCarrier"> 
                                                            <path d="M18 6L17.1991 18.0129C17.129 19.065 17.0939 19.5911 16.8667 19.99C16.6666 20.3412 16.3648 20.6235 16.0011 20.7998C15.588 21 15.0607 21 14.0062 21H9.99377C8.93927 21 8.41202 21 7.99889 20.7998C7.63517 20.6235 7.33339 20.3412 7.13332 19.99C6.90607 19.5911 6.871 19.065 6.80086 18.0129L6 6M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6M14 10V17M10 10V17" stroke="#ffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> 
                                                        </g>
                                                    </svg>
                                                </PrimaryButton>
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else-if="filter.paginate == 'All' && farmer.length > 0">
                                        <tr class="bg-white border-b" v-for="farmer in farmer.data" :key="farmer.id">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase w-1/12">
                                                <div class="border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300">
                                                    <img :src="farmer.farmer_image" :alt="farmer.firstname" class="h-16 w-16 rounded-full object-cover ml-auto mr-auto">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ farmer.ref_no }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ farmer.name }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ farmer.mobile_no ?? farmer.tel_no }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                <p class="font-semibold">{{ farmer.created_name }}</p>
                                                <small>{{ dateFormat(farmer.created_at) }}</small>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900  text-center">
                                                <Link class="inline-flex items-center bg-yellow-500 rounded-md hover:bg-yellow-700 px-3 text-white mr-1" :href="route('farmers.view', farmer.id)" style="padding: 0.58rem 0.80rem !important">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#fff">
                                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                        <g id="SVGRepo_iconCarrier"> 
                                                            <title></title> 
                                                            <g id="Complete"> 
                                                                <g id="edit">
                                                                     <g> 
                                                                        <path d="M20,16v4a2,2,0,0,1-2,2H4a2,2,0,0,1-2-2V6A2,2,0,0,1,4,4H8" fill="none" stroke="#ffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path> 
                                                                        <polygon fill="none" points="12.5 15.8 22 6.2 17.8 2 8.3 11.5 8 16 12.5 15.8" stroke="#ffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></polygon> 
                                                                    </g> 
                                                                </g> 
                                                            </g> 
                                                        </g>
                                                    </svg>
                                                </Link>
                                                <PrimaryButton class="bg-red-500 hover:bg-red-700 text-white" @click="archiveFarmer(farmer.id)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#fff">
                                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                        <g id="SVGRepo_iconCarrier"> 
                                                            <path d="M18 6L17.1991 18.0129C17.129 19.065 17.0939 19.5911 16.8667 19.99C16.6666 20.3412 16.3648 20.6235 16.0011 20.7998C15.588 21 15.0607 21 14.0062 21H9.99377C8.93927 21 8.41202 21 7.99889 20.7998C7.63517 20.6235 7.33339 20.3412 7.13332 19.99C6.90607 19.5911 6.871 19.065 6.80086 18.0129L6 6M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6M14 10V17M10 10V17" stroke="#ffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> 
                                                        </g>
                                                    </svg>
                                                </PrimaryButton>
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else>
                                        <tr class="bg-white border-b">
                                            <th colspan="7" id="no-data-found" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap text-center uppercase">No Data Found!</th>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div class="mt-6">
                                <div class="flex flex-row justify-between items-center">
                                    <div class="md:w-[11%] lg:w-[11%] xl:w-[11%] 2xl:w-1/12">
                                        <SelectInput placeholder="Show" v-model="pageValue" :model-options="pages" class="block w-full" @change="tableShow" />
                                    </div>
                                    <div class="md:w-10/12 lg:w-10/12 xl:w-10/12 2xl:w-11/12">
                                        <ul class="flex items-center justify-end -space-x-px h-8 text-sm">
                                            <li v-for="(link, index) in farmer.links" :key="index">
                                                <template v-if="index == '0'">
                                                    <Link :href="link.url" v-html="link.label" class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700" />
                                                </template>
                                                <template v-else-if="index == farmer.links.length - 1">
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