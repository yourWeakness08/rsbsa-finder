<script setup>
    import { ref, reactive, computed, getCurrentInstance, watch } from 'vue';
    import useValidationHelpers from '@/Composables/useValidationHelpers'
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
    import Dropzone from '@/Components/DropZone.vue';

    import Select2 from 'vue3-select2-component';

    import { Link, router, useForm, usePage } from '@inertiajs/vue3';
    import axios from 'axios';
    import moment from 'moment';
    import Swal from 'sweetalert2';
    import useVuelidate from '@vuelidate/core';
    import { required, email, minLength, requiredIf, numeric, helpers } from '@vuelidate/validators';
    
    import $ from 'jquery';

    const { proxy } = getCurrentInstance()

    const props = defineProps({
        assistances: {
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
        farmer: {
            type: Object,
            default: () => ({})
        },
        assistance: {
            type: Object,
            default: () => ({})
        }
    });

    const pageValue = ref(null);
    const searchValue = ref(null);
    const page = ref(1);
    const debouncedSearch = ref('');
    let availableAssistance = ref(null);
    let livelihood = ref(null);

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
        farmer: null,
        assistance: null,
        remarks: null,
        livelihood: null,
        attachments: [],
        user_id: 0
    })

    const formRules = computed(() => {
        return {
            farmer: { required },
            assistance: { required },
            remarks: { required },
            livelihood: {},
            attachments: { required, minLength: minLength(1) },
            user_id: {}
        }
    })

    const v$ = useVuelidate(formRules, form, {
        $autoDirty: false
    })

    const { hasError, inputBorderClass, getFieldState } = useValidationHelpers(v$, form, { autoTouch: true })

    const closeModal = () => {
        form.reset();
        v$.value.$reset();

        createDialog.value = false;
    }

    const isEmpty = (arr) => {
        return jQuery.isEmptyObject(arr);
    }

    const processing = ref(false);
    const recentlySuccessful = ref(false);
    const recentlyFailed = ref(false);

    const handleUploadSuccess = (fileData) => {
        form.attachments = fileData;
    };

    const handleFarmer = (event) => {
        const selectedValue = event;
        const mainlivelihood = selectedValue.main_livelihood;

        availableAssistance.value = props.assistance.filter(item =>
            item.livelihoods.some(livelihood =>
                mainlivelihood.livelihood.includes(livelihood)
            )
        );

        livelihood.value = (mainlivelihood.livelihood || []).map(l => l.replace(/_/g, ' ').toUpperCase()).join(', ');
        form.livelihood = livelihood.value;
    }

    const submitForm = () => {
        const { id } = props.auth.user;

        processing.value = true;
        form.user_id = id;

        v$.value.$touch();

        if (!v$.value.$invalid) {
            form.post(route('assistances.store'), {
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
                        v$.value.$reset();
                        livelihood.value = null;
                    } else {
                        recentlyFailed.value = true;
                        processing.value = false;

                        setTimeout(() => { recentlyFailed.value = false; }, 1500);
                    }
                }
            });
        } else {
            processing.value = false;
        }
    }
</script>

<template>
    <AppLayout title="Assistances">
        <template #header>
            <h2 class="font-semibold text-md text-gray-800 leading-tight">
                ASSISTANCES
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
                                        <th scope="col" class="px-6 py-3 w-4/12">Assistance</th>
                                        <th scope="col" class="px-6 py-3 w-4/12">Purpose</th>
                                        <th scope="col" class="px-6 py-3 w-3/12">Created By</th>
                                        <th scope="col" class="px-6 py-3">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="assistances.total > 0">

                                    </template>
                                    <template v-else-if="filter.paginate == 'All' && assistance.length > 0">
                                    </template>
                                    <template v-else>
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center">No assistance found.</td>
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
                                        <TablePagination :arr="assistances" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <DialogModal id="newAssistance" :show="createDialog" :max-width="'3xl'" @close="closeModal">
            <template #title>
                New Assistance
            </template>
            <template #content>
                <div>
                    <div class="py-3 lg:py-3 bg-white">
                        <div class="flex flex-row justify-between mb-5">
                            <div class="w-6/12">
                                <div class="form-group mb-5">
                                    <InputLabel for="type" value="Name" :required="true" />
                                    <div class="w-80">
                                        <Select2 class="uppercase mt-1" v-model="form.farmer" :options="props.farmer" :settings="{ placeholder: 'Select An Option',  width: '100%', dropdownParent: $('#newAssistance') }" @select="handleFarmer" />
                                    </div>
                                    <p v-if="hasError('farmer')" class="text-red-500 text-sm mb-1">
                                        <span class="text-red-500 text-sm" v-if="v$.farmer.required?.$invalid">Farmer is required!</span>
                                    </p>

                                    <template v-if="livelihood">
                                        <div class="mt-1">
                                            <b>MAIN LIVELIHOOD:</b> {{ livelihood }}
                                        </div>
                                    </template>

                                </div>

                                <div class="form-group mb-2">
                                    <InputLabel for="type" value="Type of Assistance" :required="true" />
                                    <div class="w-80">
                                        <Select2 class="uppercase mt-1" v-model="form.assistance" :options="availableAssistance" :settings="{ placeholder: 'Select An Option', width: '100%', dropdownParent: $('#newAssistance') }" />
                                    </div>
                                    <p v-if="hasError('assistance')" class="text-red-500 text-sm mb-1">
                                        <span class="text-red-500 text-sm" v-if="v$.farmer.required?.$invalid">Farmer is required!</span>
                                    </p>
                                </div>
                            </div>
                            <div class="w-6/12">
                                <InputLabel for="type" value="Purpose" :required="true" />
                                <TextAreaInput v-model="form.remarks" class="uppercase mt-1 block w-full uppercase" 
                                    @blur="v$.remarks.$touch()"
                                    :class="!v$.remarks.$dirty && form.remarks ? 'border-gray-300' : inputBorderClass('remarks')"
                                />
                                <p v-if="hasError('remarks')" class="text-red-500 text-sm mb-1">
                                    <span class="text-red-500 text-sm" v-if="v$.remarks.required?.$invalid">Purpose is required!</span>
                                </p>
                            </div>
                        </div>

                        <hr class="my-6 border-t border-gray-300 mb-4">

                        <div class="flex flex-row">
                            <div class="w-full">
                                <InputLabel for="attachments" value="Attachments" :required="true" />
                                <Dropzone @fileSelected="handleUploadSuccess" :isMultiple="true" :accepted-files="'.pdf, .jpg, .png'"  />
                                <p v-if="hasError('attachments')" class="text-red-500 text-sm mt-1">
                                    <span class="text-red-500 text-sm" v-if="v$.attachments.required?.$invalid">Attachments is required. Add atleast one attachment.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <ActionMessage :on="recentlySuccessful" class="me-3">
                    Assistance successfully added.
                </ActionMessage>
                <ActionMessage :on="recentlyFailed" class="me-3">
                    Failed to add assistance.
                </ActionMessage>
                <PrimaryButton class="bg-blue-500 hover:bg-blue-700 text-white me-2" :class="{ 'opacity-25': processing }" 
                    :disabled="processing" @click="submitForm">Save</PrimaryButton>
                <SecondaryButton @click="closeModal">Close</SecondaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>