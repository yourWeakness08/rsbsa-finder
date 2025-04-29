<script setup>
    import { ref, reactive, computed, getCurrentInstance, watch, onMounted } from 'vue';
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

    const routeName = 'create-farmer' // Optional: could be dynamic
    const STORAGE_KEY = `form-data-${routeName}`;

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
        is_farmer_member: '',
        main_livelihood: [],
        farmer: [],
        farm_worker: [],
        fisherfolks: [],
        agri_youth: [],
        farming_gross_income: 0,
        non_farming_gross_income: 0,
        farm_parcel_no: 0,
        is_arb: '',
        farm_parcel: [
            {
                municipality: '',
                brgy: '',
                total_farm_area: '',
                owner_doc_no: '',
                ownership_type: '',
                land_owner_name: '',
                is_other: '',
                farm_parcel_info: [
                    {
                        commondity: '',
                        size: '',
                        head_no: '',
                        farm_type: '',
                        is_organic_practitioner: ''
                    }
                ]
            }
        ]
    });

    const dateFormat = (date) => {
        return moment(date).format('MMM. DD, YYYY hh:mm A');
    }

    const step = ref(1);
    const stepLabels = ['PERSONAL INFORMATION', 'FARM DETAILS', 'VIEW DETAILS'];

    const setStep = (index) => {
        step.value = index
    }

    const goToNext = () => {
        if (step.value < stepLabels.length - 1) {
            step.value++

            // localStorage.setItem(STORAGE_KEY, JSON.stringify(form.data()))

            console.log(form);
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

    onMounted(() => {
        const saved = localStorage.getItem(STORAGE_KEY);
        
        // localStorage.removeItem(STORAGE_KEY) remove cached item
        if (saved) {
            try {
                const parsed = JSON.parse(saved)
                Object.keys(form.data()).forEach(key => {
                    if (parsed[key] !== undefined) {
                    form[key] = parsed[key]
                    }
                })
            } catch (e) {
                console.error('Error restoring form:', e)
            }
        }
    });

    const main_livelihood = ref([
        { value: 'farmer', label: 'Farmer' },
        { value: 'farm_worker', label: 'Farm Worker / Laborer' },
        { value: 'fisherfolks', label: 'Fisherfolk' },
        { value: 'agri_youth', label: 'Agri Youth' },
    ]);

    const handleLivelihood = (e) => {
        const selectedValue = e.target.value;

        if (form.main_livelihood.includes(selectedValue)) {
            const index = form.main_livelihood.indexOf(selectedValue);
            form.main_livelihood.splice(index, 1);
        } else {
            form.main_livelihood.push(selectedValue);
        }
    }

    const handleFarmer = (e) => {
        const selectedValue = e.target.value;

        if (form.farmer.includes(selectedValue)) {
            const index = form.farmer.indexOf(selectedValue);
            form.farmer.splice(index, 1);
        } else {
            form.farmer.push(selectedValue);
        }
    }
    
    const handleFarmWorker = (e) => {
        const selectedValue = e.target.value;

        if (form.farm_worker.includes(selectedValue)) {
            const index = form.farm_worker.indexOf(selectedValue);
            form.farm_worker.splice(index, 1);
        } else {
            form.farm_worker.push(selectedValue);
        }
    }

    const handleFisherFolks = (e) => {
        const selectedValue = e.target.value;

        if (form.fisherfolks.includes(selectedValue)) {
            const index = form.fisherfolks.indexOf(selectedValue);
            form.fisherfolks.splice(index, 1);
        } else {
            form.fisherfolks.push(selectedValue);
        }
    }
    
    const handleAgriYouth = (e) => {
        const selectedValue = e.target.value;

        if (form.agri_youth.includes(selectedValue)) {
            const index = form.agri_youth.indexOf(selectedValue);
            form.agri_youth.splice(index, 1);
        } else {
            form.agri_youth.push(selectedValue);
        }
    }

    const ownership_type = ref([
        { id: 'Registered Owner', text: 'Registered Owner' },
        { id: 'Tenant', text: 'Tenant' },
        { id: 'Lesse', text: 'Lesse' },
        { id: 'Others', text: 'Others' },
    ]);

    const farm_type = ref([
        { id: '1', text: 'Irrigated' },
        { id: '2', text: 'Rainfed Upland' },
        { id: '3', text: 'Rainfed Lowland' },
    ]);

    const addParcelInfo = (i) => {
        form.farm_parcel[i].farm_parcel_info.push({
            commondity: '',
            size: '',
            head_no: '',
            farm_type: '',
            is_organic_practitioner: ''
        });
    }

    const removeParcelInfo = (index, farmIndex) => {
        form.farm_parcel[farmIndex].farm_parcel_info.splice(index, 1);
    }

    const addFarmParcel = () => {
        form.farm_parcel.push({
            municipality: '',
            brgy: '',
            total_farm_area: '',
            owner_doc_no: '',
            ownership_type: '',
            land_owner_name: '',
            is_other: '',
            farm_parcel_info: [
                {
                    commondity: '',
                    size: '',
                    head_no: '',
                    farm_type: '',
                    is_organic_practitioner: ''
                }
            ]
        })
    }

    const removeFarmParcel = (index) => {
        form.farm_parcel.splice(index, 1);
    }
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

                        <div class="sm:w-full sm:w-full md:w-5/6 lg:w-[85%] mx-auto px-2">
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
                                                <TextInput type="text" name="firstname" v-model="form.firstname" class="mt-1 block w-full uppercase" autocomplete="off" />
                                            </div>
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="lastname" value="Last name" :required="true" />
                                                <TextInput type="text" name="lastname" v-model="form.lastname" class="mt-1 block w-full uppercase" autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap justify-between">
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="middlename" value="Middle name" :required="true" />
                                                <TextInput type="text" name="middlename" v-model="form.middlename" class="mt-1 block w-full uppercase" autocomplete="off" />
                                            </div>
                                            <div class="sm:w-full md:w-2/12">
                                                <InputLabel for="extension" value="Extension" :required="true" />
                                                <TextInput type="text" name="suffix" v-model="form.suffix" class="mt-1 block w-full uppercase" autocomplete="off" />
                                            </div>
                                            <div class="sm:w-full md:w-[30%] md:p-x-2">
                                                <InputLabel for="gender" value="Gender" :required="true" />
                                                <div class="flex flex-wrap items-center mt-3">
                                                    <label class="md:w-[28%] flex items-center space-x-2 cursor-pointer">
                                                        <input type="radio" name="gender" v-model="form.gender" value="male" class="accent-blue-600" />
                                                        <span class="text-gray-700">Male</span>
                                                    </label>

                                                    <label class="md:w-[28%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                        <input type="radio" name="gender" v-model="form.gender" value="female" class="accent-blue-600" />
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
                                                    <TextInput type="radio" value="1" class="accent-blue-600" name="is_household_head" v-model="form.is_household_head" />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="0" class="accent-blue-600" name="is_household_head" v-model="form.is_household_head" />
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
                                                <TextInput type="radio" value="1" name="is_pwd" v-model="form.is_pwd" class="accent-blue-600" />
                                                <span class="text-gray-700">Yes</span>
                                            </label>

                                            <label class="md:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                <TextInput type="radio" value="0" name="is_pwd" v-model="form.is_pwd" class="accent-blue-600" />
                                                <span class="text-gray-700">No</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="md:w-[32%] sm:w-full md:mb-4">
                                        <InputLabel for="4ps" value="4P's Beneficiary?" :required="true" />
                                        <div class="flex flex-wrap items-center mt-3">
                                            <label class="md:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                <TextInput type="radio" value="1" name="is_4ps" v-model="form.is_4ps" class="accent-blue-600" />
                                                <span class="text-gray-700">Yes</span>
                                            </label>

                                            <label class="md:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                <TextInput type="radio" value="0" name="is_4ps" v-model="form.is_4ps" class="accent-blue-600" />
                                                <span class="text-gray-700">No</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="md:w-[32%] sm:w-full md:mb-4">
                                        <InputLabel for="gov-id" value="With Government ID?" :required="true" />
                                        <div class="flex flex-wrap items-center mt-3">
                                            <label class="md:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                <TextInput type="radio" value="1" class="accent-blue-600" name="has_gov_id" v-model="form.has_gov_id" />
                                                <span class="text-gray-700">Yes</span>
                                            </label>

                                            <label class="md:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                <TextInput type="radio" value="0" class="accent-blue-600" name="has_gov_id" v-model="form.has_gov_id" />
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
                                                <TextInput type="radio" value="1" class="accent-blue-600" name="is_farmer_member" v-model="form.is_farmer_member" />
                                                <span class="text-gray-700">Yes</span>
                                            </label>

                                            <label class="md:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                <TextInput type="radio" value="0" class="accent-blue-600"  name="is_farmer_member" v-model="form.is_farmer_member" />
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
                            <div v-if="step === 1">
                                <div class="mb-4">
                                    <div class="flex flex-wrap w-full mb-4">
                                        <div class="w-full">
                                            <h3 class="font-bold text-lg">MAIN LIVELIHOOD</h3>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap mb-4 justify-center">
                                        <div class="md:w-9/12">
                                            <div class="flex flex-wrap items-center justify-center md:gap-x-32">
                                                <div v-for="option in main_livelihood" :key="option.value" class="inline-flex items-center space-x-2" >
                                                    <TextInput type="checkbox" :id="option.value" :value="option.value" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleLivelihood" :checked="form.main_livelihood.includes(option.value)" />
                                                    <InputLabel :for="option.value" :value="option.label" class="text-sm text-gray-700 cursor-pointer" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap justify-center items-stretch gap-x-3">
                                        <div class="bg-white shadow-xl sm:w-full rounded-md" v-if="form.main_livelihood.includes('farmer')"
                                            :class="{
                                                'md:w-[49%]' : form.main_livelihood.length >= 1 && form.main_livelihood.length <= 2,
                                                'md:w-[32%]' : form.main_livelihood.length == 3,
                                                'md:w-[24%]' : form.main_livelihood.length == 4
                                            }"
                                        >
                                            <div class="p-4 lg:p-6 bg-white">
                                                <h4 class="text-center font-bold italic text-lg mb-3">For Farmers:</h4>
    
                                                <h5 class="font-bold text-md mb-2">Type of Farming Activity</h5>
    
                                                <div class="flex flex-wrap">
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.farmer.includes('rice')" value="rice" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmer" />
                                                            <InputLabel for="rice" value="Rice" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
                                                    </div>
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.farmer.includes('corn')" value="corn" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmer" />
                                                            <InputLabel for="corn" value="Corn" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
                                                    </div>
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" id="crops" :checked="form.farmer.includes('crops')" value="crops" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmer" />
                                                            <InputLabel for="crops" value="Other crops" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
    
                                                        <div class="flex flex-wrap items-center ms-4" v-if="form.farmer.includes('crops')">
                                                            <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                            <div class="rounded-md block mt-1 w-full">
                                                                <Select2 class="h-10 uppercase" :options="civil_status" :settings="{ placeholder: 'Select An Option', width: '100%' }" @select="civilType" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" id="livestock" :checked="form.farmer.includes('livestock')" value="livestock" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmer" />
                                                            <InputLabel for="livestock" value="Livestock" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
    
                                                        <div class="flex flex-wrap items-center ms-4" v-if="form.farmer.includes('livestock')">
                                                            <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                            <div class="rounded-md block mt-1 w-full">
                                                                <Select2 class="h-10 uppercase" :options="civil_status" :settings="{ placeholder: 'Select An Option', width: '100%' }" @select="civilType" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="w-full">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" id="poultry" :checked="form.farmer.includes('poultry')" value="poultry" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmer" />
                                                            <InputLabel for="poultry" value="Poultry" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
    
                                                        <div class="flex flex-wrap items-center ms-4" v-if="form.farmer.includes('poultry')">
                                                            <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                            <div class="rounded-md block mt-1 w-full">
                                                                <Select2 class="h-10 uppercase" :options="civil_status" :settings="{ placeholder: 'Select An Option', width: '100%' }" @select="civilType" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-white shadow-xl sm:w-full rounded-md" v-if="form.main_livelihood.includes('farm_worker')"
                                            :class="{
                                                'md:w-[49%]' : form.main_livelihood.length >= 1 && form.main_livelihood.length <= 2,
                                                'md:w-[32%]' : form.main_livelihood.length == 3,
                                                'md:w-[22%]' : form.main_livelihood.length == 4
                                            }"
                                        >
                                            <div class="p-4 lg:p-6 bg-white">
                                                <h4 class="text-center font-bold italic text-lg mb-3">For Farmworkers:</h4>
    
                                                <h5 class="font-bold text-md mb-2">Kind of Work</h5>
    
                                                <div class="flex flex-wrap">
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.farm_worker.includes('Land Preparation')" value="Land Preparation" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmWorker" />
                                                            <InputLabel for="land-preparation" value="Land Preparation" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
                                                    </div>
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.farm_worker.includes('Planting / Transplanting')" value="Planting / Transplanting" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmWorker" />
                                                            <InputLabel for="planting" value="Planting / Transplanting" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
                                                    </div>
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.farm_worker.includes('Cultivation')" value="Cultivation" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmWorker" />
                                                            <InputLabel for="cultivation" value="Cultivation" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
                                                    </div>
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.farm_worker.includes('Harvesting')" value="Harvesting" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmWorker" />
                                                            <InputLabel for="harvesting" value="Harvesting" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
                                                    </div>
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.farm_worker.includes('Others')" value="Others" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmWorker" />
                                                            <InputLabel for="farmworker-other" value="Others" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
    
                                                        <div class="flex flex-wrap items-center ms-4" v-if="form.farm_worker.includes('Others')">
                                                            <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                            <div class="rounded-md block mt-1 w-full">
                                                                <Select2 class="h-10 uppercase" :options="civil_status" :settings="{ placeholder: 'Select An Option', width: '100%' }" @select="civilType" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-white shadow-xl sm:w-full rounded-md" v-if="form.main_livelihood.includes('fisherfolks')"
                                            :class="{
                                                'md:w-[49%]' : form.main_livelihood.length >= 1 && form.main_livelihood.length <= 2,
                                                'md:w-[32%]' : form.main_livelihood.length == 3,
                                                'md:w-[25%]' : form.main_livelihood.length == 4
                                            }"
                                        >
                                            <div class="p-4 lg:p-6 bg-white">
                                                <h4 class="text-center font-bold italic text-lg mb-3">For Fisherfolk:</h4>
    
                                                <p class="mb-3">The Lending Conduit shall coordinate with the Bureau of Fisheries and Aquatic Resources (BFAR) in the issuance of a certification that the fisherfolk-borrower under PUNLA / PLEA is registered under the Municipal Fisherfolk Registration (FishR).</p>
    
                                                <h5 class="font-bold text-md mb-2">Type if Fishing Activity</h5>
    
                                                <div class="flex flex-wrap">
                                                    <div class="flex flex-wrap items-center justify-between w-full mb-2">
                                                        <div class="md:w-[49%] sm:w-full">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.fisherfolks.includes('Fish Capture')" value="Fish Capture" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFisherFolks" />
                                                                <InputLabel for="fish-capture" value="Fish Capture" class="text-sm text-gray-700 cursor-pointer" />
                                                            </div>
                                                        </div>
                                                        <div class="md:w-[49%] sm:w-full">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.fisherfolks.includes('Fish Processing')" value="Fish Processing" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFisherFolks" />
                                                                <InputLabel for="fish-processing" value="Fish Processing" class="text-sm text-gray-700 cursor-pointer" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-wrap items-center justify-between w-full mb-2">
                                                        <div class="md:w-[49%] sm:w-full">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.fisherfolks.includes('Aquaculture')" value="Aquaculture" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFisherFolks" />
                                                                <InputLabel for="aquaculture" value="Aquaculture" class="text-sm text-gray-700 cursor-pointer" />
                                                            </div>
                                                        </div>
                                                        <div class="md:w-[49%] sm:w-full">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.fisherfolks.includes('Fish Vending')" value="Fish Vending" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFisherFolks" />
                                                                <InputLabel for="fish-vending" value="Fish Vending" class="text-sm text-gray-700 cursor-pointer" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-wrap items-center justify-between w-full mb-2">
                                                        <div class="md:w-[49%] sm:w-full">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.fisherfolks.includes('Gleaning')" value="Gleaning" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFisherFolks" />
                                                                <InputLabel for="gleaning" value="Gleaning" class="text-sm text-gray-700 cursor-pointer" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.fisherfolks.includes('Others')" value="Others" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFisherFolks" />
                                                            <InputLabel for="fisherfolk-other" value="Others" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
    
                                                        <div class="flex flex-wrap items-center ms-4" v-if="form.fisherfolks.includes('Others')">
                                                            <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                            <div class="rounded-md block mt-1 w-full">
                                                                <Select2 class="h-10 uppercase" :options="civil_status" :settings="{ placeholder: 'Select An Option', width: '100%' }" @select="civilType" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-white shadow-xl sm:w-full rounded-md" v-if="form.main_livelihood.includes('agri_youth')"
                                            :class="{
                                                'md:w-[49%]' : form.main_livelihood.length >= 1 && form.main_livelihood.length <= 2,
                                                'md:w-[32%]' : form.main_livelihood.length == 3,
                                                'md:w-[25%]' : form.main_livelihood.length == 4
                                            }"
                                        >
                                            <div class="p-4 lg:p-6 bg-white">
                                                <h4 class="text-center font-bold italic text-lg mb-3">For Agri Youth:</h4>
    
                                                <p class="mb-3">For the purposes of trainings, financial assistance, and either programs and catered to the youth with involvement to any agriculture activity.</p>
    
                                                <h5 class="font-bold text-md mb-2">Type of Involvement</h5>
    
                                                <div class="flex flex-wrap">
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.agri_youth.includes('Part of a farming household')" value="Part of a farming household" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleAgriYouth" />
                                                            <InputLabel for="part-of-farming-household" value="Part of a farming household" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
                                                    </div>
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.agri_youth.includes('Attending / attended formal agri-fishery related course')" value="Attending / attended formal agri-fishery related course" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleAgriYouth" />
                                                            <InputLabel for="attended-formal-agri-fishery" value="Attending / attended formal agri-fishery related course" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
                                                    </div>
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.agri_youth.includes('Attending / attended non-formal agri-fishery related course')" value="Attending / attended non-formal agri-fishery related course" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleAgriYouth" />
                                                            <InputLabel for="attended-non-formal-agri-fishery" value="Attending / attended non-formal agri-fishery related course" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
                                                    </div>
                                                    <div class="w-full mb-2">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.agri_youth.includes('Participated a any agircultural activity / program')" value="Participated a any agircultural activity / program" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleAgriYouth" />
                                                            <InputLabel for="participated-any-agri-activity" value="Participated a any agircultural activity / program" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
                                                    </div>
                                                    <div class="w-full">
                                                        <div class="inline-flex items-center space-x-2">
                                                            <TextInput type="checkbox" :checked="form.agri_youth.includes('Others')" value="Others" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleAgriYouth" />
                                                            <InputLabel for="youth-others" value="Others" class="text-sm text-gray-700 cursor-pointer" />
                                                        </div>
    
                                                        <div class="flex flex-wrap items-center ms-4" v-if="form.agri_youth.includes('Others')">
                                                            <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                            <div class="rounded-md block mt-1 w-full">
                                                                <Select2 class="h-10 uppercase" :options="civil_status" :settings="{ placeholder: 'Select An Options', width: '100%' }" @select="civilType" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="flex flex-wrap w-full mb-3">
                                        <div class="w-full">
                                            <h3 class="font-bold text-lg uppercase">Gross Annual Income last year</h3>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap justify-center">
                                        <div class="md:w-6/12 mx-auto sm:w-full">
                                            <div class="flex flex-wrap justify-between">
                                                <div class="sm:w-full md:w-[49%]">
                                                    <InputLabel for="farming" value="Farming" :required="true" />
                                                    <TextInput type="number" class="mt-1 block w-full uppercase" autocomplete="off" />
                                                </div>
                                                <div class="sm:w-full md:w-[49%]">
                                                    <InputLabel for="non-farming" value="Non-farming" :required="true" />
                                                    <TextInput type="number" class="mt-1 block w-full uppercase" autocomplete="off" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-6 border-t border-gray-500" />
                                
                                <div class="mb-6">
                                    <div class="flex flex-wrap items-center justify-between">
                                        <div class="md:w-5/12 sm:w-full">
                                            <div class="flex flex-wrap items-center">
                                                <InputLabel for="farm-parcels" class="w-4/12" value="No. of Farm Parcels" :required="true" />
                                                <TextInput type="number" class="block uppercase w-2/12" autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="md:w-7/12 sm:w-full">
                                            <div class="flex flex-wrap items-center">
                                                <InputLabel for="farm-parcels" class="w-4/12 me-4" value="Agrarian Reform Beneficiary (ARB)" :required="true" />
                                                <div class="flex flex-wrap items-center w-5/12 space-x-3">
                                                    <label class="md:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                        <TextInput type="radio" value="1" class="accent-blue-600" />
                                                        <span class="text-gray-700">Yes</span>
                                                    </label>

                                                    <label class="md:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                        <TextInput type="radio" value="0" class="accent-blue-600" />
                                                        <span class="text-gray-700">No</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="overflow-auto w-full">
                                        <template v-for="(item, index) in form.farm_parcel" :key="index">
                                            <div :class="form.farm_parcel.length > 0 ? 'mb-4' : 'mb-0'">
                                                <div class="p-6 lg:p-8 bg-white border shadow-3xl rounded-lg border-gray-300">
                                                    <div class="flex flex-wrap justify-between items-center mb-4">
                                                        <div class="w-3/12">
                                                            <h4 class="text-MD font-semibold">FARM PARCEL NO: {{ index+1 }}</h4>
                                                        </div>
                                                        <div class="w-3/12">
                                                            <div class="flex flex-wrap gap-x-2 justify-end">
                                                                <button @click="addFarmParcel" class="bg-green-500 text-white px-3 py-2 rounded-sm text-sm hover:bg-green-600 flex items-center">
                                                                    <span class="text-sm leading-none">Add Farm Parcel</span>
                                                                </button>
                                                                <button v-if="index != 0" @click="removeFarmParcel(index)" class="bg-red-500 text-white px-3 py-2 rounded-sm text-sm hover:bg-red-600 flex items-center">
                                                                    <span class="text-sm leading-none">Remove Farm Parcel</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-wrap justify-between mb-4">
                                                        <div class="w-full">
                                                            <div class="flex flex-wrap justify-between gap-x-1">
                                                                <div class="w-[40%]">
                                                                    <InputLabel for="farm_municipal" value="Municipality" :required="true" />
                                                                    <TextInput type="text" v-model="item.municipality" class="mt-1 block w-full uppercase" autocomplete="off" />
                                                                </div>
                                                                <div class="w-[40%]">
                                                                    <InputLabel for="farm_brgy" value="Barangay" :required="true" />
                                                                    <TextInput type="text" v-model="item.brgy" class="mt-1 block w-full uppercase" autocomplete="off" />
                                                                </div>
                                                                <div class="w-2/12">
                                                                    <InputLabel for="total_farm_area" value="Total Farm Area" :required="true" />
                                                                    <div class="mt-1 flex rounded-md shadow-sm">
                                                                        <TextInput type="text" class="flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" autocomplete="off" style="height: 41px" />
                                                                        <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"> ha </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-wrap justify-center items-end gap-x-5 mb-5">
                                                        <div class="w-3/12">
                                                            <InputLabel for="ownership_doc" value="Ownership Document No" :required="true" />
                                                            <TextInput type="text" v-model="item.owner_doc_no" class="mt-1 block w-full uppercase" autocomplete="off" />
                                                        </div>
                                                        <div class="w-3/12">
                                                            <InputLabel for="ownership_type" value="Type of Ownership" :required="true" />
                                                            <div class="rounded-md block w-full mt-1">
                                                                <Select2 class="uppercase" :options="ownership_type" v-model="item.ownership_type" :settings="{ placeholder: 'Select An Option', width: '100%' }" />
                                                            </div>
                                                        </div>
                                                        <div class="w-4/12" v-if="item.ownership_type == 'Tenant' || item.ownership_type == 'Lesse'">
                                                            <InputLabel for="Name of Land Owner" value="Name of Land Owner" :required="true" />
                                                            <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                                        </div>
                                                        <div class="w-4/12"v-if="item.ownership_type == 'Others'">
                                                            <InputLabel for="specify-other" value="Specify" :required="true" />
                                                            <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" />
                                                        </div>
                                                    </div>

                                                    <table class="border-collapse border-2 border-gray-400 w-full">
                                                        <thead>
                                                            <tr>
                                                                <th class="p-3 border border-gray-400 w-[30%]">
                                                                    <strong>CROP / COMMONDITY</strong>
                                                                    <p class="m-0">
                                                                        <small class="italic">( Rice / Corn / HVC / Livestock / Poultry /agri-fishery )</small>
                                                                    </p>

                                                                    <strong>For Livestock & Poultry</strong>
                                                                    <p class="m-0">
                                                                        <small>( Specify type of animal) </small>
                                                                    </p>
                                                                </th>
                                                                <th class="p-3 border border-gray-400 w-[11%]">SIZE (ha)</th>
                                                                <th class="p-3 border border-gray-400 w-[8%]">
                                                                    <strong>NO. OF HEAD</strong>
                                                                    <p class="m-0">
                                                                        <small class="italic">( For livestock and poultry)</small>
                                                                    </p>
                                                                </th>
                                                                <th class="p-3 border border-gray-400 w-[24%]">FARM TYPE</th>
                                                                <th class="p-3 border border-gray-400 w-[18%]">ORGANIC PRACTITIONER</th>
                                                                <th class="p-3 border border-gray-400 w-[10%]"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(v, i) in item.farm_parcel_info" :key="i">
                                                                <td class="p-3 border border-gray-400">
                                                                    <div class="rounded-md block w-full">
                                                                        <Select2 class="h-10 uppercase" v-model="v.commondity" :options="ownership_type" :settings="{ placeholder: 'Select An Option', width: '100%' }" />
                                                                    </div>
                                                                </td>
                                                                <td class="p-3 border border-gray-400">
                                                                    <TextInput type="number" class="block w-full uppercase" v-model="v.size" autocomplete="off" min="0" value="0" />
                                                                </td>
                                                                <td class="p-3 border border-gray-400">
                                                                    <TextInput type="number" class="block w-full uppercase" v-model="v.head_no" autocomplete="off" min="0" value="0" />
                                                                </td>
                                                                <td class="p-3 border border-gray-400">
                                                                    <div class="rounded-md block w-full">
                                                                        <Select2 class="h-10 uppercase" v-model="v.farm_type" :options="farm_type" :settings="{ placeholder: 'Select Option', width: '100%' }" />
                                                                    </div>
                                                                </td>
                                                                <td class="p-3 border border-gray-400">
                                                                    <div class="rounded-md block w-full">
                                                                        <Select2 class="h-10 uppercase" v-model="v.is_organic_practitioner" :options="[{id: 1, text: 'Yes' }, {id: 0, text: 'No'}]" :settings="{ placeholder: 'Select', width: '100%' }" />
                                                                    </div>
                                                                </td>
                                                                <td class="p-3 border border-gray-400">
                                                                    <div class="flex flex-wrap gap-x-1 justify-center mt-2">
                                                                        <button @click="addParcelInfo(index)" class="bg-green-500 text-white px-2 py-1 rounded-full text-sm hover:bg-green-600 flex items-center">
                                                                            <span class="text-lg leading-none">+</span>
                                                                        </button>
                                                                        <button v-if="i != 0" @click="removeParcelInfo(i, index)" class="bg-red-500 text-white px-2 py-1 rounded-full text-sm hover:bg-red-600 flex items-center">
                                                                            <span class="text-lg leading-none">−</span>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
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