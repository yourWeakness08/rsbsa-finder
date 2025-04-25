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

    import Stepper from '@/Components/StepperNavigation.vue';
    import DropzoneInput from '@/Components/DropzoneProfileInput.vue'

    import Select2 from 'vue3-select2-component';

    import { Link, router, useForm, usePage } from '@inertiajs/vue3';
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
        filter: String,
        auth: {
            type: Object,
            default: () => ({}),
        },
    });

    const pageValue = ref(null);
    const searchValue = ref(null);
    const debouncedSearch = ref('');

    const pages = ref([ 10, 25, 50, 100, 200, 'All']);

    const form = useForm({
        surname: '', 
        firstname: '', 
        middlename: '', 
        suffix: '', 
        sex: '',
        birth: '', 
        birthplace: '', 
        religion: '',
        contact: '',
        civil_status: '', 
        spouse: '',
        mother_maiden_name: '',
        is_household_head: '',
        household_head_name: '',
        head_relationship: '',
        no_of_male: '',
        no_of_female: '',
        education: '',
        is_pwd: '',
        is_4ps: '',
        has_gov_id: '',
        is_farmer_member: ''
    })

    const dateFormat = (date) => {
        return moment(date).format('MMM. DD, YYYY hh:mm A');
    }

    const step = ref(0);
    const stepLabels = ['PERSONAL INFORMATION', 'FARM DETAILS', 'VIEW DETAILS'];

    const setStep = (index) => {
        step.value = index
    }

    const goToNext = () => {
        if (step.value < stepLabels.length - 1) {
            step.value++
        }
    }

    const goToPrevious = () => {
        if (step.value > 0) {
            step.value--
        }
    }

    const civil_status = ref([
        { id: 'Single', text: 'Single' },
        { id: 'Married', text: 'Married' },
        { id: 'Widowed', text: 'Widowed' },
        { id: 'Seperated', text: 'Seperated' },
    ]);

    const isMarried = ref(false);

    const civilType = (event) => {
        const selectedValue = event.id;

        isMarried.value = (selectedValue == 'Married' || selectedValue == 'Widowed') ? true : false;
    }

    const education = ref([
        { id: 'None', text: 'None' },
        { id: 'Elementary', text: 'Elementary' },
        { id: 'High School', text: 'High School' },
        { id: 'Vocational', text: 'Vocational' },
        { id: 'College', text: 'College' },
        { id: 'Post Graduate', text: 'Post Graduate' },
    ])
</script>

