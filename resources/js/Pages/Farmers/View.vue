<script setup>
    import useValidationHelpers from '@/composables/useValidationHelpers'
    import { ref, reactive, computed, getCurrentInstance, watch, onMounted, nextTick, onBeforeUpdate } from 'vue';
    import useVuelidate from '@vuelidate/core';
    import { required, email, minLength, requiredIf, numeric, helpers } from '@vuelidate/validators';
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
    import DropzoneInput from '@/Components/DropzoneProfileInput.vue';
    import Dropzone from '@/Components/Dropzone.vue';
    import FarmerTabs from '@/Components/FarmerTabs.vue';

    import Select2 from 'vue3-select2-component';

    import { Link, router, usePage, useForm } from '@inertiajs/vue3';
    import axios from 'axios';
    import daterangepicker from 'daterangepicker';

    import Swal from 'sweetalert2';
    import Inputmask from 'inputmask';
    
    import $ from 'jquery';

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
        types: {
            type: Object,
            default: () => ({})
        }
    });

    let mergeTypes = ref([]);
    mergeTypes = Object.values(props.types).flat();
    mergeTypes.sort((a, b) => a.text.localeCompare(b.text));
    
    const back = () => {
        const url = route('farmers.index');
        router.visit(url)
    }

    const main_livelihood = ref([
        { value: 'farmer', label: 'Farmer' },
        { value: 'farm_worker', label: 'Farm Worker / Laborer' },
        { value: 'fisherfolks', label: 'Fisherfolk' },
        { value: 'agri_youth', label: 'Agri Youth' },
    ]);

    const farmCommodity = (item) => {
        let text = '';
        const val = typeof item === 'string' ? item.toLowerCase() : parseInt(item);

        $.each(mergeTypes, function(index, value) {
            const temp = value.id;
            const _val = typeof temp === 'string' ? temp.toLowerCase() : parseInt(temp);
            
            if (_val == val){
                text = value.text;
            }
        })

        return text.toUpperCase();
    }

    const farm_type = ref([
        { id: '1', text: 'Irrigated' },
        { id: '2', text: 'Rainfed Upland' },
        { id: '3', text: 'Rainfed Lowland' },
    ]);

    const formatFarmType = (id) => {
        let text = '';
        if (id) {
            const result = farm_type.value.find(item => item.id == id);
            text = result.text;
        }
        return text;
    }

    const checkMeta = (arr, meta) => {
        let result = false;
        $.each(arr, function(index, value) {
            if (value.meta.toLowerCase() == meta) {
                result = true;
                return result;
            }
        });
        return result;
    }



    const famerSpecify = (farmingtype, arr) => {
        let temp = [];
        let _temp = [];
        $.each(arr, function(index, v) {
            if (v.meta.toLowerCase() == farmingtype) {
                _temp.push(parseInt(v.value));
            }
        });
        $.each(props.types[farmingtype], function(index, value) {
            if (_temp.includes(value.id)) {
                temp.push(value.text.toUpperCase());
            }
        });

        return temp.length > 0 ? temp.join(', ') : "";
    }

    const otherActivity = (type, arr) => {
        let text = '';

        $.each(arr, function(index, v) {
            if (v.meta == type) {
                text = v.value
            }
        });

        return text;
    }
</script>

