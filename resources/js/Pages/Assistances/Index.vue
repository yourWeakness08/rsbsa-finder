<script setup>
    import { ref, reactive, computed, getCurrentInstance, watch, onMounted } from 'vue';
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

    const user = ref([]);
    const _page = usePage()
    user.value = _page.props.auth.user;

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
        const status = props.filter.status ? props.filter.status : 'All';

        debouncedSearch.value = val;
        let formData = {};
        if (value) { formData.paginate = value };
        formData.search = debouncedSearch.value ? val : '';
        formData.status = status;

        // deletes the search key if the search value is empty to prevent unnecessary query parameter in the url
        if (!formData.search) {
            delete formData.search
        }
        
        router.visit('/assistances', {
            method: 'get',
            data: formData,
            preserveState: true,
            only: ['assistances', 'filter']
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
        formData.status = props.filter.status ? props.filter.status : 'All';

        router.visit('/assistances', {
            method: 'get',
            data: formData,
            preserveState: true,
            only: ['assistances', 'filter']
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
            livelihood: { required },
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
        livelihood.value = null;
        availableAssistance.value = null;

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

    const handleFarmer = (event, type = 'add') => {
        livelihood.value = [];
        availableAssistance.value = [];

        if (type == 'add') {
            form.livelihood = '';
            form.assistance = '';
        } else {
            editForm.livelihood = '';
            editForm.assistance = '';
        }

        const selectedValue = event;
        const mainlivelihood = selectedValue.main_livelihood;

        const filteredLivelihoods = main_livelihood.value.filter(item =>
            mainlivelihood.livelihood.includes(item.id)
        );

        livelihood.value = filteredLivelihoods;
        // availableAssistance.value = props.assistance.filter(item =>
        //     item.livelihoods.some(livelihood =>
        //         mainlivelihood.livelihood.includes(livelihood)
        //     )
        // );

        // livelihood.value = (mainlivelihood.livelihood || []).map(l => l.replace(/_/g, ' ').toUpperCase()).join(', ');
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
                        availableAssistance.value = null;
                    } else { 
                        recentlyFailed.value = true;
                        processing.value = false;

                        if (response.livelihood != 'farmer' && response.applied_assistance.includes('cash') && response.message) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Create Assistance',
                                text: response.message,
                                target: document.getElementById('newAssistance')
                            })
                        }

                        if (typeof response.is_farm_worker != 'undefined' && response.is_farm_worker == 1) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Create Assistance',
                                text: response.message,
                                target: document.getElementById('newAssistance')
                            })
                        }

                        if (typeof response.is_fisherfolks != 'undefined' && response.is_fisherfolks == 1) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Create Assistance',
                                text: response.message,
                                target: document.getElementById('newAssistance')
                            })
                        }

                        setTimeout(() => { recentlyFailed.value = false; }, 1500);
                    }
                }
            });
        } else {
            processing.value = false;
        }
    }

    const main_livelihood = ref([
        { id: 'farmer', text: 'Farmer' },
        { id: 'farm_worker', text: 'Farm Worker / Laborer' },
        { id: 'fisherfolks', text: 'Fisherfolk' },
        // { id: 'agri_youth', text: 'Agri Youth' },
    ]);

    const handleLivelihood = (event, type = 'add') => {
        availableAssistance.value = [];

        if (type == 'add') {
            form.assistance = '';
        } else {
            editForm.assistance = '';
        }

        const selectedValue = event;

        const filteredAssistance = props.assistance.filter(item =>
            item.livelihoods.includes(selectedValue.id)
        );

        availableAssistance.value = filteredAssistance;
    }

    const viewDialog = ref(false);
    const _viewAssistance = ref([]);
    let status = null;

    const viewAssistance = (val) => {
        _viewAssistance.value = val;
        viewDialog.value = true;
        status = computed(() => (_viewAssistance.value.status || '').toLowerCase())
    }

    const closeViewModal = () => {
        viewDialog.value = false;
        processing.value = false;
        form.reset();

        setTimeout(() => {
            _viewAssistance.value = [];
        }, 500);
    }

    const viewAttachment = (path) => {
        if (path) {
            window.open(path);
        } else {
            Swal.fire({
                target: document.getElementById('viewAssistance'),
                icon: 'warning',
                title: 'View Attacment',
                text: 'Attachment not found!'
            })
        }
    }

    const actions = computed(() => ({
        approve: {
            show: status.value === 'pending' || status.value === 'approved',
            nextStatus: status.value === 'pending' ? 'approved' : 'pending',
            label: status.value === 'pending' ? 'Approve' : 'Undo Approved',
            classes: 'bg-blue-500 hover:bg-blue-700',
        },
        disapprove: {
            show: status.value === 'pending' || status.value === 'disapproved',
            nextStatus: status.value === 'pending' ? 'disapproved' : 'pending',
            label: status.value === 'pending' ? 'Disapprove' : 'Undo Disapproved',
            classes: 'bg-red-500 hover:bg-red-700',
        },
        cancel: {
            show: status.value === 'pending' || status.value === 'cancelled',
            nextStatus: status.value === 'pending' ? 'cancelled' : 'pending',
            label: status.value === 'pending' ? 'Cancel' : 'Undo Cancel',
            classes: 'bg-zinc-500 hover:bg-zinc-700',
        },
    }))

    const updateStatus = (type) => {
        if (processing.value) return

        processing.value = true
        const action = actions.value[type]
        const nextStatus = action.nextStatus;

        Swal.fire({
            target: document.getElementById('viewAssistance'),
            icon: 'question',
            title: action.label + ' Assistance',
            html: `Are you sure you want to <b>${action.label}</b>?`,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            allowOutsideClick: false,
            customClass: {
                popup: 'swal2-above-modal'
            },
            input: 'textarea',
            inputLabel: 'Remarks',
            preConfirm: (remarks) => {
                if (!remarks || remarks.trim() === '') {
                    Swal.showValidationMessage('Remarks is required.')
                    return false
                }
                return remarks
            },
        }).then((result) => {
            if (result.isConfirmed && typeof result.value != 'undefined' && result.value != '') {
                router.put(`/assistances/update_status/${_viewAssistance.value.id}`, { 
                    status: action.nextStatus,
                    remarks: result.value
                }, {
                    preserveScroll: true,
                    onSuccess: (page) => {
                        const response = page.props.flash?.response;

                        if (response.state == 1) {
                            _viewAssistance.value.status = action.nextStatus
    
                            let newList = page?.props?.assistances;
                            newList = newList.data ? newList.data : newList;
    
                            Swal.fire({
                                target: document.getElementById('viewAssistance'),
                                icon: 'success',
                                title: 'Updated!',
                                text: 'Status has been updated.',
                                timer: 2500,
                                showConfirmButton: true
                            })
    
                            const updated = newList.find(item =>
                                item.id == _viewAssistance.value.id
                            )
    
                            _viewAssistance.value = {... updated}
                        } else {
                            if (typeof response.livelihood != 'undefined' && response.status == 'approved') {
                                Swal.fire({
                                    target: document.getElementById('viewAssistance'),
                                    icon: 'warning',
                                    title: 'Approve Assistance',
                                    text: response.message
                                })
                            }
                        }
                    },
                    onError: () => {
                        Swal.fire({
                            target: document.getElementById('viewAssistance'),
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong.'
                        })
                    },
                    onFinish: () => {
                        processing.value = false
                    }
                })
            } else {
                processing.value = false
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
                router.put(route('assistances.archive_assistance', _id), {id: id}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        const page = usePage()
                        const response = page.props.flash?.response

                        if (response?.state) {
                            Swal.fire({
                                icon: 'success',
                                text: 'Assistance succesfully deleted!'
                            })
                            } else {
                            Swal.fire({
                                icon: 'error',
                                text: 'Failed to delete assistance!'
                            })
                        }
                    },
                    onError: () => {
                        Swal.fire({
                        icon: 'error',
                        text: 'Failed to delete assistance!'
                        })
                    }
                })
            }
        });
    }

    const closeEditModal = () => {
        editDialog.value = false;
        processing.value = false;
    }

    const editForm = useForm({
        id: 0,
        farmer: null,
        assistance: null,
        remarks: null,
        livelihood: null,
        attachments: [],
        user_id: 0
    })

    const editFormRules = computed(() => {
        return {
            id: 0,
            farmer: { required },
            assistance: { required },
            remarks: { required },
            livelihood: { required },
            attachments: { required, minLength: minLength(1) },
            user_id: {}
        }
    })

    const y$ = useVuelidate(editFormRules, editForm, {
        $autoDirty: false
    })

    const { hasError: editHasError, inputBorderClass: editInputBorderClass, getFieldState: editGetFieldState } = useValidationHelpers(y$, editForm, { autoTouch: true })

    const setFormEditData = (val) => {
        editDialog.value = true;

        const filterFarmer = props.farmer.filter(item => 
            item.id == val.farmer_id
        )

        let mainlivelihood = filterFarmer[0].main_livelihood;
        const filteredLivelihoods = main_livelihood.value.filter(item =>
            mainlivelihood.livelihood.includes(item.id)
        );

        livelihood.value = filteredLivelihoods;

        const filteredAssistance = props.assistance.filter(item =>
            item.livelihoods.includes(val.livelihood)
        );

        availableAssistance.value = filteredAssistance;

        editForm.id = val.id;
        editForm.farmer = val.farmer_id;
        editForm.assistance = val.assistance_id;
        editForm.remarks = val.purpose;
        editForm.livelihood = val.livelihood;
        

        let _uploaded = [];
        $.each(val.attachments, function (index, item) {
            if (item.url) {
                _uploaded.push(item.url);
            }
        });
        editForm.attachments = _uploaded;
    }

    const handleEditUploadSuccess = (fileData) => {
        editForm.attachments = fileData;
    }

    const submitEditForm = () => {
        const { id } = props.auth.user;

        processing.value = true;
        editForm.user_id = id;

        y$.value.$touch();
        if (!y$.value.$invalid) {
            editForm.transform((data) => ({
                ...data,
                _method: 'put',
            })).post(route('assistances.update', editForm.id), {
                onProgress: () => processing.value = true,
                onSuccess: () => {
                    const page = usePage();
                    const response = page.props.flash?.response;

                    if (response.state) {
                        recentlySuccessful.value = true;
                        processing.value = false;

                        setTimeout(() => { 
                            closeEditModal();
                            editForm.reset();
                            y$.value.$reset();
                            livelihood.value = null;
                            availableAssistance.value = null;
                        }, 800);
                        setTimeout(() => { recentlySuccessful.value = false; }, 1500);
                        processing.value = false;
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

    const formatLivelihood = (livelihood) => {
        if (!livelihood) return '---';
        const found = main_livelihood.value.find(item => item.id == livelihood);
        return found ? found.text : livelihood;
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
                                        <th scope="col" class="px-6 py-3 w-1/12">Ref No.</th>
                                        <th scope="col" class="px-6 py-3 w-1/12">Status</th>
                                        <th scope="col" class="px-6 py-3 w-2/12">Name</th>
                                        <th scope="col" class="px-6 py-3 w-2/12">Assistance</th>
                                        <th scope="col" class="px-6 py-3 w-3/12">Purpose</th>
                                        <th scope="col" class="px-6 py-3 w-2/12">Created By</th>
                                        <th scope="col" class="px-6 py-3 w-3/12">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="assistances.total > 0">
                                        <tr class="bg-white border-b" v-for="(item, index) in assistances.data" :key="item.id">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ item.reference_no }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium inset-ring inset-ring-yellow-400/20"
                                                    :class="{
                                                        'bg-yellow-500 text-white' : item.status.toLowerCase() == 'pending',
                                                        'bg-green-500 text-white' : item.status.toLowerCase() == 'approved',
                                                        'bg-red-500 text-white' : item.status.toLowerCase() == 'disapproved',
                                                        'bg-zinc-500 text-white' : item.status.toLowerCase() == 'cancelled',
                                                    }">
                                                    {{ item.status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 uppercase">
                                                {{ item.name }}
                                                <p class="m-0">
                                                    <small>Applied Livelihood: <b>{{ item.livelihood ? formatLivelihood(item.livelihood) : ' --- ' }}</b> </small>
                                                </p>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ item.assistance_name }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 uppercase">
                                                {{ item.purpose }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                <p class="font-semibold">{{ item.created_name }}</p>
                                                <small>{{ dateFormat(item.created_at) }}</small>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                <PrimaryButton class="bg-yellow-500 hover:bg-yellow-700 text-white mr-1" @click="setFormEditData(item)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;" v-if="item.status.toLowerCase() == 'pending'">
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
                                                <PrimaryButton class="bg-gray-800 hover:bg-gray-700 text-white mr-1" @click="viewAssistance(item)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                        <g id="SVGRepo_iconCarrier"> 
                                                            <path d="M9 4.45962C9.91153 4.16968 10.9104 4 12 4C16.1819 4 19.028 6.49956 20.7251 8.70433C21.575 9.80853 22 10.3606 22 12C22 13.6394 21.575 14.1915 20.7251 15.2957C19.028 17.5004 16.1819 20 12 20C7.81811 20 4.97196 17.5004 3.27489 15.2957C2.42496 14.1915 2 13.6394 2 12C2 10.3606 2.42496 9.80853 3.27489 8.70433C3.75612 8.07914 4.32973 7.43025 5 6.82137" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"></path>
                                                            <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="#ffffff" stroke-width="1.5"></path> 
                                                        </g>
                                                    </svg>
                                                </PrimaryButton>
                                                <template v-if="user.role == 1">
                                                    <PrimaryButton class="bg-red-500 hover:bg-red-700 text-white" v-if="item.status.toLowerCase() != 'approved'"  @click="archiveAssistance(item.id)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
                                                        <svg class="w-4 h-4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#fff">
                                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                            <g id="SVGRepo_iconCarrier"> 
                                                                <path d="M18 6L17.1991 18.0129C17.129 19.065 17.0939 19.5911 16.8667 19.99C16.6666 20.3412 16.3648 20.6235 16.0011 20.7998C15.588 21 15.0607 21 14.0062 21H9.99377C8.93927 21 8.41202 21 7.99889 20.7998C7.63517 20.6235 7.33339 20.3412 7.13332 19.99C6.90607 19.5911 6.871 19.065 6.80086 18.0129L6 6M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6M14 10V17M10 10V17" stroke="#ffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> 
                                                            </g>
                                                        </svg>
                                                    </PrimaryButton>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else-if="filter.paginate == 'All' && assistances.length > 0">
                                        <tr class="bg-white border-b" v-for="(item, index) in assistances" :key="item.id">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ item.reference_no }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium inset-ring inset-ring-yellow-400/20"
                                                    :class="{
                                                        'bg-yellow-500 text-white' : item.status.toLowerCase() == 'pending',
                                                        'bg-green-500 text-white' : item.status.toLowerCase() == 'approved',
                                                        'bg-red-500 text-white' : item.status.toLowerCase() == 'disapproved',
                                                        'bg-zinc-500 text-white' : item.status.toLowerCase() == 'cancelled',
                                                    }">
                                                    {{ item.status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 uppercase">
                                                {{ item.name }}
                                                <p class="m-0">
                                                    <small>Applied Livelihood: <b>{{ item.livelihood ? formatLivelihood(item.livelihood) : ' --- ' }}</b> </small>
                                                </p>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ item.assistance_name }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 uppercase">
                                                {{ item.purpose }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                <p class="font-semibold">{{ item.created_name }}</p>
                                                <small>{{ dateFormat(item.created_at) }}</small>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                <PrimaryButton class="bg-yellow-500 hover:bg-yellow-700 text-white mr-1" @click="setFormEditData(item)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;" v-if="item.status.toLowerCase() == 'pending'">
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
                                                <PrimaryButton class="bg-gray-800 hover:bg-gray-700 text-white mr-1" @click="viewAssistance(item)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                        <g id="SVGRepo_iconCarrier"> 
                                                            <path d="M9 4.45962C9.91153 4.16968 10.9104 4 12 4C16.1819 4 19.028 6.49956 20.7251 8.70433C21.575 9.80853 22 10.3606 22 12C22 13.6394 21.575 14.1915 20.7251 15.2957C19.028 17.5004 16.1819 20 12 20C7.81811 20 4.97196 17.5004 3.27489 15.2957C2.42496 14.1915 2 13.6394 2 12C2 10.3606 2.42496 9.80853 3.27489 8.70433C3.75612 8.07914 4.32973 7.43025 5 6.82137" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"></path>
                                                            <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="#ffffff" stroke-width="1.5"></path> 
                                                        </g>
                                                    </svg>
                                                </PrimaryButton>
                                                <template v-if="user.role == 1">
                                                    <PrimaryButton class="bg-red-500 hover:bg-red-700 text-white" v-if="item.status.toLowerCase() != 'approved'"  @click="archiveAssistance(item.id)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
                                                        <svg class="w-4 h-4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#fff">
                                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                            <g id="SVGRepo_iconCarrier"> 
                                                                <path d="M18 6L17.1991 18.0129C17.129 19.065 17.0939 19.5911 16.8667 19.99C16.6666 20.3412 16.3648 20.6235 16.0011 20.7998C15.588 21 15.0607 21 14.0062 21H9.99377C8.93927 21 8.41202 21 7.99889 20.7998C7.63517 20.6235 7.33339 20.3412 7.13332 19.99C6.90607 19.5911 6.871 19.065 6.80086 18.0129L6 6M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6M14 10V17M10 10V17" stroke="#ffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> 
                                                            </g>
                                                        </svg>
                                                    </PrimaryButton>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else>
                                        <tr>
                                            <td colspan="7" id="no-data-found" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap text-center uppercase">No data found!</td>
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
                                        <Select2 class="uppercase mt-1" v-model="form.farmer" :options="props.farmer" :settings="{ placeholder: 'Select An Option',  width: '100%', dropdownParent: $('#newAssistance') }" @select="handleFarmer($event, 'add')" />
                                    </div>
                                    <p v-if="hasError('farmer')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.farmer.required?.$invalid">Farmer is required!</span>
                                    </p>
                                </div>

                                <div class="form-group mb-5">
                                    <InputLabel for="type" value="Livelihood" :required="true" />
                                    <div class="w-80">
                                        <Select2 class="uppercase mt-1" v-model="form.livelihood" :options="livelihood" :settings="{ placeholder: 'Select An Option',  width: '100%', dropdownParent: $('#newAssistance') }" @select="handleLivelihood($event, 'add')" :disabled="livelihood ? false : 'disabled'" />
                                    </div>

                                    <p v-if="hasError('livelihood')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.livelihood.required?.$invalid">Livelihood is required!</span>
                                    </p>
                                </div>

                                <div class="form-group mb-2">
                                    <InputLabel for="type" value="Type of Assistance" :required="true" />
                                    <div class="w-80">
                                        <Select2 class="uppercase mt-1" v-model="form.assistance" :options="availableAssistance" :settings="{ placeholder: 'Select An Option', width: '100%', dropdownParent: $('#newAssistance') }" :disabled="availableAssistance ? false : 'disabled'" />
                                    </div>
                                    <p v-if="hasError('assistance')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.assistance.required?.$invalid">Assistance is required!</span>
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

        <DialogModal id="viewAssistance" :show="viewDialog" :max-width="'xl'" @close="closeViewModal">
            <template #title>
                View Assistance
            </template>
            <template #content>
                <div class="uppercase">
                    <div class="flex flex-row flex-wrap mb-3 justify-between items-center">
                        <div class="w-6/12">
                            <p class="m-0">
                                Reference No: 
                                <b>{{ _viewAssistance.reference_no }}</b>
                            </p>
                        </div>
                        <div class="w-6/12">
                            <label class="text-sm text-gray-500 me-3">Status</label>
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-md font-medium uppercase inset-ring inset-ring-yellow-400/20"
                                :class="{
                                    'bg-yellow-500 text-white' : _viewAssistance.status.toLowerCase() == 'pending',
                                    'bg-green-500 text-white' : _viewAssistance.status.toLowerCase() == 'approved',
                                    'bg-red-500 text-white' : _viewAssistance.status.toLowerCase() == 'disapproved',
                                    'bg-zinc-500 text-white' : _viewAssistance.status.toLowerCase() == 'cancelled',
                                }">
                                {{ _viewAssistance.status }}
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-row justify-between mb-5">
                        <div class="w-6/12">
                            <p class="text-sm text-gray-500 m-0">Name</p>
                            <h2 class="font-semibold text-lg text-gray-800 uppercase">{{ _viewAssistance.name }}</h2>
                            <p class="m-0">
                                <small>Applied Livelihood: <b>{{ _viewAssistance.livelihood ? formatLivelihood(_viewAssistance.livelihood) : ' --- ' }}</b> </small>
                            </p>
                        </div>
                        <div class="w-6/12">
                            <p class="text-sm text-gray-500 m-0">Type of Assistance</p>
                            <h2 class="font-semibold text-lg text-gray-800 uppercase">{{ _viewAssistance.assistance_name }}</h2>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-start mb-3">
                        <div class="w-12/12">
                            <p class="text-sm text-gray-500 m-0">Purpose</p>
                            <h2 class="font-semibold text-lg text-gray-800 uppercase">{{ _viewAssistance.purpose }}</h2>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-start mb-3" v-if="_viewAssistance.remarks && _viewAssistance.remarks != null">
                        <div class="w-2/2">
                            <p class="text-sm text-gray-500 m-0">Remarks</p>
                            <h2 class="font-semibold text-lg text-gray-800 uppercase pl-2">- {{ _viewAssistance.remarks ?? _viewAssistance.approved_remarks }}</h2>
                        </div>
                    </div>

                    <hr class="my-6 border-t border-gray-300" />

                    <div class="flex flex-wrap flex-row items-start justify-between">
                        <div class="w-5/12 mb-3">
                            <p class="text-sm text-gray-500 m-0">Created By</p>
                            <h3 class="font-semibold text-lg text-gray-800 uppercase">{{ _viewAssistance.created_name }}</h3>
                            <h4 class="font-semibold text-gray-800">{{ dateFormat(_viewAssistance.created_at) }}</h4>
                        </div>
                        <div class="w-5/12 mb-3" v-if="_viewAssistance.updated_name && _viewAssistance.updated_at">
                            <p class="text-sm text-gray-500 m-0">Updated By</p>
                            <h3 class="font-semibold text-lg text-gray-800 uppercase">{{ _viewAssistance.updated_name }}</h3>
                            <h4 class="font-semibold text-gray-800">{{ dateFormat(_viewAssistance.updated_at) }}</h4>
                        </div>
                        <div class="w-5/12 mb-3" v-if="_viewAssistance.approved_name && _viewAssistance.approved_at">
                            <p class="text-sm text-gray-500 m-0">Approved By</p>
                            <h3 class="font-semibold text-lg text-gray-800 uppercase">{{ _viewAssistance.approved_name ? _viewAssistance.approved_name : 'Not Approved Yet' }}</h3>
                            <h4 class="font-semibold text-gray-800">{{ dateFormat(_viewAssistance.approved_at) }}</h4>
                            <p class="text-sm text-gray-500 m-0">
                                <strong>Remarks: </strong>
                                <small>{{ _viewAssistance.approved_remarks }}</small>
                            </p>
                        </div>
                        <div class="w-5/12 mb-3" v-if="_viewAssistance.disapproved_name && _viewAssistance.disapproved_at">
                            <p class="text-sm text-gray-500 m-0">Disapproved By</p>
                            <h3 class="font-semibold text-lg text-gray-800 uppercase">{{ _viewAssistance.disapproved_name ? _viewAssistance.disapproved_name : 'Not Disapproved Yet' }}</h3>
                            <h4 class="font-semibold text-gray-800">{{ dateFormat(_viewAssistance.disapproved_at) }}</h4>
                            <p class="text-sm text-gray-500 m-0">
                                <strong>Remarks: </strong>
                                <small>{{ _viewAssistance.disapproved_remarks }}</small>
                            </p>
                        </div>
                        <div class="w-5/12 mb-3" v-if="_viewAssistance.cancelled_name && _viewAssistance.cancelled_at">
                            <p class="text-sm text-gray-500 m-0">Cancelled By</p>
                            <h3 class="font-semibold text-lg text-gray-800 uppercase">{{ _viewAssistance.cancelled_name ? _viewAssistance.cancelled_name : 'Not Cancelled Yet' }}</h3>
                            <h4 class="font-semibold text-gray-800">{{ dateFormat(_viewAssistance.cancelled_at) }}</h4>
                            <p class="text-sm text-gray-500 m-0">
                                <strong>Remarks: </strong>
                                <small>{{ _viewAssistance.cancelled_remarks }}</small>
                            </p>
                        </div>
                    </div>

                    <hr class="my-6 border-t border-gray-300" />

                    <div class="flex flex-wrap flex-row">
                        <div class="w-full">
                            <p class="text-sm text-gray-500 m-0">Attachments</p>
                            <table  class="w-full text-sm text-left text-gray-500 mt-3">
                                <thead lass="text-xs text-gray-700 uppercase">
                                    <tr>
                                        <th class="px-3 py-2 w-6/12">Filename</th>
                                        <th class="px-3 py-2 w-1/12"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="_viewAssistance.attachments && _viewAssistance.attachments.length > 0">
                                        <tr class="bg-white border-b" v-for="(item, index) in _viewAssistance.attachments" :key="item.id">
                                            <td class="px-3 py-3 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                {{ item.filename }} 
                                            </td>
                                            <td class="px-3 py-3 font-medium text-gray-900 whitespace-nowrap uppercase text-center">
                                                <PrimaryButton class="bg-info-500 hover:bg-info-700 text-white mr-1" @click="viewAttachment(item.url)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                        <g id="SVGRepo_iconCarrier"> 
                                                            <path d="M15.0007 12C15.0007 13.6569 13.6576 15 12.0007 15C10.3439 15 9.00073 13.6569 9.00073 12C9.00073 10.3431 10.3439 9 12.0007 9C13.6576 9 15.0007 10.3431 15.0007 12Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> 
                                                            <path d="M12.0012 5C7.52354 5 3.73326 7.94288 2.45898 12C3.73324 16.0571 7.52354 19 12.0012 19C16.4788 19 20.2691 16.0571 21.5434 12C20.2691 7.94291 16.4788 5 12.0012 5Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> 
                                                        </g>
                                                    </svg>
                                                </PrimaryButton>
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else>
                                        <tr>
                                            <td colspan="2">No Attachment(s) Found.</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <template v-if="user.role == 1">
                    <PrimaryButton
                        v-if="actions.approve.show"
                        :class="[actions.approve.classes, 'text-white me-2', { 'opacity-25': processing }]"
                        :disabled="processing"
                        @click="updateStatus('approve')"
                    >
                        {{ actions.approve.label }}
                    </PrimaryButton>
    
                    <!-- DISAPPROVE -->
                    <template v-if="!_viewAssistance.assistance_name.includes('cash') && _viewAssistance.remarks != null">
                        <PrimaryButton
                            v-if="actions.disapprove.show"
                            :class="[actions.disapprove.classes, 'text-white me-2', { 'opacity-25': processing }]"
                            :disabled="processing"
                            @click="updateStatus('disapprove')"
                        >
                            {{ actions.disapprove.label }}
                        </PrimaryButton>
                    </template>
    
                    <!-- CANCEL -->
                    <PrimaryButton
                        v-if="actions.cancel.show"
                        :class="[actions.cancel.classes, 'text-white me-2', { 'opacity-25': processing }]"
                        :disabled="processing"
                        @click="updateStatus('cancel')"
                    >
                        {{ actions.cancel.label }}
                    </PrimaryButton>
                </template>
                <SecondaryButton @click="closeViewModal">Close</SecondaryButton>
            </template>
        </DialogModal>

        <DialogModal id="editAssistance" :show="editDialog" :max-width="'3xl'" @close="closeEditModal">
            <template #title>
                Edit Assistance
            </template>
            <template #content>
                <div>
                    <div class="py-3 lg:py-3 bg-white">
                        <div class="flex flex-row justify-between mb-5">
                            <div class="w-6/12">
                                <div class="form-group mb-5">
                                    <InputLabel for="type" value="Name" :required="true" />
                                    <div class="w-80">
                                        <Select2 class="uppercase mt-1" v-model="editForm.farmer" :options="props.farmer" :settings="{ placeholder: 'Select An Option',  width: '100%', dropdownParent: $('#editAssistance') }" @select="handleFarmer($event, 'edit')" />
                                    </div>
                                    <p v-if="editHasError('farmer')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="y$.farmer.required?.$invalid">Farmer is required!</span>
                                    </p>
                                </div>

                                <div class="form-group mb-5">
                                    <InputLabel for="type" value="Livelihood" :required="true" />
                                    <div class="w-80">
                                        <Select2 class="uppercase mt-1" v-model="editForm.livelihood" :options="livelihood" :settings="{ placeholder: 'Select An Option',  width: '100%', dropdownParent: $('#editAssistance') }" @select="handleLivelihood($event, 'edit')" />
                                    </div>

                                    <p v-if="editHasError('livelihood')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="y$.livelihood.required?.$invalid">Livelihood is required!</span>
                                    </p>
                                </div>

                                <div class="form-group mb-2">
                                    <InputLabel for="type" value="Type of Assistance" :required="true" />
                                    <div class="w-80">
                                        <Select2 class="uppercase mt-1" v-model="editForm.assistance" :options="availableAssistance" :settings="{ placeholder: 'Select An Option', width: '100%', dropdownParent: $('#editAssistance') }" />
                                    </div>
                                    <p v-if="editHasError('assistance')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="y$.assistance.required?.$invalid">Assistance is required!</span>
                                    </p>
                                </div>
                            </div>
                            <div class="w-6/12">
                                <InputLabel for="type" value="Purpose" :required="true" />
                                <TextAreaInput v-model="editForm.remarks" class="uppercase mt-1 block w-full uppercase" 
                                    @blur="y$.remarks.$touch()"
                                    :class="!y$.remarks.$dirty && editForm.remarks ? 'border-gray-300' : editInputBorderClass('remarks')"
                                />
                                <p v-if="editHasError('remarks')" class="text-red-500 text-sm mb-1">
                                    <span class="text-red-500 text-sm" v-if="y$.remarks.required?.$invalid">Purpose is required!</span>
                                </p>
                            </div>
                        </div>

                        <hr class="my-6 border-t border-gray-300 mb-4">

                        <div class="flex flex-row">
                            <div class="w-full">
                                <InputLabel for="attachments" value="Attachments" :required="true" />
                                <Dropzone @fileSelected="handleEditUploadSuccess" :uploadedFiles="editForm.attachments" :isMultiple="true" :accepted-files="'.pdf, .jpg, .png'"  />
                                <p v-if="editHasError('attachments')" class="text-red-500 text-sm mt-1">
                                    <span class="text-red-500 text-sm" v-if="y$.attachments.required?.$invalid">Attachments is required. Add atleast one attachment.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <ActionMessage :on="recentlySuccessful" class="me-3">
                    Assistance successfully updated.
                </ActionMessage>
                <ActionMessage :on="recentlyFailed" class="me-3">
                    Failed to update assistance.
                </ActionMessage>
                <PrimaryButton class="bg-blue-500 hover:bg-blue-700 text-white me-2" :class="{ 'opacity-25': processing }" 
                    :disabled="processing" @click="submitEditForm">Save</PrimaryButton>
                <SecondaryButton @click="closeEditModal">Close</SecondaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>