<template>
    <AppLayout title="Create Farmer">
        <template #header>
            <h2 class="font-semibold text-md text-gray-800 leading-tight">
                FARMERS / CREATE FARMER
            </h2>
        </template>

        <div class="py-8">
            <div class="w-full mx-auto">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
                        <Link class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 bg-blue-500 hover:bg-blue-700 text-white px-1 py-1" :href="route('farmers.index')">
                            Back to Masterfile
                        </Link>

                        <div class="max-w-2xl mx-auto p-6">
                            <Stepper :currentStep="step" :steps="stepLabels" @step-selected="setStep" />
                        </div>

                        <div class="sm:w-full md:w-4/5 lg:w-4/5 mx-auto px-2">
                            <!-- Form logic here -->
                            <div v-if="step === 0">
                                <div class="flex flex-wrap items-center">
                                    <div class="md:w-[18%] mx-auto">
                                        <InputLabel for="profile" value="Farmer Image" :required="true" />
                                        <DropzoneInput
                                            label="Profile Photo"
                                            upload-url="/upload/photo"
                                            @uploaded="(res) => console.log('File path:', res.path)"
                                        />
                                    </div>
                                    <div class="md:w-[79%] sm:w-full">
                                        <div class="flex flex-wrap justify-between mb-4">
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="firstname" value="First name" :required="true" />
                                                <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                            </div>
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="lastname" value="Last name" :required="true" />
                                                <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap justify-between">
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="middlename" value="Middle name" :required="true" />
                                                <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                            </div>
                                            <div class="sm:w-full md:w-2/12">
                                                <InputLabel for="extension" value="Extension" :required="true" />
                                                <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                            </div>
                                            <div class="sm:w-full md:w-[30%] md:p-x-2">
                                                <InputLabel for="gender" value="Gender" :required="true" />
                                                <div class="flex flex-wrap items-center mt-3">
                                                    <label class="md:w-[28%] flex items-center space-x-2 cursor-pointer">
                                                        <input type="radio" value="male" class="accent-blue-600" />
                                                        <span class="text-gray-700">Male</span>
                                                    </label>

                                                    <label class="md:w-[28%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                        <input type="radio" value="female" class="accent-blue-600" />
                                                        <span class="text-gray-700">Female</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-6 border-t border-gray-500" />

                                <div class="flex flex-wrap items-center justify-between mb-4">
                                    <div class="w-full">
                                        <h3 class="font-bold text-lg">ADDRESS</h3>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center justify-between mb-4">
                                    <div class="md:w-[32.10%] sm:w-full">
                                        <InputLabel for="house" value="House / Lot / Bldg. No." :required="true" />
                                        <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                    </div>
                                    <div class="md:w-[32.10%] sm:w-full">
                                        <InputLabel for="street" value="Street / Sitio / Subdv." :required="true" />
                                        <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                    </div>
                                    <div class="md:w-[32.10%] sm:w-full">
                                        <InputLabel for="barangay" value="Barangay" :required="true" />
                                        <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-between">
                                    <div class="md:w-[39.10%] sm:w-full">
                                        <InputLabel for="municipality" value="Municipality / City" :required="true" />
                                        <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                    </div>
                                    <div class="md:w-[39.10%] sm:w-full">
                                        <InputLabel for="province" value="Province" :required="true" />
                                        <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                    </div>
                                    <div class="md:w-[18%] sm:w-full">
                                        <InputLabel for="region" value="Region" :required="true" />
                                        <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                    </div>
                                </div>

                                <hr class="my-6 border-t border-gray-500" />

                                <div class="flex flex-wrap items-center justify-between mb-4">
                                    <div class="md:w-[24%] sm:w-full">
                                        <InputLabel for="contact" value="Contact No." :required="true" />
                                        <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                    </div>
                                    <div class="md:w-[24%] sm:w-full">
                                        <InputLabel for="birth" value="Date of Birth" :required="true" />
                                        <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                    </div>
                                    <div class="md:w-[24%] sm:w-full">
                                        <InputLabel for="birth-place" value="Place of Birth" :required="true" />
                                        <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                    </div>
                                    <div class="md:w-[24%] sm:w-full">
                                        <InputLabel for="religon" value="Religon" :required="true" />
                                        <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-x-4 align-start justify-center mb-4">
                                    <div class="md:w-[32%] sm:w-full">
                                        <InputLabel for="civil-status" value="Civil Status" :required="true" />
                                        <div class="rounded-md block w-full mt-1">
                                            <Select2 class="h-10 uppercase" :options="civil_status" :settings="{ placeholder: 'Select An Option', width: '100%' }" @select="civilType" />
                                        </div>

                                        <div class="mt-4" v-if="isMarried">
                                            <InputLabel for="spouse-name" value="Name of Spouse" :required="true" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                    </div>
                                    <div class="md:w-[32%] sm:w-full">
                                        <InputLabel for="mother-name" value="Mothers' Maiden Name" :required="true" />
                                        <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-x-4 align-start justify-center mb-4">
                                    <div class="w-full">
                                        <div class="flex items-center gap-6 mt-3">
                                            <!-- Label -->
                                            <InputLabel for="household-head" value="Household Head?" :required="true" class="mb-0" />

                                            <!-- Radio buttons -->
                                            <div class="flex items-center gap-4">
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="radio" value="1" class="accent-blue-600" v-model="form.is_household_head" />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="radio" value="0" class="accent-blue-600" v-model="form.is_household_head" />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="md:w-6/12 mx-auto sm:w-full mb-4" v-if="form.is_household_head == 0 && form.is_household_head != ''">
                                    <div class="flex flex-wrap align-start justify-between">
                                        <div class="sm:w-full md:w-[49%]">
                                            <InputLabel for="household-head" value="Name of Household Head" :required="true" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                        <div class="sm:w-full md:w-[49%]">
                                            <InputLabel for="relationship" value="Relationship" :required="true" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                    </div>
                                </div>

                                <div class="md:w-8/12 mx-auto sm:w-full mb-4">
                                    <div class="flex flex-wrap align-start justify-between">
                                        <div class="sm:w-full md:w-[32%]">
                                            <InputLabel for="living-household-members" value="No. of living household members" :required="true" />
                                            <TextInput type="number" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                        <div class="sm:w-full md:w-[32%]">
                                            <InputLabel for="no-of-male" value="No. of Male" :required="true" />
                                            <TextInput type="number" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                        <div class="sm:w-full md:w-[32%]">
                                            <InputLabel for="no-of-female" value="No. of Female" :required="true" />
                                            <TextInput type="number" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-6 border-t border-gray-500" />

                                <div class="flex flex-wrap gap-x-5 align-start justify-center mb-4">
                                    <div class="md:w-[32%] sm:w-full md:mb-4">
                                        <InputLabel for="education" value="Highest Formal Education" :required="true" />
                                        <div class="rounded-md block w-full mt-1">
                                            <Select2 class="h-10 uppercase" :options="education" :settings="{ placeholder: 'Select An Option', width: '100%' }" @select="civilType" />
                                        </div>

                                        <div class="mt-4" v-if="isMarried">
                                            <InputLabel for="spouse-name" value="Name of Spouse" :required="true" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                    </div>
                                    <div class="md:w-[32%] sm:w-full md:mb-4">
                                        <InputLabel for="pwd" value="Person with Disability (PWD)" :required="true" />
                                        <div class="flex flex-wrap items-center mt-3">
                                            <label class="md:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                <input type="radio" value="1" class="accent-blue-600" />
                                                <span class="text-gray-700">Yes</span>
                                            </label>

                                            <label class="md:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                <input type="radio" value="0" class="accent-blue-600" />
                                                <span class="text-gray-700">No</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="md:w-[32%] sm:w-full md:mb-4">
                                        <InputLabel for="4ps" value="4P's Beneficiary?" :required="true" />
                                        <div class="flex flex-wrap items-center mt-3">
                                            <label class="md:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                <input type="radio" value="1" class="accent-blue-600" />
                                                <span class="text-gray-700">Yes</span>
                                            </label>

                                            <label class="md:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                <input type="radio" value="0" class="accent-blue-600" />
                                                <span class="text-gray-700">No</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="md:w-[32%] sm:w-full md:mb-4">
                                        <InputLabel for="gov-id" value="With Government ID?" :required="true" />
                                        <div class="flex flex-wrap items-center mt-3">
                                            <label class="md:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                <input type="radio" value="1" class="accent-blue-600" v-model="form.has_gov_id" />
                                                <span class="text-gray-700">Yes</span>
                                            </label>

                                            <label class="md:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                <input type="radio" value="0" class="accent-blue-600"  v-model="form.has_gov_id" />
                                                <span class="text-gray-700">No</span>
                                            </label>
                                        </div>

                                        <div class="mt-4" v-if="form.has_gov_id == 1">
                                            <InputLabel for="specify_id" value="Specify ID number" :required="true" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                    </div>
                                    <div class="md:w-[32%] sm:w-full md:mb-4">
                                        <InputLabel for="gov-id" value="Member of any Farmers Association / Cooperative?" :required="true" />
                                        <div class="flex flex-wrap items-center mt-3">
                                            <label class="md:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                <input type="radio" value="1" class="accent-blue-600" v-model="form.is_farmer_member" />
                                                <span class="text-gray-700">Yes</span>
                                            </label>

                                            <label class="md:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                <input type="radio" value="0" class="accent-blue-600" v-model="form.is_farmer_member" />
                                                <span class="text-gray-700">No</span>
                                            </label>
                                        </div>

                                        <div class="mt-4" v-if="form.is_farmer_member == 1">
                                            <InputLabel for="specify_farmer_asso" value="Specify" :required="true" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-6 border-t border-gray-500" />

                                <div class="md:w-6/12 mx-auto sm:w-full mb-4">
                                    <div class="flex flex-wrap align-start justify-between">
                                        <div class="sm:w-full md:w-[49%]">
                                            <InputLabel for="person-emergency" value="Person to notify in case of Emergency" :required="true" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                        <div class="sm:w-full md:w-[49%]">
                                            <InputLabel for="contact-emergency-no" value="Contact No." :required="true" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="step === 1"> <!-- Account Details fields --> </div>
                            <div v-if="step === 2"> <!-- Role Assignment fields --> </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="button" class="inline-flex items-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded me-2" @click="goToPrevious" v-if="step > 0" > 
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="me-1 size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" />
                                </svg>
                                <strong>PREVIOUS</strong>
                            </button>

                            <button v-if="step < stepLabels.length - 1" type="button" class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded" @click="goToNext"> 
                                <strong>NEXT</strong>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="ms-1 size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                                </svg>
                            </button>

                            <button v-else type="button" class="bg-green-700 hover:bg-green-600 text-white px-4 py-2 rounded" @click="submitForm" :disabled="form.processing" >
                                <strong>SUBMIT</strong> 
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>