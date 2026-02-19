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
    import TablePagination from '@/Components/TablePagination.vue';

    import Select2 from 'vue3-select2-component';

    import { Link, router, useForm, usePage } from '@inertiajs/vue3';
    import axios from 'axios';
    import moment from 'moment';
    import Swal from 'sweetalert2';
    
    import $ from 'jquery';

    const { proxy } = getCurrentInstance()

    const props = defineProps({
        assistance: {
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
    const page = ref(1);
    const debouncedSearch = ref('');

    const pages = ref([ 10, 25, 50, 100, 200, 'All']);

    const handleSearch = proxy.$debounce((val) => {
        const { value } = pageValue;

        debouncedSearch.value = val;
        let formData = {};
        if (value) { formData.paginate = value };
        formData.search = debouncedSearch.value ? val : '';

        // deletes the search key if the search value is empty to prevent unnecessary query parameter in the url
        if (!formData.search) {
            delete formData.search
        }
        
        router.visit('/assistance', {
            method: 'get',
            data: formData,
            preserveState: true,
            only: ['assistance', 'filter']
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

        router.visit('/assistance', {
            method: 'get',
            data: formData,
            preserveState: true,
            only: ['assistance', 'filter']
        });
    }

    const dateFormat = (date) => {
        return moment(date).format('MMM. DD, YYYY hh:mm A');
    }

    const createDialog = ref(false);
    const editDialog = ref(false);

    const form = useForm({
        livelihoods: [],
        name: '',
        user_id: 0
    })

    const closeModal = () => {
        form.reset();

        createDialog.value = false;
    }

    const main_livelihood = ref([
        { id: 'farmer', text: 'Farmer' },
        { id: 'farm_worker', text: 'Farm Worker / Laborer' },
        { id: 'fisherfolks', text: 'Fisherfolk' },
        { id: 'agri_youth', text: 'Agri Youth' },
    ]);

    const isEmpty = (arr) => {
        return jQuery.isEmptyObject(arr);
    }

    const processing = ref(false);
    const recentlySuccessful = ref(false);
    const recentlyFailed = ref(false);

    const submitForm = () => {
        const { id } = props.auth.user;

        form.user_id = id;

        form.post(route('assistance.store'), {
            onProgress: () => processing.value = true,
            onSuccess: () => {
                const page = usePage();
                const response = page.props.flash?.response;

                if (response.state) {
                    recentlySuccessful.value = true;
                    processing.value = false;

                    setTimeout(() => { closeModal(); }, 800);
                    setTimeout(() => { recentlySuccessful.value = false; }, 1500);
                    form.reset();
                } else {
                    recentlyFailed.value = true;
                    processing.value = false;

                    setTimeout(() => { recentlyFailed.value = false; }, 1500);
                }
            }
        });
    }

    const editForm = useForm({
        id: '',
        livelihoods: [],
        name: '',
        user_id: 0
    })

    const closeEditModal = () => {
        editDialog.value = false;
        editForm.reset();
    }

    const setFormEditData = (data) => {
        editDialog.value = true;

        editForm.id = data.id;
        editForm.name = data.name;
        editForm.livelihoods = data.livelihood;
    }

    const submitEdit = () => {
        const { id } = props.auth.user;

        editForm.user_id = id;

        editForm.put(route('assistance.update', editForm.id), {
            onProgress: () => processing.value = true,
            onSuccess: () => {
                const page = usePage();
                const response = page.props.flash?.response;

                if (response?.state) {
                    recentlySuccessful.value = true;
                    processing.value = false;

                    setTimeout(() => { recentlySuccessful.value = false; }, 1500);
                    setTimeout(() => { closeEditModal(); }, 800);
                    editForm.reset();
                } else {
                    recentlyFailed.value = true;
                    processing.value = false;

                    setTimeout(() => { recentlyFailed.value = false; }, 1500);
                }
            }
        });
    }

    const archiveAssistance = (_id) => {
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
                axios.put(route('assistance.archive_assistance', _id), { id: id }, {
                    headers: { 'Accept' : 'application/json' }
                }).then(async (response) => {
                    const result = response.data;

                    Swal.fire({
                        icon: result.state ? 'success' : 'error',
                        text: result.message
                    });

                    if (result.state) {
                        router.reload({ only: ['assistance'] });
                    }
                });
            }
        });
    }
</script>

<template>
	<AppLayout title="Assistance">
		<template #header>
            <h2 class="font-semibold text-md text-gray-800 leading-tight">
                ASSISTANCE
            </h2>
        </template>

        <div class="py-8">
            <div class="w-full mx-auto">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
                        <div class="flex flex-row justify-between align-center">
                            <div class="md:w-1/6">
                                <PrimaryButton class="bg-blue-500 hover:bg-blue-700 text-white px-2 py-3 mr-3" @click="createDialog = true">
                                    <svg class="w-5 h-5 me-2" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" fill="#fff">
                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                        <g id="SVGRepo_iconCarrier"> 
                                            <path fill="#fff" fill-rule="evenodd" d="M9 17a1 1 0 102 0v-6h6a1 1 0 100-2h-6V3a1 1 0 10-2 0v6H3a1 1 0 000 2h6v6z"></path> 
                                        </g>
                                    </svg>
                                    New
                                </PrimaryButton>
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
                                        <th scope="col" class="px-6 py-3 w-3/12">Name</th>
                                        <th scope="col" class="px-6 py-3 w-4/12">Livelihood</th>
                                        <th scope="col" class="px-6 py-3 w-3/12">Created By</th>
                                        <th scope="col" class="px-6 py-3">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="assistance.total > 0">
                                        <tr class="bg-white border-b" v-for="assistance in assistance.data" :key="assistance.id">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ assistance.name }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ assistance.livelihoods }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                <p class="font-semibold">{{ assistance.created_name }}</p>
                                                <small>{{ dateFormat(assistance.created_at) }}</small>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900  text-center">
                                                <PrimaryButton class="bg-yellow-500 hover:bg-yellow-700 text-white mr-1" @click="setFormEditData(assistance)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
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
                                                </PrimaryButton>
                                                <PrimaryButton class="bg-red-500 hover:bg-red-700 text-white" @click="archiveAssistance(assistance.id)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
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
                                    <template v-else-if="filter.paginate == 'All' && assistance.length > 0">
                                        <tr class="bg-white border-b" v-for="assistance in assistance" :key="assistance.id">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ assistance.name }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ assistance.livelihoods }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                <p class="font-semibold">{{ assistance.created_name }}</p>
                                                <small>{{ assistance.created_at }}</small>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 text-center">
                                                <PrimaryButton class="bg-yellow-500 hover:bg-yellow-700 text-white mr-1" @click="setFormEditData(assistance)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
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
                                                </PrimaryButton>

                                                <PrimaryButton class="bg-red-500 hover:bg-red-700 text-white" @click="archiveAssistance(assistance.id)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
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
                                            <th colspan="4" id="no-data-found" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap text-center uppercase">No Data Found!</th>
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
                                        <TablePagination :arr="assistance" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <DialogModal id="newAssistance" :show="createDialog" :max-width="'md'" @close="closeModal">
            <template #title>
                New Assistance
            </template>
            <template #content>
                <div>
                    <div class="py-3 lg:py-3 bg-white">
                        <div class="mb-4">
                            <InputLabel for="type" value="Livelihood" :required="true" />
                            <div class="rounded-md block mt-1 w-full">
                                <Select2 class="uppercase" v-model="form.livelihoods" :options="main_livelihood" :settings="{ placeholder: 'Select An Option', width: '100%', dropdownParent: $('#newAssistance'), multiple: true }" />
                            </div>
                            <InputError :message="form.errors.livelihoods && isEmpty(form.livelihoods) ? 'Select atleast 1 Livelihood.' : ''" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="name" value="Name" :required="true" />
                            <TextInput v-model="form.name" type="text" class="mt-1 block w-full uppercase" placeholder="Enter Name" autocomplete="off" />
                            <InputError :message="form.name ? '': form.errors.name" class="mt-2" />
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <ActionMessage :on="recentlySuccessful" class="me-3">
                    Assistance successfully added.
                </ActionMessage>
                <PrimaryButton class="bg-blue-500 hover:bg-blue-700 text-white me-2" :class="{ 'opacity-25': processing }" 
                    :disabled="processing" @click="submitForm">Save</PrimaryButton>
                <SecondaryButton @click="closeModal">Close</SecondaryButton>
            </template>
        </DialogModal>

        <DialogModal id="editAssistance" :show="editDialog" :max-width="'md'" @close="closeEditModal">
            <template #title>
                Edit Assistance
            </template>
            <template #content>
                <div>
                    <div class="py-3 lg:py-3 bg-white">
                        <div class="mb-4">
                            <InputLabel for="type" value="Livelihood" :required="true" />
                            <div class="rounded-md block mt-1 w-full">
                                <Select2 id="edit-livelihood" class="uppercase" v-model="editForm.livelihoods" :options="main_livelihood" :settings="{ placeholder: 'Select An Option', width: '100%', dropdownParent: $('#editAssistance'), multiple: true }" />
                            </div>
                            <InputError :message="editForm.errors.livelihoods && isEmpty(editForm.livelihoods) ? 'Select atleast 1 Livelihood.' : ''" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="name" value="Name" :required="true" />
                            <TextInput v-model="editForm.name" type="text" class="mt-1 block w-full uppercase" placeholder="Enter Name" autocomplete="off" />
                            <InputError :message="editForm.name ? '': editForm.errors.name" class="mt-2" />
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <ActionMessage :on="recentlySuccessful" class="me-3">
                    Assistance successfully updated.
                </ActionMessage>
                <ActionMessage :on="recentlyFailed" class="me-3">
                    Failed to add update assistance.
                </ActionMessage>
                <PrimaryButton class="bg-blue-500 hover:bg-blue-700 text-white me-2" :class="{ 'opacity-25': processing }" 
                    :disabled="processing" @click="submitEdit">Save</PrimaryButton>
                <SecondaryButton @click="closeEditModal">Close</SecondaryButton>
            </template>
        </DialogModal>
	</AppLayout>
</template>