<template>
    <AppLayout title="View Farmer Information">
        <template #header>
            <h2 class="font-semibold text-md text-gray-800 leading-tight">
                FARMERS / VIEW FARMER
            </h2>
        </template>

        <div class="py-8 uppercase">
            <div class="flex flex-wrap justify-between">
                <div class="w-[24%]">
                    <div class="bg-white rounded-sm shadow-xl sm:rounded-lg px-8 py-8">
                        <div class="mb-6">
                            <img :src="farmer.farmer_image" alt="profile" class="w-48 h-48 mx-auto rounded-full object-cover">
                        </div>

                        <div class="text-center mb-4">
                            <h4 class="font-semibold text-xl">{{ farmer.name }}</h4>
                        </div>

                        <hr class="mb-4">

                        <ul class="list-none">
                            <li @click="back" class="inline-flex items-center w-full cursor-pointer hover:bg-gray-100 py-2 px-3"> 
                                <svg class="w-6 h-6 me-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                    <g id="SVGRepo_iconCarrier"> 
                                        <path d="M6 12H18M6 12L11 7M6 12L11 17" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> 
                                    </g>
                                </svg>
                                <h4 class="font-bold">Back to Masterfile</h4>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="w-[74%]">
                    <div class="bg-white rounded-sm shadow-xl sm:rounded-lg px-8 py-8">
                        <FarmerTabs>
                            <template #farmer-profile>
                                <div class="p-3">
                                    <div class="flex flex-wrap justify-between mb-4">
                                        <div class="sm:w-full md:w-[50%]">
                                            <InputLabel for="firstname" value="First name" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.firstname ? farmer.firstname : 'No Firstname' }}</p>
                                        </div>
                                        <div class="sm:w-full md:w-[49%]">
                                            <InputLabel for="lastname" value="Last name" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.lastname ? farmer.lastname : 'No Lastname' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap mb-4 gap-x-3">
                                        <div class="sm:w-full md:w-[32%]">
                                            <InputLabel for="middlename" value="Middle name" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.middlename ? farmer.middlename : 'No Lastname' }}</p>
                                        </div>
                                        <div class="sm:w-full md:w-[17%]">
                                            <InputLabel for="extension" value="Extension" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.suffix ? farmer.suffix : 'No Lastname' }}</p>
                                        </div>
                                        <div class="sm:w-full md:w-[14%]">
                                            <InputLabel for="gender" value="Gender" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.gender ? farmer.gender : 'N/A' }}</p>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-300 mb-4" />

                                    <div class="flex flex-wrap items-center justify-between mb-4">
                                        <div class="w-full">
                                            <h3 class="font-bold text-md">ADDRESS</h3>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-start justify-between mb-4">
                                        <div class="md:w-[32.10%] sm:w-full">
                                            <InputLabel for="house" value="House / Lot / Bldg. No." />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.lot ? farmer.lot : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[32.10%] sm:w-full">
                                            <InputLabel for="street" value="Street / Sitio / Subdv." />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.street ? farmer.street : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[32.10%] sm:w-full">
                                            <InputLabel for="barangay" value="Barangay" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.brgy ? farmer.brgy : '&nbsp;' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-start justify-between">
                                        <div class="md:w-[39.10%] sm:w-full">
                                            <InputLabel for="municipality" value="Municipality / City" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.muni_city ? farmer.muni_city : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[39.10%] sm:w-full">
                                            <InputLabel for="province" value="Province" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.province ? farmer.province : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[18%] sm:w-full">
                                            <InputLabel for="region" value="Region" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.region ? farmer.region : '&nbsp;' }}</p>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-300" />

                                    <div class="flex flex-wrap items-start justify-between mb-4">
                                        <div class="md:w-[24%] sm:w-full">
                                            <InputLabel for="contact" value="Contact No." />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.mobile_no ? farmer.mobile_no : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[24%] sm:w-full">
                                            <InputLabel for="birth" value="Date of Birth" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.date_of_birth ? farmer.date_of_birth : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[24%] sm:w-full">
                                            <InputLabel for="birth-place" value="Place of Birth" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.place_of_birth ? farmer.place_of_birth : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[24%] sm:w-full">
                                            <InputLabel for="religon" value="Religon" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.religion ? farmer.religion : '&nbsp;' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-x-4 items-start justify-center mb-4">
                                        <div class="md:w-[32%] sm:w-full">
                                            <InputLabel for="civil-status" value="Civil Status" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.civil_status ? farmer.civil_status : '&nbsp;' }}</p>
                                            <div class="mt-4" v-if="farmer.civil_status.includes(['Married', 'Windowed'])">
                                                <InputLabel for="spouse-name" value="Name of Spouse" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.spouse ? farmer.spouse : '&nbsp;' }}</p>
                                            </div>
                                        </div>
                                        <div class="md:w-[32%] sm:w-full">
                                            <InputLabel for="mother-name" value="Mothers' Maiden Name" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.mothers_maiden_name ? farmer.mothers_maiden_name : '&nbsp;' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-x-4 items-start justify-center mb-4">
                                        <div class="w-full">
                                            <div class="flex items-center gap-6 mt-3">
                                                <InputLabel for="household-head" value="Household Head?" class="mb-0" />

                                                <div class="flex items-center gap-4">
                                                    <label class="flex items-center space-x-2">
                                                        <TextInput type="radio" :value="1" class="accent-blue-600" :checked="farmer.is_household_head == 1 && farmer.is_household_head != ''" disabled />
                                                        <span class="text-gray-700">Yes</span>
                                                    </label>

                                                    <label class="flex items-center space-x-2">
                                                        <TextInput type="radio" :value="0" class="accent-blue-600" :checked="farmer.is_household_head == 0 && farmer.is_household_head != ''" disabled />
                                                        <span class="text-gray-700">No</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sm:w-full md:w-10/12 lg:w-8/12 xl:w-6/12 2xl:w-6/12 mx-auto mb-4" v-if="farmer.is_household_head == 0 && farmer.is_household_head != ''">
                                        <div class="flex flex-wrap align-start justify-between">
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="household-head" value="Name of Household Head" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.name_if_not_head ? farmer.name_if_not_head : '&nbsp;' }}</p>
                                            </div>
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="relationship" value="Relationship" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.is_not_head_relationship ? farmer.is_not_head_relationship : '&nbsp;' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sm:w-full md:w-full lg:w-12/12 xl:w-11/12 2xl:w-8/12 mx-auto mb-4">
                                        <div class="flex flex-wrap items-start gap-x-4">
                                            <div class="sm:w-full md:w-[40%]">
                                                <InputLabel for="living-household-members" value="No. of living household members" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.no_of_living_members ? farmer.no_of_living_members : '&nbsp;' }}</p>
                                            </div>
                                            <div class="sm:w-full md:w-[18%]">
                                                <InputLabel for="no-of-male" value="No. of Male" :required="true" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.no_of_male > 0 ? farmer.no_of_male : 0 }}</p>
                                            </div>
                                            <div class="sm:w-full md:w-[18%]">
                                                <InputLabel for="no-of-female" value="No. of Female" :required="true" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.no_of_female > 0 ? farmer.no_of_female : 0 }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-300" />

                                    <div class="flex flex-wrap sm:gap-x-0 md:gap-x-2 lg:gap-x-3 xl:gap-x-4 2xl:gap-x-5 items-start justify-center mb-4">
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="education" value="Highest Formal Education" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.highest_formal_education ? farmer.highest_formal_education : '&nbsp;' }}</p>
                                        </div>
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="pwd" value="Person with Disability (PWD)" />
                                            <div class="flex flex-wrap items-center mt-3">
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2">
                                                    <TextInput type="radio" value="1" class="accent-blue-600" :checked="farmer.is_pwd == 1" disabled />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2">
                                                    <TextInput type="radio" value="0" class="accent-blue-600" :checked="farmer.is_pwd == 0" disabled />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="4ps" value="4P's Beneficiary?" />
                                            <div class="flex flex-wrap items-center mt-3">
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2">
                                                    <TextInput type="radio" value="1" class="accent-blue-600" :checked="farmer.is_4ps == 1" disabled />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2">
                                                    <TextInput type="radio" value="0" class="accent-blue-600" :checked="farmer.is_4ps == 0" disabled />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="gov-id" value="With Government ID?" />
                                            <div class="flex flex-wrap items-center mt-3">
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2">
                                                    <TextInput type="radio" value="1" class="accent-blue-600" :checked="farmer.has_gov_id == 1 && farmer.has_gov_id != ''" disabled />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2">
                                                    <TextInput type="radio" value="0" class="accent-blue-600" :checked="farmer.has_gov_id == 0 && farmer.has_gov_id != ''" disabled />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>

                                            <div class="mt-4" v-if="farmer.has_gov_id == 1">
                                                <InputLabel for="specify_id" value="Specify ID number" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.id_no ? farmer.id_no : '&nbsp;' }}</p>
                                            </div>
                                        </div>
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[40%] md:mb-4">
                                            <InputLabel for="gov-id" value="Member of any Farmers Association / Cooperative?" />
                                            <div class="flex flex-wrap items-center mt-3">
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2">
                                                    <TextInput type="radio" value="1" class="accent-blue-600" :checked="farmer.is_farmer_coop_mem == 1 && farmer.is_farmer_coop_mem != ''" disabled />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2">
                                                    <TextInput type="radio" value="0" class="accent-blue-600" :checked="farmer.is_farmer_coop_mem == 0 && farmer.is_farmer_coop_mem != ''" disabled />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>

                                            <div class="mt-4" v-if="farmer.is_farmer_coop_mem == 1">
                                                <InputLabel for="specify_farmer_asso" value="Specify" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.is_farmer_mem ? farmer.is_farmer_mem : '&nbsp;' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-300" />

                                    <div class="sm:w-full md:w-10/12 lg:w-10/12 xl:w-8/12 2xl:w-8/12 mx-auto mb-4">
                                        <div class="flex flex-wrap items-start justify-between">
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="person-emergency" value="Person to notify in case of Emergency" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.contact_emergency ? farmer.contact_emergency : '&nbsp;' }}</p>
                                            </div>
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="contact-emergency-no" value="Contact No." />
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.contact_no ? farmer.contact_no : '&nbsp;' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template #farm-profile>
                                <div class="p-3">
                                    <div class="flex flex-wrap w-full mb-4">
                                        <div class="w-full mb-3">
                                            <h3 class="font-bold text-md">MAIN LIVELIHOOD</h3>
                                        </div>
                                        <div class="w-full mx-auto">
                                            <div class="flex flex-wrap items-center justify-center sm:gap-x-6 md:gap-x-7 lg:gap-x-10 xl:gap-x-15 2xl:gap-x-32">
                                                <div v-for="option in main_livelihood" :key="option.value" class="inline-flex items-center space-x-2" >
                                                    <TextInput type="checkbox" :id="option.value" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 " :checked="farmer.main_livelihood.includes(option.value)" disabled />
                                                    <InputLabel :for="option.value" :value="option.label" class="text-sm text-gray-700" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap w-full lg:justify-start xl:justify-center 2xl:justify-center items-stretch gap-x-3">
                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="farmer.main_livelihood_info['farmer'].length > 0"
                                                :class="{
                                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : farmer.main_livelihood.length >= 1 && farmer.main_livelihood.length <= 2,
                                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : farmer.main_livelihood.length == 3,
                                                    'md:w-[49%] lg:w-[32%] xl:w-[30%] 2xl:w-[24%]' : farmer.main_livelihood.length == 4
                                                }"
                                            >
                                                <div class="p-4 lg:p-6 bg-white">
                                                    <h4 class="text-center font-bold italic text-lg mb-3">For Farmers:</h4>

                                                    <h5 class="font-bold text-md mb-2">Type of Farming Activity</h5>
                                                    <div class="flex flex-wrap">
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['farmer'], 'rice')" value="rice" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="rice" value="Rice" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['farmer'], 'corn')" value="corn" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="corn" value="Corn" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" id="crops" :checked="checkMeta(farmer.main_livelihood_info['farmer'], 'crops')" value="crops" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="crops" value="Other crops" class="text-sm text-gray-700" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="checkMeta(farmer.main_livelihood_info['farmer'], 'crops')">
                                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                                <p class="border rounded block p-2 w-full uppercase">{{ famerSpecify('crops', farmer.main_livelihood_info['farmer']) }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" id="livestock" :checked="checkMeta(farmer.main_livelihood_info['farmer'], 'livestock')" value="livestock" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="crops" value="Livestock" class="text-sm text-gray-700" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="checkMeta(farmer.main_livelihood_info['farmer'], 'livestock')">
                                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                                <p class="border rounded block p-2 w-full uppercase">{{ famerSpecify('livestock', farmer.main_livelihood_info['farmer']) }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="w-full">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" id="livestock" :checked="checkMeta(farmer.main_livelihood_info['farmer'], 'poultry')" value="livestock" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="poultry" value="Poultry" class="text-sm text-gray-700" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="checkMeta(farmer.main_livelihood_info['farmer'], 'poultry')">
                                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                                <p class="border rounded block p-2 w-full uppercase">{{ famerSpecify('poultry', farmer.main_livelihood_info['farmer']) }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="farmer.main_livelihood_info['farm_worker'].length > 0"
                                                :class="{
                                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : farmer.main_livelihood.length >= 1 && farmer.main_livelihood.length <= 2,
                                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : farmer.main_livelihood.length == 3,
                                                    'md:w-[49%] lg:w-[28%] xl:w-[28%] 2xl:w-[22%]' : farmer.main_livelihood.length == 4
                                                }"
                                            >
                                                <div class="p-4 lg:p-6 bg-white">
                                                    <h4 class="text-center font-bold italic text-lg mb-3">For Farmworkers:</h4>

                                                    <h5 class="font-bold text-md mb-2">Kind of Work</h5>

                                                    <div class="flex flex-wrap">
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['farm_worker'], 'land preparation')" value="Land Preparation" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="land-preparation" value="Land Preparation" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['farm_worker'], 'planting / transplanting')" value="Planting / Transplanting" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 " disabled />
                                                                <InputLabel for="planting" value="Planting / Transplanting" class="text-sm text-gray-700 " />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['farm_worker'], 'cultivation')" value="Cultivation" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="cultivation" value="Cultivation" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['farm_worker'], 'harvesting')" value="Harvesting" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="harvesting" value="Harvesting" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['farm_worker'], 'others')" value="Others" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="farmworker-other" value="Others" class="text-sm text-gray-700" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="checkMeta(farmer.main_livelihood_info['farm_worker'], 'others')">
                                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                                <p class="border rounded block p-2 w-full uppercase">{{ otherActivity('Others', farmer.main_livelihood_info['farm_worker']) }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="farmer.main_livelihood_info['fisherfolks'].length > 0"
                                                :class="{
                                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : farmer.main_livelihood.length >= 1 && farmer.main_livelihood.length <= 2,
                                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : farmer.main_livelihood.length == 3,
                                                    'md:w-[49%] lg:w-[36%] xl:w-[36%] 2xl:w-[26%]' : farmer.main_livelihood.length == 4
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
                                                                    <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['fisherfolks'], 'fish capture')" value="Fish Capture" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                    <InputLabel for="fish-capture" value="Fish Capture" class="text-sm text-gray-700" />
                                                                </div>
                                                            </div>
                                                            <div class="md:w-[49%] sm:w-full">
                                                                <div class="inline-flex items-center space-x-2">
                                                                    <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['fisherfolks'], 'Fish Processing')" value="Fish Processing" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                    <InputLabel for="fish-processing" value="Fish Processing" class="text-sm text-gray-700" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-wrap items-center justify-between w-full mb-2">
                                                            <div class="md:w-[49%] sm:w-full">
                                                                <div class="inline-flex items-center space-x-2">
                                                                    <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['fisherfolks'], 'aquaculture')" value="Aquaculture" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                    <InputLabel for="aquaculture" value="Aquaculture" class="text-sm text-gray-700" />
                                                                </div>
                                                            </div>
                                                            <div class="md:w-[49%] sm:w-full">
                                                                <div class="inline-flex items-center space-x-2">
                                                                    <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['fisherfolks'], 'fish vending')" value="Fish Vending" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                    <InputLabel for="fish-vending" value="Fish Vending" class="text-sm text-gray-700" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-wrap items-center justify-between w-full mb-2">
                                                            <div class="md:w-[49%] sm:w-full">
                                                                <div class="inline-flex items-center space-x-2">
                                                                    <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['fisherfolks'], 'gleaning')" value="Gleaning" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                    <InputLabel for="gleaning" value="Gleaning" class="text-sm text-gray-700" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['fisherfolks'], 'others')" value="Others" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="fisherfolk-other" value="Others" class="text-sm text-gray-700" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="checkMeta(farmer.main_livelihood_info['fisherfolks'], 'others')">
                                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                                <p class="border rounded block p-2 w-full uppercase">{{ otherActivity('Others', farmer.main_livelihood_info['fisherfolks']) }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="farmer.main_livelihood_info['agri_youth'].length > 0"
                                                :class="{
                                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : farmer.main_livelihood.length >= 1 && farmer.main_livelihood.length <= 2,
                                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : farmer.main_livelihood.length == 3,
                                                    'md:w-[49%] lg:w-[33%] xl:w-[33%] 2xl:w-[24%]' : farmer.main_livelihood.length == 4
                                                }"
                                            >
                                                <div class="p-4 lg:p-6 bg-white">
                                                    <h4 class="text-center font-bold italic text-lg mb-3">For Agri Youth:</h4>

                                                    <p class="mb-3">For the purposes of trainings, financial assistance, and either programs and catered to the youth with involvement to any agriculture activity.</p>

                                                    <h5 class="font-bold text-md mb-2">Type of Involvement</h5>

                                                    <div class="flex flex-wrap">
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['agri_youth'], 'part of a farming household')" value="Part of a farming household" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="part-of-farming-household" value="Part of a farming household" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['agri_youth'], 'attending / attended formal agri-fishery related course')" value="Attending / attended formal agri-fishery related course" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="attended-formal-agri-fishery" value="Attending / attended formal agri-fishery related course" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['agri_youth'], 'attending / attended non-formal agri-fishery related course')" value="Attending / attended non-formal agri-fishery related course" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="attended-non-formal-agri-fishery" value="Attending / attended non-formal agri-fishery related course" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['agri_youth'], 'participated a any agircultural activity / program')" value="Participated a any agircultural activity / program" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="participated-any-agri-activity" value="Participated a any agircultural activity / program" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="checkMeta(farmer.main_livelihood_info['agri_youth'], 'others')" value="Others" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="youth-others" value="Others" class="text-sm text-gray-700" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="checkMeta(farmer.main_livelihood_info['agri_youth'], 'others')">
                                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                                <p class="border rounded block p-2 w-full uppercase">{{ otherActivity('Others', farmer.main_livelihood_info['agri_youth']) }}</p>
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
                                                <h3 class="font-bold text-md uppercase">Gross Annual Income last year</h3>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap justify-center">
                                            <div class="md:w-6/12 mx-auto sm:w-full">
                                                <div class="flex flex-wrap justify-between">
                                                    <div class="sm:w-full md:w-[49%]">
                                                        <InputLabel for="farming" value="Farming" />
                                                        <p class="border rounded block p-2 w-full uppercase">{{ farmer.farming_gross > 0 ? farmer.farming_gross : 0 }}</p>
                                                    </div>
                                                    <div class="sm:w-full md:w-[49%]">
                                                        <InputLabel for="non-farming" value="Non-farming" />
                                                        <p class="border rounded block p-2 w-full uppercase">{{ farmer.non_farming_gross > 0 ? farmer.non_farming_gross : 0 }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-300" />

                                    <div class="mb-6">
                                        <div class="flex flex-wrap items-center justify-between">
                                            <div class="sm:w-full md:w-6/12 lg:w-6/12 xl:w-6/12 2xl:w-5/12">
                                                <div class="flex flex-wrap items-center">
                                                    <InputLabel for="farm-parcels" class="lg:w-[36%] xl:w-4/12 2xl:w-6/12" value="No. of Farm Parcels" />
                                                    <p class="border rounded block p-2 uppercase lg:w-2/12 xl:w-2/12 2xl:w-2/12">{{ farmer.farm_parcel_no ? farmer.farm_parcel_no : '&nbsp;' }}</p>
                                                </div>
                                            </div>
                                            <div class="sm:w-full md:w-6/12 lg:w-6/12 xl:w-6/12 2xl:w-7/12">
                                                <div class="flex flex-wrap items-center">
                                                    <InputLabel for="farm-parcels" class="lg:w-[36%] xl:w-4/12 2xl:w-6/12 me-4" value="Agrarian Reform Beneficiary (ARB)" />
                                                    <div class="flex flex-wrap items-center lg:w-4/12 xl:w-4/12 2xl:w-5/12 space-x-3">
                                                        <label class="lg:w-[40%] xl:w-[25%] 2xl:w-[20%] flex items-center space-x-2">
                                                            <TextInput type="radio" name="is_arb" :checked="farmer.is_arb == 1" value="1" class="accent-blue-600" disabled />
                                                            <span class="text-gray-700">Yes</span>
                                                        </label>

                                                        <label class="lg:w-[40%] xl:w-[25%] 2xl:w-[20%] flex items-center m-y-0 space-x-2">
                                                            <TextInput type="radio" name="is_arb" :checked="farmer.is_arb == 0" value="0" class="accent-blue-600" disabled />
                                                            <span class="text-gray-700">No</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <div class="overflow-auto w-full">
                                            <template v-for="(item, index) in farmer.farm_parcel" :key="index">
                                                <div :class="farmer.farm_parcel.length > 0 ? 'mb-4' : 'mb-0'">
                                                    <div class="p-6 lg:p-8 bg-white border shadow-3xl rounded-lg border-gray-300">
                                                        <div class="flex flex-wrap justify-between items-center mb-4">
                                                            <div class="w-3/12 md:order-2 lg:order-1 xl:order-1 2xl:order-1">
                                                                <h4 class="text-MD font-semibold">FARM PARCEL NO: {{ index+1 }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-wrap justify-between mb-4">
                                                            <div class="w-full">
                                                                <div class="flex flex-wrap mb-4">
                                                                    <div class="w-6/12">
                                                                        <InputLabel for="rotation" value="Name of Farmer(s) in Rotation"/>
                                                                        <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">{{ item.farmer_in_rotation_name ? item.farmer_in_rotation_name : '&nbsp;' }}</p>
                                                                    </div>
                                                                </div>
                                                                <div class="flex flex-wrap justify-between gap-x-1">
                                                                    <div class="w-[40%]">
                                                                        <InputLabel for="farm_municipal" value="Municipality" />
                                                                        <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">{{ item.city ? item.city : '&nbsp;' }}</p>
                                                                    </div>
                                                                    <div class="w-[40%]">
                                                                        <InputLabel for="farm_brgy" value="Barangay" />
                                                                        <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">{{ item.brgy ? item.brgy : '&nbsp;' }}</p>
                                                                    </div>
                                                                    <div class="w-2/12">
                                                                        <InputLabel for="total_farm_area" value="Total Farm Area" />
                                                                            <div class="mt-1 flex rounded-md shadow-sm">
                                                                                <p class="border block p-2 w-full uppercase flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">{{ item.total_farm_area ? item.total_farm_area : '&nbsp;' }}</p>
                                                                            <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"> ha </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-wrap justify-center items-start gap-x-5 mb-5">
                                                            <div class="w-5/12">
                                                                <InputLabel for="Ansentral" value="Within Ancentral Domain" />
                                                                <div class="flex flex-wrap items-center mt-3">
                                                                    <label class="sm:w-[49%] md:w-[49%] lg:w-[40%] 2xl:w-[28%] flex items-center space-x-2">
                                                                        <TextInput type="radio" value="1" class="accent-blue-600" :checked="item.is_whithin_ancentral_domain == 1 && item.is_whithin_ancentral_domain != ''" disabled />
                                                                        <span class="text-gray-700">Yes</span>
                                                                    </label>

                                                                    <label class="sm:w-[49%] md:w-[49%] lg:w-[40%] 2xl:w-[28%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                                        <TextInput type="radio" value="0" class="accent-blue-600" :checked="item.is_whithin_ancentral_domain == 0 && item.is_whithin_ancentral_domain != ''" disabled />
                                                                        <span class="text-gray-700">No</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="w-5/12">
                                                                <InputLabel for="Agrarian" value="Agrarian Reform Beneficiary" />
                                                                <div class="flex flex-wrap items-center mt-3">
                                                                    <label class="sm:w-[49%] md:w-[49%] lg:w-[40%] 2xl:w-[28%] flex items-center space-x-2">
                                                                        <TextInput type="radio" value="1" class="accent-blue-600" :checked="item.is_agrarian_reform_beneficiary == 1" disabled />
                                                                        <span class="text-gray-700">Yes</span>
                                                                    </label>

                                                                    <label class="sm:w-[49%] md:w-[49%] lg:w-[40%] 2xl:w-[28%] flex items-center m-y-0 space-x-2">
                                                                        <TextInput type="radio" value="0" class="accent-blue-600" :checked="item.is_agrarian_reform_beneficiary == 0" disabled />
                                                                        <span class="text-gray-700">No</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-wrap justify-center items-start gap-x-5 mb-5">
                                                            <div class="w-3/12">
                                                                <InputLabel for="ownership_doc" value="Ownership Document No" />
                                                                <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">{{ item.ownership_document_no ? item.ownership_document_no : '&nbsp;' }}</p>
                                                            </div>
                                                            <div class="w-3/12">
                                                                <InputLabel for="ownership_type" value="Type of Ownership" />
                                                                <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">{{ item.ownership_type ? item.ownership_type : '&nbsp;' }}</p>
                                                            </div>
                                                            <div class="w-4/12" v-if="item.ownership_type == 'Tenant' || item.ownership_type == 'Lesse'">
                                                                <InputLabel for="Name of Land Owner" value="Name of Land Owner" />
                                                                <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">{{ item.landowner_name ? item.landowner_name : '&nbsp;' }}</p>
                                                            </div>
                                                            <div class="w-4/12"v-if="item.ownership_type == 'Others'">
                                                                <InputLabel for="specify-other" value="Specify" />
                                                                <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">{{ item.is_other ? item.is_other : '&nbsp;' }}</p>
                                                            </div>
                                                        </div>
                                                        <table class="border-collapse border-2 border-gray-400 w-full">
                                                            <thead>
                                                                <tr>
                                                                    <th class="p-3 border border-gray-400 w-[25%]">
                                                                        <strong>CROP / COMMODITY</strong>
                                                                        <p class="m-0">
                                                                            <small class="italic">( Rice / Corn / HVC / Livestock / Poultry /agri-fishery )</small>
                                                                        </p>

                                                                        <strong>For Livestock & Poultry</strong>
                                                                        <p class="m-0">
                                                                            <small>( Specify type of animal) </small>
                                                                        </p>
                                                                    </th>
                                                                    <th class="p-3 border border-gray-400 w-[11%]">SIZE (ha)</th>
                                                                    <th class="p-3 border border-gray-400 w-[11%]">
                                                                        <strong>NO. OF HEAD</strong>
                                                                        <p class="m-0">
                                                                            <small class="italic">( For livestock and poultry)</small>
                                                                        </p>
                                                                    </th>
                                                                    <th class="p-3 border border-gray-400 w-[24%]">FARM TYPE</th>
                                                                    <th class="p-3 border border-gray-400 w-[18%]">ORGANIC PRACTITIONER</th>
                                                                    <th class="p-3 border border-gray-400 w-[18%]">REMARKS</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-for="(v, i) in item.farm_parcel_informations" :key="i" style="vertical-align: top;">
                                                                    <td class="p-3 border border-gray-400">
                                                                        <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">{{ !isNaN(parseFloat(v.farming_type)) && isFinite(v.farming_type) ? farmCommodity(v.farming_type) : v.farming_type }}</p>
                                                                    </td>
                                                                    <td class="p-3 border border-gray-400">
                                                                        <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">{{ v.size }}</p>
                                                                    </td>
                                                                    <td class="p-3 border border-gray-400">
                                                                        <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">{{ v.head_no > 0 ? v.head_no : 0 }}</p>
                                                                    </td>
                                                                    <td class="p-3 border border-gray-400">
                                                                        <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">{{ v.farm_type ? formatFarmType(v.farm_type) : '&nbsp;' }}</p>
                                                                    </td>
                                                                    <td class="p-3 border border-gray-400">
                                                                        <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">
                                                                        {{ v.is_organic_practitioner == 1 ? 'Yes' : 'No' }}
                                                                        </p>
                                                                    </td>
                                                                    <td class="p-3 border border-gray-400">
                                                                        <p class="border rounded block p-2 uppercase mt-1 w-full uppercase">{{ v.remarks ? v.remarks : '&nbsp;' }}</p>
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
                            </template>
                        </FarmerTabs>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
