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
    import DropzoneInput from '@/Components/DropzoneProfileInput.vue'

    import Select2 from 'vue3-select2-component';

    import { Link, router, usePage, useForm } from '@inertiajs/vue3';
    import axios from 'axios';
    import daterangepicker from 'daterangepicker';

    import Swal from 'sweetalert2';
    import Inputmask from 'inputmask';
    
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
    const submitted = ref(false);
    let is_not_head = ref('');
    let gov_id = ref('');
    let is_mem = ref('');

    const pages = ref([ 10, 25, 50, 100, 200, 'All']);

    const form = useForm({
        image: [],
        ref_no: '',
        lastname: '', 
        firstname: '', 
        middlename: '', 
        suffix: '', 
        gender: '',
        lot: '',
        street: '',
        brgy: '',
        muni_city: '',
        province: '',
        region: '',
        contact: '',
        birth: '', 
        birthplace: '', 
        religion: '',
        civil_status: '', 
        spouse: '',
        mother_maiden_name: '',
        is_household_head: '',
        household_head_name: '',
        head_relationship: '',
        members_no: '',
        no_of_male: '',
        no_of_female: '',
        education: '',
        is_pwd: '',
        is_4ps: '',
        has_gov_id: '',
        gov_id_no: '',
        is_farmer_member: '',
        asocc_name: '',
        person_emergency: '',
        contact_emergency: '',
        main_livelihood: [],
        farmer: [],
        crops: [],
        livestock: [],
        poultry: [],
        farm_worker: [],
        farm_worker_others: '',
        fisherfolks: [],
        fisherfolks_others: '',
        agri_youth: [],
        agri_others: '',
        farming_gross_income: 0,
        non_farming_gross_income: 0,
        farm_parcel_no: 1,
        is_arb: '',
        farm_parcel: [
            {
                municipality: null,
                brgy: null,
                total_farm_area: null,
                owner_doc_no: null,
                ownership_type: null,
                is_whithin_ancentral_domain: null,
                is_agrarian_reform_beneficiary: null,
                land_owner_name: null,
                is_other: null,
                farm_parcel_info: [
                    {
                        commodity: null,
                        size: 0,
                        head_no: 0,
                        farm_type: null,
                        is_organic_practitioner: null,
                        remarks: null
                    }
                ]
            }
        ],
        user_id: 0
    });

    watch(() => form.is_household_head, (val) => {
        is_not_head.value = val == 0 ? true : false;
        
        if(is_not_head) {
            v$.value.household_head_name.$reset()
            v$.value.head_relationship.$reset()    
        }
        
        form.household_head_name = ''
        form.head_relationship = ''
    })
    
    watch(() => form.has_gov_id, (val) => {
        gov_id.value = val == 1 ? true : false;
        
        v$.value.gov_id_no.$reset()
        form.gov_id_no = ''
    })

    const phoneFormat = helpers.regex(/^\(09\) \d{4}-\d{5}$/)
    const refNoFormat = helpers.regex(/^\d{2}-\d{2}-\d{2}-\d{3}-\d{3}$/)

    const personalInfo = {
        image: { required },
        ref_no: { required, exactLength: minLength(12), refNoFormat },
        lastname: { required },
        firstname: { required },
        middlename: {},
        suffix: {},
        gender: { required },
        lot: {},
        street: {},
        brgy: { required },
        muni_city: { required },
        province: { required },
        region: { required },
        contact: { required, exactLength: minLength(11), phoneFormat },
        birth: { required },
        birthplace: { required },
        religion: {},
        civil_status: { required },
        spouse: {
            required: requiredIf(() =>
                ['Married', 'Widowed'].includes(form.civil_status)
            )
        },
        mother_maiden_name: { required },
        is_household_head: { required },
        household_head_name: {
            required: requiredIf(is_not_head)
        },
        head_relationship: {
            required: requiredIf(is_not_head)
        },
        members_no: { required },
        no_of_male: { required },
        no_of_female: { required },
        education: { required },
        is_pwd: { required },
        is_4ps: { required },
        has_gov_id: { required },
        gov_id_no: {
            required: requiredIf(gov_id)
        },
        is_farmer_member: { required },
        asocc_name: {
            required: requiredIf(is_mem)
        },
        person_emergency: { required },
        contact_emergency: { required, exactLength: minLength(11), phoneFormat },
    }

    const ownerType = ref(false);
    const ownerOthers = ref(false);

    const handleOwnership = (index, event) => {
        const selectedValue = event.id;

        console.log(event);

        if (selectedValue == 'Tenant' || selectedValue == 'Lesse') {
            ownerType.value = true;
        } else {
            ownerType.value = false;
        }

        if (selectedValue == 'Others') {
            ownerOthers.value = true;
        } else {
            ownerOthers.value = false;
        }
    }

    watch(() => form.farmer, (newVal) => {
        console.log(newVal);
    })

    const farmInfo = {
        main_livelihood: { required, minLength: minLength(1) },
        farmer: { required, minLength: minLength(1) },
        crops: {
            required: requiredIf(() => form.farmer.includes('crops')),
            minLength: minLength(1)
        },
        livestock: {
            required: requiredIf(() => form.farmer.includes('livestock')),
            minLength: minLength(1)
        },
        poultry: {
            required: requiredIf(() => form.farmer.includes('poultry')),
            minLength: minLength(1)
        },
        farm_worker: { required, minLength: minLength(1) },
        farm_worker_others: {
            required: requiredIf(() => form.farm_worker.includes('Others'))
        },
        fisherfolks: { required, minLength: minLength(1) },
        fisherfolks_others: {
            required: requiredIf(() => form.fisherfolks.includes('Others'))
        },
        agri_youth: { required, minLength: minLength(1) },
        agri_youth_others: {
            required: requiredIf(() =>  form.agri_youth.includes('Others'))
        },
        farming_gross_income: { required },
        non_farming_gross_income: { required },
        farm_parcel_no: { required, min: 1 },
        is_arb: { required },
        farm_parcel: {
            $each: helpers.forEach({
                municipality: { 'required' : required },
                brgy: { required },
                total_farm_area: { required },
                owner_doc_no: { required },
                is_whithin_ancentral_domain: { required },
                is_agrarian_reform_beneficiary: { required },
                ownership_type: { required },
                land_owner_name: {},
                is_other: {
                    required: requiredIf(ownerOthers)
                },
                farm_parcel_info: {
                    $each: helpers.forEach({
                        commodity: { required },
                        size: { required },
                        head_no: { required },
                        farm_type: { required },
                        is_organic_practitioner: { required },
                        remarks: {}
                    })
                }
            })
        }
    }

    const dateFormat = (date) => {
        return moment(date).format('MMM. DD, YYYY hh:mm A');
    }

    const step = ref(1);

    const rules = computed(() => {
        if (step.value == 0) return personalInfo
        if (step.value == 1) return farmInfo
        return {}
    });

    const v$ = useVuelidate(rules, form, {
        $autoDirty: false
    })

    const stepLabels = ['PERSONAL INFORMATION', 'FARM DETAILS', 'VIEW DETAILS'];

    const setStep = (index) => {
        step.value = index
    }

    const goToNext = (event) => {
        v$.value.$touch();
        if (step.value < stepLabels.length - 1) {
            if (!v$.value.$invalid) {
                step.value++
            }

            // localStorage.setItem(STORAGE_KEY, JSON.stringify(form.data()))
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

        v$.value.spouse.$reset();
        form.spouse = ''
    }

    const education = ref([
        { id: 'None', text: 'None' },
        { id: 'Elementary', text: 'Elementary' },
        { id: 'High School', text: 'High School' },
        { id: 'Vocational', text: 'Vocational' },
        { id: 'College', text: 'College' },
        { id: 'Post Graduate', text: 'Post Graduate' },
    ])

    let ref_no = ref(null);
    let contact = ref(null);
    let emergency = ref(null);

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
        
        ref_no = new Inputmask({
            mask: "99-99-99-999-999",
	        alias: 'reference_no'
        })
        ref_no.mask($("#ref_no"));

        contact = new Inputmask({
            mask: "(0\\9) 9999-99999",
	        alias: 'phonenumber'
        })
        contact.mask($("#contact"));

        emergency = new Inputmask({
            mask: "(0\\9) 9999-99999",
	        alias: 'phonenumber'
        })
        emergency.mask($("#contact-emergency"));

        datepicker();
    });

    const datepicker = () => {
        $('#birth').daterangepicker({
            opens: 'left',
            locale: {
                format: 'YYYY-MM-DD',
            },
            singleDatePicker: true,
            showDropdowns: true,
            autoUpdateInput: false,
        }).on('apply.daterangepicker', function(ev, picker){
            $(this).val(picker.startDate.format('YYYY-MM-DD'))

            form.birth = moment(picker.startDate.format('YYYY-MM-DD')).format('YYYY-MM-DD');
        });
    }

    watch(() => step.value, (val) => {
        if (val == 0) {
            console.log(typeof $('#ref_no'))
            if (typeof $("#ref_no") !== 'undefined') {
                setTimeout(() => {
                    ref_no.mask($("#ref_no"));
                    contact.mask($("#contact"));
                    emergency.mask($("#contact-emergency"));
                    datepicker();
                }, 250);

            }
        }
    })

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
            commodity: null,
            size: 0,
            head_no: 0,
            farm_type: null,
            is_organic_practitioner: null,
            remarks: null
        });
    }

    const removeParcelInfo = (index, farmIndex) => {
        form.farm_parcel[farmIndex].farm_parcel_info.splice(index, 1);
    }

    const addFarmParcel = () => {
        form.farm_parcel.push({
            municipality: null,
            brgy: null,
            total_farm_area: null,
            owner_doc_no: null,
            ownership_type: null,
            is_whithin_ancentral_domain: null,
            is_agrarian_reform_beneficiary: null,
            land_owner_name: null,
            is_other: null,
            farm_parcel_info: [
                {
                    commodity: null,
                    size: 0,
                    head_no: 0,
                    farm_type: null,
                    is_organic_practitioner: null,
                    remarks: null
                }
            ]
        })
    }

    const removeFarmParcel = (index) => {
        form.farm_parcel.splice(index, 1);
    }

    function handleImageSelected(file) {
        form.image = file // attach file to form
    }

    const { hasError, inputBorderClass, getFieldState } = useValidationHelpers(v$, form, { autoTouch: true })

    const submitForm = () => {
        const { id } = props.auth.user;

        form.user_id = id;

        form.post(route('farmers.store'), {
            errorBag: 'submitForm',
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
            }
        })
    }

    const data = ref([
        { id: 'farmer', text: 'Farmer' },
        { id: 'farm_worker', text: 'Farm Worker / Laborer' },
        { id: 'fisherfolks', text: 'Fisherfolk' },
        { id: 'agri_youth', text: 'Agri Youth' },
        { id: 'farmer1', text: 'Farmer' },
        { id: 'farm_worker1', text: 'Farm Worker / Laborer' },
        { id: 'fisherfolks1', text: 'Fisherfolk' },
        { id: 'agri_youth1', text: 'Agri Youth' },
        { id: 'farmer2', text: 'Farmer' },
        { id: 'farm_worker2', text: 'Farm Worker / Laborer' },
        { id: 'fisherfolks2', text: 'Fisherfolk' },
        { id: 'agri_youth2', text: 'Agri Youth' },
    ]);

    const handleCrops = (event) => {
        const selectedValue = event.id;

        if (form.crops.includes(selectedValue)) {
            const index = form.crops.indexOf(selectedValue);
            form.crops.splice(index, 1);
        } else {
            form.crops.push(selectedValue);
        }
    }
    
    const handleLivestock = (event) => {
        const selectedValue = event.id;

        if (form.livestock.includes(selectedValue)) {
            const index = form.livestock.indexOf(selectedValue);
            form.livestock.splice(index, 1);
        } else {
            form.livestock.push(selectedValue);
        }
    }
    
    const handlePoultry = (event) => {
        const selectedValue = event.id;

        if (form.poultry.includes(selectedValue)) {
            const index = form.poultry.indexOf(selectedValue);
            form.poultry.splice(index, 1);
        } else {
            form.poultry.push(selectedValue);
        }
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
                        <form @submit.prevent>
                            <div class="sm:w-full md:w-12/12 lg:w-[95%] xl:w-[90%] 2xl:w-[85%] mx-auto px-2">
                                <div v-if="step === 0">
                                
                                    <div class="flex flex-wrap">
                                        <div class="w-6/12">
                                            <div class="flex flex-wrap items-center gap-6 mt-3">
                                                <InputLabel for="ref_no" value="Reference / Control No." :required="true" class="sm:w-full md:w-[18%] lg:w-[38%] 2xl:w-[26%] mb-0" />
                                                <TextInput type="text" class="mt-1 block w-full uppercase sm:w-full md:w-[18%]  md:w-[16%] lg:w-[38%] 2xl:w-[40%]" id="ref_no" v-model="form.ref_no" autocomplete="off" 
                                                    @blur="v$.ref_no.$touch()"
                                                    :class="inputBorderClass('ref_no')"
                                                />
                                            </div>
                                            <p v-if="hasError('ref_no')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.ref_no.required?.$invalid">Reference no is required.</span>
                                                <span class="text-red-500 text-sm" v-if="v$.ref_no.refNoFormat?.$invalid">Invalid Reference Format</span>
                                            </p>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-500" />

                                    <div class="flex flex-wrap items-center">
                                        <div class="sm:w-full md:w-[18%]  md:w-[16%] lg:w-[18%] 2xl:w-[18%] mx-auto">
                                            <InputLabel for="profile" value="Farmer Image" :required="true" />
                                            <DropzoneInput
                                                label="Profile Photo"
                                                upload-url="/"
                                                :current-image-url="'/storage/images/no-user-image.png'"
                                                @fileSelected="handleImageSelected"
                                            />
                                            <span class="text-red-500 text-sm" v-if="v$.image.$error">Image is required.</span>
                                        </div>
                                        <div class="sm:w-full md:w-[76%] lg:w-[76%] xl:w-[76%] 2xl:w-[79%]">
                                            <div class="flex flex-wrap justify-between mb-4">
                                                <div class="sm:w-full md:w-[49%]">
                                                    <InputLabel for="firstname" value="First name" :required="true" />
                                                    <TextInput type="text" name="firstname" v-model="form.firstname" class="mt-1 block w-full uppercase" autocomplete="off"
                                                        @blur="v$.firstname.$touch()"
                                                        :class="inputBorderClass('firstname')"
                                                    />
                                                    <p v-if="hasError('firstname')" class="text-red-500 text-sm">
                                                        <span class="text-red-500 text-sm" v-if="v$.firstname.required?.$invalid">First name is required.</span>
                                                    </p>
                                                </div>
                                                <div class="sm:w-full md:w-[49%]">
                                                    <InputLabel for="lastname" value="Last name" :required="true" />
                                                    <TextInput type="text" name="lastname" v-model="form.lastname" class="mt-1 block w-full uppercase" autocomplete="off"
                                                        @blur="v$.lastname.$touch()"
                                                        :class="inputBorderClass('lastname')"
                                                    />
                                                    <p v-if="hasError('lastname')" class="text-red-500 text-sm">
                                                        <span class="text-red-500 text-sm" v-if="v$.firstname.required?.$invalid">Last name is required.</span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap justify-between">
                                                <div class="sm:w-full md:w-[49%]">
                                                    <InputLabel for="middlename" value="Middle name" />
                                                    <TextInput type="text" name="middlename" v-model="form.middlename" class="mt-1 block w-full uppercase" autocomplete="off" />
                                                </div>
                                                <div class="sm:w-full md:w-2/12">
                                                    <InputLabel for="extension" value="Extension" />
                                                    <TextInput type="text" name="suffix" v-model="form.suffix" class="mt-1 block w-full uppercase" autocomplete="off" />
                                                </div>
                                                <div class="sm:w-full md:w-[30%] md:p-x-2">
                                                    <InputLabel for="gender" value="Gender" :required="true" />
                                                    <div class="flex flex-wrap items-center mt-3">
                                                        <label class="sm:w-[49%] md:w-[49%] lg:w-[40%] 2xl:w-[28%] flex items-center space-x-2 cursor-pointer">
                                                            <input type="radio" name="gender" v-model="form.gender" value="male" class="accent-blue-600" />
                                                            <span class="text-gray-700">Male</span>
                                                        </label>

                                                        <label class="sm:w-[49%] md:w-[49%] lg:w-[40%] 2xl:w-[28%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                            <input type="radio" name="gender" v-model="form.gender" value="female" class="accent-blue-600" />
                                                            <span class="text-gray-700">Female</span>
                                                        </label>
                                                    </div>
                                                    <span class="text-red-500 text-sm" v-if="v$.gender.$error">Gender is required.</span>
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

                                    <div class="flex flex-wrap items-start justify-between mb-4">
                                        <div class="md:w-[32.10%] sm:w-full">
                                            <InputLabel for="house" value="House / Lot / Bldg. No." />
                                            <TextInput type="text" v-model="form.lot" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                        <div class="md:w-[32.10%] sm:w-full">
                                            <InputLabel for="street" value="Street / Sitio / Subdv." />
                                            <TextInput type="text" v-model="form.street" class="mt-1 block w-full uppercase" autocomplete="off" />
                                        </div>
                                        <div class="md:w-[32.10%] sm:w-full">
                                            <InputLabel for="barangay" value="Barangay" :required="true" />
                                            <TextInput type="text" v-model="form.brgy" class="mt-1 block w-full uppercase" autocomplete="off"
                                                @blur="v$.brgy.$touch()"
                                                :class="inputBorderClass('brgy')"
                                            />
                                            <p v-if="hasError('brgy')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.brgy.required?.$invalid">Barangay is required is required.</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-start justify-between">
                                        <div class="md:w-[39.10%] sm:w-full">
                                            <InputLabel for="municipality" value="Municipality / City" :required="true" />
                                            <TextInput type="text" v-model="form.muni_city" class="mt-1 block w-full uppercase" autocomplete="off" 
                                                @blur="v$.muni_city.$touch()"
                                                :class="inputBorderClass('muni_city')"
                                            />
                                            <p v-if="hasError('muni_city')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.muni_city.required?.$invalid">Municipality / City is required.</span>
                                            </p>
                                        </div>
                                        <div class="md:w-[39.10%] sm:w-full">
                                            <InputLabel for="province" value="Province" :required="true" />
                                            <TextInput type="text" v-model="form.province" class="mt-1 block w-full uppercase" autocomplete="off" 
                                                @blur="v$.province.$touch()"
                                                :class="inputBorderClass('province')"
                                            />
                                            <p v-if="hasError('province')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.province.required?.$invalid">Province is required.</span>
                                            </p>
                                        </div>
                                        <div class="md:w-[18%] sm:w-full">
                                            <InputLabel for="region" value="Region" :required="true" />
                                            <TextInput type="text" v-model="form.region" class="mt-1 block w-full uppercase" autocomplete="off" 
                                                @blur="v$.region.$touch()"
                                                :class="inputBorderClass('region')"
                                            />
                                            <p v-if="hasError('region')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.region.required?.$invalid">Region is required.</span>
                                            </p>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-500" />

                                    <div class="flex flex-wrap items-start justify-between mb-4">
                                        <div class="md:w-[24%] sm:w-full">
                                            <InputLabel for="contact" value="Contact No." :required="true" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" id="contact" v-model="form.contact" autocomplete="off" 
                                                @blur="v$.contact.$touch()"
                                                :class="inputBorderClass('contact')"
                                            />
                                            <p v-if="hasError('contact')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.contact.required?.$invalid">Contact No. is required.</span>
                                                <span class="text-red-500 text-sm" v-if="v$.contact.phoneFormat?.$invalid">Invalid phone format. Use (09) 1234-56789</span>
                                            </p>
                                        </div>
                                        <div class="md:w-[24%] sm:w-full">
                                            <InputLabel for="birth" value="Date of Birth" :required="true" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" id="birth" v-model="form.birth" autocomplete="off" 
                                                @blur="v$.birth.$touch()"
                                                :class="inputBorderClass('birth')"
                                            />
                                            <p v-if="hasError('birth')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.birth.required?.$invalid">Date of Birth is required.</span>
                                            </p>
                                        </div>
                                        <div class="md:w-[24%] sm:w-full">
                                            <InputLabel for="birth-place" value="Place of Birth" :required="true" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.birthplace" autocomplete="off"
                                                @blur="v$.birthplace.$touch()"
                                                :class="inputBorderClass('birthplace')"
                                            />
                                            <p v-if="hasError('birthplace')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.birthplace.required?.$invalid">Place of Birth is required.</span>
                                            </p>
                                        </div>
                                        <div class="md:w-[24%] sm:w-full">
                                            <InputLabel for="religon" value="Religon" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.religion" autocomplete="off" />
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-x-4 items-start justify-center mb-4">
                                        <div class="md:w-[32%] sm:w-full">
                                            <InputLabel for="civil-status" value="Civil Status" :required="true" />
                                            <div class="rounded-md block w-full mt-1">
                                                <Select2 class="h-10 uppercase" v-model="form.civil_status" :options="civil_status" :settings="{ placeholder: 'Select An Option', width: '100%' }" @select="civilType" />
                                                <span class="text-red-500 text-sm" v-if="v$.civil_status.$error">Civil Status is required.</span>
                                            </div>
                                            <div class="mt-4" v-if="isMarried">
                                                <InputLabel for="spouse-name" value="Name of Spouse" :required="true" />
                                                <TextInput type="text" v-model="form.spouse" class="mt-1 block w-full uppercase" autocomplete="off" 
                                                    @blur="v$.spouse.$touch()"
                                                    :class="inputBorderClass('spouse')"
                                                />
                                                <p v-if="hasError('spouse')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.spouse.required?.$invalid">Name of Spouse is required.</span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="md:w-[32%] sm:w-full">
                                            <InputLabel for="mother-name" value="Mothers' Maiden Name" :required="true" />
                                            <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.mother_maiden_name" autocomplete="off" 
                                                @blur="v$.mother_maiden_name.$touch()"
                                                :class="inputBorderClass('mother_maiden_name')"
                                            />
                                            <p v-if="hasError('mother_maiden_name')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.mother_maiden_name.required?.$invalid">Mothers' Maiden Name is required.</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-x-4 items-start justify-center mb-4">
                                        <div class="w-full">
                                            <div class="flex items-center gap-6 mt-3">
                                                <InputLabel for="household-head" value="Household Head?" :required="true" class="mb-0" />

                                                <div class="flex items-center gap-4">
                                                    <label class="flex items-center space-x-2 cursor-pointer">
                                                        <TextInput type="radio" :value="1" class="accent-blue-600" name="is_household_head" v-model="form.is_household_head" />
                                                        <span class="text-gray-700">Yes</span>
                                                    </label>

                                                    <label class="flex items-center space-x-2 cursor-pointer">
                                                        <TextInput type="radio" :value="0" class="accent-blue-600" name="is_household_head" v-model="form.is_household_head" />
                                                        <span class="text-gray-700">No</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <p v-if="hasError('is_household_head')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.is_household_head.required?.$invalid">Household head is required.</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="sm:w-full md:w-10/12 lg:w-8/12 xl:w-6/12 2xl:w-6/12 mx-auto mb-4" v-if="form.is_household_head == 0 && form.is_household_head != ''">
                                        <div class="flex flex-wrap align-start justify-between">
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="household-head" value="Name of Household Head" :required="true" />
                                                <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.household_head_name"
                                                    @blur="v$.household_head_name.$touch()"
                                                    :class="inputBorderClass('household_head_name')"
                                                />
                                                <p v-if="hasError('household_head_name')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.household_head_name.required?.$invalid">Name of Household head is required.</span>
                                                </p>
                                            </div>
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="relationship" value="Relationship" :required="true" />
                                                <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.head_relationship" 
                                                    @blur="v$.head_relationship.$touch()"
                                                    :class="inputBorderClass('head_relationship')"
                                                />
                                                <p v-if="hasError('head_relationship')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.head_relationship.required?.$invalid">Relationship is required.</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sm:w-full md:w-full lg:w-12/12 xl:w-11/12 2xl:w-8/12 mx-auto mb-4">
                                        <div class="flex flex-wrap items-start justify-between">
                                            <div class="sm:w-full md:w-[32%]">
                                                <InputLabel for="living-household-members" value="No. of living household members" :required="true" />
                                                <TextInput type="number" v-model="form.members_no" min="0" class="mt-1 block w-full uppercase" autocomplete="off"
                                                    @blur="v$.members_no.$touch()"
                                                    :class="inputBorderClass('members_no')"
                                                />
                                                <p v-if="hasError('members_no')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.members_no.required?.$invalid">No. of members is required.</span>
                                                </p>
                                            </div>
                                            <div class="sm:w-full md:w-[32%]">
                                                <InputLabel for="no-of-male" value="No. of Male" :required="true" />
                                                <TextInput type="number" v-model="form.no_of_male" min="0" class="mt-1 block w-full uppercase" autocomplete="off"
                                                    @blur="v$.no_of_male.$touch()"
                                                    :class="inputBorderClass('no_of_male')"
                                                />
                                                <p v-if="hasError('no_of_male')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.no_of_male.required?.$invalid">No. of Male is required.</span>
                                                </p>
                                            </div>
                                            <div class="sm:w-full md:w-[32%]">
                                                <InputLabel for="no-of-female" value="No. of Female" :required="true" />
                                                <TextInput type="number" v-model="form.no_of_female" min="0" class="mt-1 block w-full uppercase" autocomplete="off"
                                                    @blur="v$.no_of_female.$touch()"
                                                    :class="inputBorderClass('no_of_female')"
                                                />
                                                <p v-if="hasError('no_of_female')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.no_of_female.required?.$invalid">No. of Female is required.</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-500" />

                                    <div class="flex flex-wrap sm:gap-x-0 md:gap-x-2 lg:gap-x-3 xl:gap-x-4 2xl:gap-x-5 items-start justify-center mb-4">
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="education" value="Highest Formal Education" :required="true" />
                                            <div class="rounded-md block w-full mt-1">
                                                <Select2 class="h-10 uppercase" v-model="form.education" :options="education" :settings="{ placeholder: 'Select An Option', width: '100%' }" />
                                                <p v-if="hasError('education')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.education.required?.$invalid">Education is required.</span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="pwd" value="Person with Disability (PWD)" :required="true" />
                                            <div class="flex flex-wrap items-center mt-3">
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="1" name="is_pwd" v-model="form.is_pwd" class="accent-blue-600" />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="0" name="is_pwd" v-model="form.is_pwd" class="accent-blue-600" />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>
                                            <p v-if="hasError('is_pwd')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.is_pwd.required?.$invalid">PWD is required.</span>
                                            </p>
                                        </div>
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="4ps" value="4P's Beneficiary?" :required="true" />
                                            <div class="flex flex-wrap items-center mt-3">
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="1" name="is_4ps" v-model="form.is_4ps" class="accent-blue-600" />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="0" name="is_4ps" v-model="form.is_4ps" class="accent-blue-600" />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>
                                            <p v-if="hasError('is_4ps')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.is_4ps.required?.$invalid">4P's Beneficiary is required.</span>
                                            </p>
                                        </div>
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="gov-id" value="With Government ID?" :required="true" />
                                            <div class="flex flex-wrap items-center mt-3">
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="1" class="accent-blue-600" name="has_gov_id" v-model="form.has_gov_id" />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="0" class="accent-blue-600" name="has_gov_id" v-model="form.has_gov_id" />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>
                                            <p v-if="hasError('has_gov_id')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.has_gov_id.required?.$invalid">Government ID is required.</span>
                                            </p>

                                            <div class="mt-4" v-if="form.has_gov_id == 1">
                                                <InputLabel for="specify_id" value="Specify ID number" :required="true" />
                                                <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.gov_id_no" autocomplete="off" 
                                                    @blur="v$.gov_id_no.$touch()"
                                                    :class="inputBorderClass('gov_id_no')"
                                                />
                                                <p v-if="hasError('gov_id_no')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.gov_id_no.required?.$invalid">Government ID is required.</span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="gov-id" value="Member of any Farmers Association / Cooperative?" :required="true" />
                                            <div class="flex flex-wrap items-center mt-3">
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="1" class="accent-blue-600" name="is_farmer_member" v-model="form.is_farmer_member" />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="0" class="accent-blue-600"  name="is_farmer_member" v-model="form.is_farmer_member" />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>
                                            <p v-if="hasError('is_farmer_member')" class="text-red-500 text-sm">
                                                <span class="text-red-500 text-sm" v-if="v$.is_farmer_member.required?.$invalid">Farmers Association is required.</span>
                                            </p>

                                            <div class="mt-4" v-if="form.is_farmer_member == 1">
                                                <InputLabel for="specify_farmer_asso" value="Specify" :required="true" />
                                                <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.asocc_name" autocomplete="off" 
                                                    @blur="v$.asocc_name.$touch()"
                                                    :class="inputBorderClass('asocc_name')"
                                                />
                                                <p v-if="hasError('asocc_name')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.asocc_name.required?.$invalid">Specify Farmers Association is required.</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-500" />

                                    <div class="sm:w-full md:w-10/12 lg:w-10/12 xl:w-8/12 2xl:w-6/12 mx-auto mb-4">
                                        <div class="flex flex-wrap items-start justify-between">
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="person-emergency" value="Person to notify in case of Emergency" :required="true" />
                                                <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" v-model="form.person_emergency"
                                                    @blur="v$.person_emergency.$touch()"
                                                    :class="inputBorderClass('person_emergency')"
                                                />
                                                <p v-if="hasError('person_emergency')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.person_emergency.required?.$invalid">Emergency Person is required.</span>
                                                </p>
                                            </div>
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="contact-emergency-no" value="Contact No." :required="true" />
                                                <TextInput type="text" class="mt-1 block w-full uppercase" id="contact-emergency" v-model="form.contact_emergency" autocomplete="off" 
                                                    @blur="v$.contact_emergency.$touch()"
                                                    :class="inputBorderClass('contact_emergency')"
                                                />
                                                <p v-if="hasError('contact_emergency')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.contact_emergency.required?.$invalid">Emergency Contact No. is required.</span>
                                                    <span class="text-red-500 text-sm" v-if="v$.contact_emergency.phoneFormat?.$invalid">Invalid phone format. Use (09) 1234-56789</span>
                                                </p>
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
                                            <div class="sm:w-full md:w-11/12 lg:w-11/12 xl:w-10/12 2xl:w-9/12">
                                                <div class="flex flex-wrap items-center justify-center sm:gap-x-6 md:gap-x-7 lg:gap-x-10 xl:gap-x-15 2xl:gap-x-32">
                                                    <div v-for="option in main_livelihood" :key="option.value" class="inline-flex items-center space-x-2" >
                                                        <TextInput type="checkbox" :id="option.value" :value="option.value" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleLivelihood" :checked="form.main_livelihood.includes(option.value)" />
                                                        <InputLabel :for="option.value" :value="option.label" class="text-sm text-gray-700 cursor-pointer" />
                                                    </div>
                                                </div>
                                                <p v-if="hasError('main_livelihood')" class="text-red-500 text-sm text-center">
                                                    <span class="text-red-500 text-sm" v-if="v$.main_livelihood.required?.$invalid">Main Livelihood is required. Select atleast 1 livelihood.</span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap lg:justify-start xl:justify-center 2xl:justify-center items-stretch gap-x-3">
                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="form.main_livelihood.includes('farmer')"
                                                :class="{
                                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : form.main_livelihood.length >= 1 && form.main_livelihood.length <= 2,
                                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : form.main_livelihood.length == 3,
                                                    'md:w-[49%] lg:w-[32%] xl:w-[30%] 2xl:w-[24%]' : form.main_livelihood.length == 4
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
                                                                    <Select2 class="uppercase" :options="data" :settings="{ placeholder: 'Select An Option', width: '100%', multiple: true }" @select="handleCrops" />
                                                                </div>

                                                                <p v-if="hasError('crops')" class="text-red-500 text-sm">
                                                                    <span class="text-red-500 text-sm" v-if="v$.crops.required?.$invalid">Crops is required. Please select atleast 1 crop.</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" id="livestock" :checked="form.farmer.includes('livestock')" value="livestock" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmer" />
                                                                <InputLabel for="livestock" value="Livestock" class="text-sm text-gray-700 cursor-pointer" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="form.farmer.includes('livestock')">
                                                                <InputLabel for="livestock-specify" value="Specify: " class="me-4" />
                                                                <div class="rounded-md block mt-1 w-full">
                                                                    <Select2 class="uppercase" :options="data" :settings="{ placeholder: 'Select An Option', width: '100%', multiple: true }" @select="handleLivestock" />
                                                                </div>
                                                                <p v-if="hasError('livestock')" class="text-red-500 text-sm">
                                                                    <span class="text-red-500 text-sm" v-if="v$.livestock.required?.$invalid">Crops is required. Please select atleast 1 livestock.</span>
                                                                </p>
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
                                                                    <Select2 class="uppercase" :options="data" :settings="{ placeholder: 'Select An Option', width: '100%', multiple: true }" @select="handlePoultry" />
                                                                </div>
                                                                <p v-if="hasError('poultry')" class="text-red-500 text-sm">
                                                                    <span class="text-red-500 text-sm" v-if="v$.poultry.required?.$invalid">Poultry is required. Please select atleast 1 poultry.</span>
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <p v-if="hasError('farmer')" class="text-red-500 text-sm">
                                                            <span class="text-red-500 text-sm" v-if="v$.farmer.required?.$invalid">Farming Activity is required. Please select atleast 1.</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="form.main_livelihood.includes('farm_worker')"
                                                :class="{
                                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : form.main_livelihood.length >= 1 && form.main_livelihood.length <= 2,
                                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : form.main_livelihood.length == 3,
                                                    'md:w-[49%] lg:w-[28%] xl:w-[28%] 2xl:w-[22%]' : form.main_livelihood.length == 4
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
                                                                    <TextInput type="text" name="suffix" v-model="form.farm_worker_others" class="mt-1 block w-full uppercase" autocomplete="off" 
                                                                        @blur="v$.farm_worker_others.$touch()"
                                                                        :class="inputBorderClass('farm_worker_others')"
                                                                    />
                                                                </div>
                                                                <p v-if="hasError('farm_worker_others')" class="text-red-500 text-sm">
                                                                    <span class="text-red-500 text-sm" v-if="v$.farm_worker_others.required?.$invalid">Value is required.</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <p v-if="hasError('farm_worker')" class="text-red-500 text-sm">
                                                        <span class="text-red-500 text-sm" v-if="v$.farm_worker.required?.$invalid">Kind of work for farmworkers is required. Please select atleast 1.</span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="form.main_livelihood.includes('fisherfolks')"
                                                :class="{
                                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : form.main_livelihood.length >= 1 && form.main_livelihood.length <= 2,
                                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : form.main_livelihood.length == 3,
                                                    'md:w-[49%] lg:w-[36%] xl:w-[36%] 2xl:w-[26%]' : form.main_livelihood.length == 4
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
                                                                    <TextInput type="text" name="suffix" v-model="form.fisherfolks_others" class="mt-1 block w-full uppercase" autocomplete="off" 
                                                                    @blur="v$.fisherfolks_others.$touch()"
                                                                        :class="inputBorderClass('fisherfolks_others')"
                                                                        />
                                                                </div>
                                                                <p v-if="hasError('fisherfolks_others')" class="text-red-500 text-sm">
                                                                    <span class="text-red-500 text-sm" v-if="v$.fisherfolks_others.required?.$invalid">Value is required.</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <p v-if="hasError('fisherfolks')" class="text-red-500 text-sm">
                                                        <span class="text-red-500 text-sm" v-if="v$.fisherfolks.required?.$invalid">Fishing Activity is required. Please select atleast 1.</span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="form.main_livelihood.includes('agri_youth')"
                                                :class="{
                                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : form.main_livelihood.length >= 1 && form.main_livelihood.length <= 2,
                                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : form.main_livelihood.length == 3,
                                                    'md:w-[49%] lg:w-[33%] xl:w-[33%] 2xl:w-[24%]' : form.main_livelihood.length == 4
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
                                                                    <TextInput type="text" name="suffix" v-model="form.agri_youth_others" class="mt-1 block w-full uppercase" autocomplete="off" 
                                                                    @blur="v$.agri_youth_others.$touch()"
                                                                        :class="inputBorderClass('agri_youth_others')"
                                                                        />
                                                                </div>
                                                                <p v-if="hasError('agri_youth_others')" class="text-red-500 text-sm">
                                                                    <span class="text-red-500 text-sm" v-if="v$.agri_youth_others.required?.$invalid">Value is required.</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <p v-if="hasError('agri_youth')" class="text-red-500 text-sm">
                                                        <span class="text-red-500 text-sm" v-if="v$.agri_youth.required?.$invalid">Type of Involvement is required. Please select atleast 1.</span>
                                                    </p>
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
                                                        <TextInput type="number" class="mt-1 block w-full uppercase" v-model="form.farming_gross_income"
                                                            @blur="v$.farming_gross_income.$touch()"
                                                            :class="inputBorderClass('farming_gross_income')"
                                                        />
                                                        <p v-if="hasError('farming_gross_income')" class="text-red-500 text-sm">
                                                            <span class="text-red-500 text-sm" v-if="v$.farming_gross_income.required?.$invalid">Farming Gross Income is required.</span>
                                                        </p>
                                                    </div>
                                                    <div class="sm:w-full md:w-[49%]">
                                                        <InputLabel for="non-farming" value="Non-farming" :required="true" />
                                                        <TextInput type="number" class="mt-1 block w-full uppercase"  v-model="form.non_farming_gross_income"
                                                            @blur="v$.non_farming_gross_income.$touch()"
                                                            :class="inputBorderClass('non_farming_gross_income')"
                                                        />
                                                        <p v-if="hasError('non_farming_gross_income')" class="text-red-500 text-sm">
                                                            <span class="text-red-500 text-sm" v-if="v$.non_farming_gross_income.required?.$invalid">Non Farming Gross Income is required.</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-500" />
                                    
                                    <div class="mb-6">
                                        <div class="flex flex-wrap items-center justify-between">
                                            <div class="sm:w-full md:w-6/12 lg:w-6/12 xl:w-6/12 2xl:w-5/12">
                                                <div class="flex flex-wrap items-center">
                                                    <InputLabel for="farm-parcels" class="lg:w-[36%] xl:w-4/12 2xl:w-4/12" value="No. of Farm Parcels" :required="true" />
                                                    <TextInput type="number" class="block uppercase lg:w-2/12 xl:w-2/12 2xl:w-2/12" min="1" autocomplete="off" v-model="form.farm_parcel_no"
                                                        @blur="v$.farm_parcel_no.$touch()"
                                                        :class="inputBorderClass('farm_parcel_no')"
                                                    />
                                                </div>
                                                <p v-if="hasError('farm_parcel_no')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.farm_parcel_no.required?.$invalid">Farming Parcel No. is required.</span>
                                                </p>
                                            </div>
                                            <div class="sm:w-full md:w-6/12 lg:w-6/12 xl:w-6/12 2xl:w-7/12">
                                                <div class="flex flex-wrap items-center">
                                                    <InputLabel for="farm-parcels" class="lg:w-[36%] xl:w-4/12 2xl:w-4/12 me-4" value="Agrarian Reform Beneficiary (ARB)" :required="true" />
                                                    <div class="flex flex-wrap items-center lg:w-4/12 xl:w-4/12 2xl:w-5/12 space-x-3">
                                                        <label class="lg:w-[40%] xl:w-[25%] 2xl:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                            <TextInput type="radio" name="is_arb" v-model="form.is_arb" value="1" class="accent-blue-600" />
                                                            <span class="text-gray-700">Yes</span>
                                                        </label>

                                                        <label class="lg:w-[40%] xl:w-[25%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                            <TextInput type="radio" name="is_arb" v-model="form.is_arb" value="0" class="accent-blue-600" />
                                                            <span class="text-gray-700">No</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <p v-if="hasError('is_arb')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="v$.is_arb.required?.$invalid">ARB is required.</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <div class="overflow-auto w-full">
                                            <template v-for="(item, index) in form.farm_parcel" :key="index">
                                                <div :class="form.farm_parcel.length > 0 ? 'mb-4' : 'mb-0'">
                                                    <div class="p-6 lg:p-8 bg-white border shadow-3xl rounded-lg border-gray-300">
                                                        <div class="flex flex-wrap justify-between items-center mb-4">
                                                            <div class="w-3/12 md:order-2 lg:order-1 xl:order-1 2xl:order-1">
                                                                <h4 class="text-MD font-semibold">FARM PARCEL NO: {{ index+1 }}</h4>
                                                            </div>
                                                            <div class="w-5/12 md:order-1 lg:order-2 xl:order-2 2xl:order-2">
                                                                <div class="flex flex-wrap gap-x-2 justify-end">
                                                                    <button button="button" @click="addFarmParcel" class="bg-green-500 text-white px-3 py-2 rounded-sm text-sm hover:bg-green-600 flex items-center">
                                                                        <span class="text-sm leading-none">Add Farm Parcel</span>
                                                                    </button>
                                                                    <button button="button" v-if="index != 0" @click="removeFarmParcel(index)" class="bg-red-500 text-white px-3 py-2 rounded-sm text-sm hover:bg-red-600 flex items-center">
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
                                                                        <TextInput type="text" v-model="item.municipality" class="mt-1 block w-full uppercase" autocomplete="off"

                                                                            :class="{
                                                                                'border-gray-300': item.municipality == null,
                                                                                'border-red-500' : item.municipality != NULL && v$.farm_parcel.$each.$response.$errors[index].municipality.length == 1,
                                                                                'border-green-500' : item.municipality && v$.farm_parcel.$each.$response.$errors[index].municipality.length == 0
                                                                            }"
                                                                        />
                                                                        <span class="text-red-500 text-sm" v-for="error in v$.farm_parcel.$each.$response.$errors[index].municipality" :key="error">Municipality is Required</span>
                                                                    </div>
                                                                    <div class="w-[40%]">
                                                                        <InputLabel for="farm_brgy" value="Barangay" :required="true" />
                                                                        <TextInput type="text" v-model="item.brgy" class="mt-1 block w-full uppercase" autocomplete="off" :class="{
                                                                                'border-gray-300': item.brgy == null,
                                                                                'border-red-500' : item.brgy != NULL && v$.farm_parcel.$each.$response.$errors[index].brgy.length == 1,
                                                                                'border-green-500' : item.brgy && v$.farm_parcel.$each.$response.$errors[index].brgy.length == 0
                                                                            }"
                                                                        />
                                                                        <span class="text-red-500 text-sm" v-for="error in v$.farm_parcel.$each.$response.$errors[index].brgy" :key="error">Barangay is Required</span>
                                                                    </div>
                                                                    <div class="w-2/12">
                                                                        <InputLabel for="total_farm_area" value="Total Farm Area" :required="true" />
                                                                            <div class="mt-1 flex rounded-md shadow-sm">
                                                                                <TextInput type="number" v-model="item.total_farm_area" class="flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" autocomplete="off" style="height: 41px" :class="{
                                                                                    'border-gray-300': item.total_farm_area == null,
                                                                                    'border-red-500' : item.total_farm_area != NULL && v$.farm_parcel.$each.$response.$errors[index].total_farm_area.length == 1,
                                                                                    'border-green-500' : item.total_farm_area && v$.farm_parcel.$each.$response.$errors[index].total_farm_area.length == 0
                                                                                }"
                                                                            />
                                                                            <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"> ha </span>
                                                                        </div>
                                                                        <span class="text-red-500 text-sm" v-for="error in v$.farm_parcel.$each.$response.$errors[index].total_farm_area" :key="error">Farm Area is Required</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-wrap justify-center items-start gap-x-5 mb-5">
                                                            <div class="w-5/12">
                                                                <InputLabel for="Ansentral" value="Within Ancentral Domain" :required="true" />
                                                                <div class="flex flex-wrap items-center mt-3">
                                                                    <label class="sm:w-[49%] md:w-[49%] lg:w-[40%] 2xl:w-[28%] flex items-center space-x-2 cursor-pointer">
                                                                        <TextInput type="radio" name="is_whithin_ancentral_domain" v-model="item.is_whithin_ancentral_domain" value="1" class="accent-blue-600" />
                                                                        <span class="text-gray-700">Yes</span>
                                                                    </label>

                                                                    <label class="sm:w-[49%] md:w-[49%] lg:w-[40%] 2xl:w-[28%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                                        <TextInput type="radio" name="is_whithin_ancentral_domain" v-model="item.is_whithin_ancentral_domain" value="0" class="accent-blue-600" />
                                                                        <span class="text-gray-700">No</span>
                                                                    </label>
                                                                </div>
                                                                <span class="text-red-500 text-sm" v-for="error in v$.farm_parcel.$each.$response.$errors[index].is_whithin_ancentral_domain" :key="error">Ancentral Domain is Required</span>
                                                            </div>
                                                            <div class="w-5/12">
                                                                <InputLabel for="Agrarian" value="Agrarian Reform Beneficiary" :required="true" />
                                                                <div class="flex flex-wrap items-center mt-3">
                                                                    <label class="sm:w-[49%] md:w-[49%] lg:w-[40%] 2xl:w-[28%] flex items-center space-x-2 cursor-pointer">
                                                                        <TextInput type="radio" name="is_agrarian_reform_beneficiary" v-model="item.is_agrarian_reform_beneficiary" value="1" class="accent-blue-600" />
                                                                        <span class="text-gray-700">Yes</span>
                                                                    </label>

                                                                    <label class="sm:w-[49%] md:w-[49%] lg:w-[40%] 2xl:w-[28%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                                        <TextInput type="radio" name="is_agrarian_reform_beneficiary" v-model="item.is_agrarian_reform_beneficiary" value="0" class="accent-blue-600" />
                                                                        <span class="text-gray-700">No</span>
                                                                    </label>
                                                                </div>
                                                                <span class="text-red-500 text-sm" v-for="error in v$.farm_parcel.$each.$response.$errors[index].is_agrarian_reform_beneficiary" :key="error">Agrarian Refom Beneficiary is Required</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-wrap justify-center items-start gap-x-5 mb-5">
                                                            <div class="w-3/12">
                                                                <InputLabel for="ownership_doc" value="Ownership Document No" :required="true" />
                                                                <TextInput type="text" v-model="item.owner_doc_no" class="mt-1 block w-full uppercase" autocomplete="off" 
                                                                :class="{
                                                                        'border-gray-300': item.owner_doc_no == null,
                                                                        'border-red-500' : item.owner_doc_no != NULL && v$.farm_parcel.$each.$response.$errors[index].owner_doc_no.length == 1,
                                                                        'border-green-500' : item.owner_doc_no && v$.farm_parcel.$each.$response.$errors[index].owner_doc_no.length == 0
                                                                    }"
                                                                />
                                                                <span class="text-red-500 text-sm" v-for="error in v$.farm_parcel.$each.$response.$errors[index].owner_doc_no" :key="error">Owner Doc No. is Required</span>
                                                            </div>
                                                            <div class="w-3/12">
                                                                <InputLabel for="ownership_type" value="Type of Ownership" :required="true" />
                                                                <div class="rounded-md block w-full mt-1">
                                                                    <Select2 class="uppercase" :options="ownership_type" v-model="item.ownership_type" :settings="{ placeholder: 'Select An Option', width: '100%' }" @select="handleOwnership(index, $event)" />
                                                                </div>
                                                                <span class="text-red-500 text-sm" v-for="error in v$.farm_parcel.$each.$response.$errors[index].ownership_type" :key="error">Type of Ownership is Required</span>
                                                            </div>
                                                            <div class="w-4/12" v-if="item.ownership_type == 'Tenant' || item.ownership_type == 'Lesse'">
                                                                <InputLabel for="Name of Land Owner" value="Name of Land Owner" :required="true" />
                                                                <input type="text" class="mt-1 block w-full uppercase" v-model="item.land_owner_name" autocomplete="off" required="true" :class="{
                                                                        'border-gray-300': item.land_owner_name == null,
                                                                        'border-red-500' : item.land_owner_name != null && item.land_owner_name == '',
                                                                        'border-green-500' : item.land_owner_name
                                                                    }"
                                                                />
                                                                <span class="text-red-500 text-sm" v-if="item.land_owner_name != null && item.land_owner_name == ''">Name of Land Owner is Required</span>
                                                            </div>
                                                            <div class="w-4/12"v-if="item.ownership_type == 'Others'">
                                                                <InputLabel for="specify-other" value="Specify" :required="true" />
                                                                <input type="text" class="mt-1 block w-full uppercase" v-model="item.is_other" autocomplete="off" required="true" :class="{
                                                                        'border-gray-300': item.is_other == null,
                                                                        'border-red-500' : item.is_other != null && item.is_other == '',
                                                                        'border-green-500' : item.is_other
                                                                    }"
                                                                />
                                                                <span class="text-red-500 text-sm" v-if="item.is_other != null && item.is_other == ''">Name of Land Owner is Required</span>
                                                            </div>
                                                        </div>

                                                        <table class="border-collapse border-2 border-gray-400 w-full">
                                                            <thead>
                                                                <tr>
                                                                    <th class="p-3 border border-gray-400 w-[17%]">
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
                                                                    <th class="p-3 border border-gray-400 w-[8%]">
                                                                        <strong>NO. OF HEAD</strong>
                                                                        <p class="m-0">
                                                                            <small class="italic">( For livestock and poultry)</small>
                                                                        </p>
                                                                    </th>
                                                                    <th class="p-3 border border-gray-400 w-[24%]">FARM TYPE</th>
                                                                    <th class="p-3 border border-gray-400 w-[18%]">ORGANIC PRACTITIONER</th>
                                                                    <th class="p-3 border border-gray-400 w-[18%]">REMARKS</th>
                                                                    <th class="p-3 border border-gray-400 w-[10%]"></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-for="(v, i) in item.farm_parcel_info" :key="i" style="vertical-align: top;">
                                                                    <td class="p-3 border border-gray-400">
                                                                        <div class="rounded-md block w-full">
                                                                            <Select2 class="h-10 uppercase" v-model="v.commodity" :options="ownership_type" :settings="{ placeholder: 'Select An Option', width: '200px' }" />
                                                                        </div>
                                                                        <template v-for="error in v$.farm_parcel.$each.$response.$errors[index].farm_parcel_info" :key="error">
                                                                            <span class="text-red-500 text-sm" v-if="error.$response.$errors[i].commodity.length == 1">
                                                                                Value is required
                                                                            </span>
                                                                        </template>
                                                                    </td>
                                                                    <td class="p-3 border border-gray-400">
                                                                        <TextInput type="number" class="block w-full uppercase" v-model="v.size" min="0" />
                                                                        <template v-for="error in v$.farm_parcel.$each.$response.$errors[index].farm_parcel_info" :key="error">
                                                                            <span class="text-red-500 text-sm" v-if="error.$response.$errors[i].size.length == 1">
                                                                                Value is required
                                                                            </span>
                                                                        </template>
                                                                    </td>
                                                                    <td class="p-3 border border-gray-400">
                                                                        <TextInput type="number" class="block w-full uppercase" v-model="v.head_no" min="0" />
                                                                        <template v-for="error in v$.farm_parcel.$each.$response.$errors[index].farm_parcel_info" :key="error">
                                                                            <span class="text-red-500 text-sm" v-if="error.$response.$errors[i].head_no.length == 1">
                                                                                Value is required
                                                                            </span>
                                                                        </template>
                                                                    </td>
                                                                    <td class="p-3 border border-gray-400">
                                                                        <div class="rounded-md block w-full">
                                                                            <Select2 class="h-10 uppercase" v-model="v.farm_type" :options="farm_type" :settings="{ placeholder: 'Select Option', width: '100%' }" />
                                                                            <template v-for="error in v$.farm_parcel.$each.$response.$errors[index].farm_parcel_info" :key="error">
                                                                                <span class="text-red-500 text-sm" v-if="error.$response.$errors[i].farm_type.length == 1">
                                                                                    Value is required
                                                                                </span>
                                                                            </template>
                                                                        </div>
                                                                    </td>
                                                                    <td class="p-3 border border-gray-400">
                                                                        <div class="rounded-md block w-full">
                                                                            <Select2 class="h-10 uppercase" v-model="v.is_organic_practitioner" :options="[{id: 1, text: 'Yes' }, {id: 0, text: 'No'}]" :settings="{ placeholder: 'Select', width: '100%' }" />
                                                                            <template v-for="error in v$.farm_parcel.$each.$response.$errors[index].farm_parcel_info" :key="error">
                                                                                <span class="text-red-500 text-sm" v-if="error.$response.$errors[i].is_organic_practitioner.length == 1">
                                                                                    Value is required
                                                                                </span>
                                                                            </template>
                                                                        </div>
                                                                    </td>
                                                                    <td class="p-3 border border-gray-400">
                                                                        <TextInput type="text" class="block w-full uppercase" v-model="v.remarks" autocomplete="off" />
                                                                    </td>
                                                                    <td class="p-3 border border-gray-400">
                                                                        <div class="flex flex-wrap gap-x-1 justify-center mt-2">
                                                                            <button button="button" @click="addParcelInfo(index)" class="bg-green-500 text-white px-2 py-1 rounded-full text-sm hover:bg-green-600 flex items-center mb-1">
                                                                                <span class="text-lg leading-none">+</span>
                                                                            </button>
                                                                            <button button="button" v-if="i != 0" @click="removeParcelInfo(i, index)" class="bg-red-500 text-white px-2 py-1 rounded-full text-sm hover:bg-red-600 flex items-center mb-1">
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
                                <div v-if="step === 2">
                                    <div class="flex flex-wrap w-full mb-4">
                                        <div class="w-full">
                                            <h3 class="font-bold text-lg">PART I: FARMER PROFILE</h3>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap">
                                        <div class="w-6/12">
                                            <div class="flex flex-wrap items-center gap-6 mt-3">
                                                <InputLabel for="ref_no" value="Reference / Control No." :required="true" class="sm:w-full md:w-[18%] lg:w-[38%] 2xl:w-[26%] mb-0" />
                                                <p class="border rounded block p-2 w-full uppercase sm:w-full md:w-[18%]  md:w-[16%] lg:w-[38%] 2xl:w-[40%]" id="ref_no">{{ form.ref_no ? form.ref_no : 'No Reference No.' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-500" />

                                    <div class="flex flex-wrap items-center">
                                        <div class="sm:w-full md:w-[18%]  md:w-[16%] lg:w-[18%] 2xl:w-[18%] mx-auto">
                                            <InputLabel for="profile" value="Farmer Image" :required="true" />
                                            <div class="relative md:w-35 md:h-35 2xl:w-40 2xl:h-40 rounded-md border border-gray-300">
                                                <img :src="form.image.length > 0 ? form.image : '/storage/images/no-user-image.png'" alt="" class="w-full h-full object-contain" style="scale: 0.9;">
                                            </div>
                                        </div>
                                        <div class="sm:w-full md:w-[76%] lg:w-[76%] xl:w-[76%] 2xl:w-[79%]">
                                            <div class="flex flex-wrap justify-between mb-4">
                                                <div class="sm:w-full md:w-[49%]">
                                                    <InputLabel for="firstname" value="First name" :required="true" />
                                                    <p class="border rounded block p-2 w-full uppercase">{{ form.firstname ? form.firstname : 'No Firstname' }}</p>
                                                </div>
                                                <div class="sm:w-full md:w-[49%]">
                                                    <InputLabel for="lastname" value="Last name" :required="true" />
                                                    <p class="border rounded block p-2 w-full uppercase">{{ form.lastname ? form.lastname : 'No Lastname' }}</p>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap justify-between">
                                                <div class="sm:w-full md:w-[49%]">
                                                    <InputLabel for="middlename" value="Middle name" />
                                                    <p class="border rounded block p-2 w-full uppercase">{{ form.middlename ? form.middlename : '&nbsp;' }}</p>
                                                </div>
                                                <div class="sm:w-full md:w-2/12">
                                                    <InputLabel for="extension" value="Extension" />
                                                    <p class="border rounded block p-2 w-full uppercase">{{ form.suffix ? form.suffix : '&nbsp;' }}</p>
                                                </div>
                                                <div class="sm:w-full md:w-[30%] md:p-x-2">
                                                    <InputLabel for="gender" value="Gender" :required="true" />
                                                    <p class="border rounded block p-2 w-full uppercase">{{ form.gender ? form.gender : 'N/A' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-500" />

                                    <div class="flex flex-wrap items-center justify-between mb-4">
                                        <div class="w-full">
                                            <h3 class="font-bold text-md">ADDRESS</h3>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-start justify-between mb-4">
                                        <div class="md:w-[32.10%] sm:w-full">
                                            <InputLabel for="house" value="House / Lot / Bldg. No." />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.lot ? form.lot : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[32.10%] sm:w-full">
                                            <InputLabel for="street" value="Street / Sitio / Subdv." />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.street ? form.street : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[32.10%] sm:w-full">
                                            <InputLabel for="barangay" value="Barangay" :required="true" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.brgy ? form.brgy : '&nbsp;' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-start justify-between">
                                        <div class="md:w-[39.10%] sm:w-full">
                                            <InputLabel for="municipality" value="Municipality / City" :required="true" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.muni_city ? form.muni_city : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[39.10%] sm:w-full">
                                            <InputLabel for="province" value="Province" :required="true" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.province ? form.province : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[18%] sm:w-full">
                                            <InputLabel for="region" value="Region" :required="true" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.region ? form.region : '&nbsp;' }}</p>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-500" />

                                    <div class="flex flex-wrap items-start justify-between mb-4">
                                        <div class="md:w-[24%] sm:w-full">
                                            <InputLabel for="contact" value="Contact No." :required="true" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.contact ? form.contact : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[24%] sm:w-full">
                                            <InputLabel for="birth" value="Date of Birth" :required="true" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.birth ? form.birth : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[24%] sm:w-full">
                                            <InputLabel for="birth-place" value="Place of Birth" :required="true" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.birthplace ? form.birthplace : '&nbsp;' }}</p>
                                        </div>
                                        <div class="md:w-[24%] sm:w-full">
                                            <InputLabel for="religon" value="Religon" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.religion ? form.religion : '&nbsp;' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-x-4 items-start justify-center mb-4">
                                        <div class="md:w-[32%] sm:w-full">
                                            <InputLabel for="civil-status" value="Civil Status" :required="true" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.civil_status ? form.civil_status : '&nbsp;' }}</p>
                                            <div class="mt-4" v-if="form.civil_status.includes(['Married', 'Windowed'])">
                                                <InputLabel for="spouse-name" value="Name of Spouse" :required="true" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ form.spouse ? form.spouse : '&nbsp;' }}</p>
                                            </div>
                                        </div>
                                        <div class="md:w-[32%] sm:w-full">
                                            <InputLabel for="mother-name" value="Mothers' Maiden Name" :required="true" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.mother_maiden_name ? form.mother_maiden_name : '&nbsp;' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-x-4 items-start justify-center mb-4">
                                        <div class="w-full">
                                            <div class="flex items-center gap-6 mt-3">
                                                <InputLabel for="household-head" value="Household Head?" :required="true" class="mb-0" />

                                                <div class="flex items-center gap-4">
                                                    <label class="flex items-center space-x-2">
                                                        <TextInput type="radio" :value="1" class="accent-blue-600" :checked="form.is_household_head == 1 && form.is_household_head != ''" disabled />
                                                        <span class="text-gray-700">Yes</span>
                                                    </label>

                                                    <label class="flex items-center space-x-2">
                                                        <TextInput type="radio" :value="0" class="accent-blue-600" :checked="form.is_household_head == 0 && form.is_household_head != ''" disabled />
                                                        <span class="text-gray-700">No</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sm:w-full md:w-10/12 lg:w-8/12 xl:w-6/12 2xl:w-6/12 mx-auto mb-4" v-if="form.is_household_head == 0 && form.is_household_head != ''">
                                        <div class="flex flex-wrap align-start justify-between">
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="household-head" value="Name of Household Head" :required="true" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ form.household_head_name ? form.household_head_name : '&nbsp;' }}</p>
                                            </div>
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="relationship" value="Relationship" :required="true" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ form.head_relationship ? form.head_relationship : '&nbsp;' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sm:w-full md:w-full lg:w-12/12 xl:w-11/12 2xl:w-8/12 mx-auto mb-4">
                                        <div class="flex flex-wrap items-start justify-between">
                                            <div class="sm:w-full md:w-[32%]">
                                                <InputLabel for="living-household-members" value="No. of living household members" :required="true" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ form.members_no ? form.members_no : '&nbsp;' }}</p>
                                            </div>
                                            <div class="sm:w-full md:w-[32%]">
                                                <InputLabel for="no-of-male" value="No. of Male" :required="true" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ form.no_of_male ? form.no_of_male : '&nbsp;' }}</p>
                                            </div>
                                            <div class="sm:w-full md:w-[32%]">
                                                <InputLabel for="no-of-female" value="No. of Female" :required="true" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ form.no_of_female ? form.no_of_female : '&nbsp;' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-500" />

                                    <div class="flex flex-wrap sm:gap-x-0 md:gap-x-2 lg:gap-x-3 xl:gap-x-4 2xl:gap-x-5 items-start justify-center mb-4">
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="education" value="Highest Formal Education" :required="true" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ form.education ? form.education : '&nbsp;' }}</p>
                                        </div>
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="pwd" value="Person with Disability (PWD)" :required="true" />
                                            <div class="flex flex-wrap items-center mt-3">
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="1" class="accent-blue-600" :checked="form.is_pwd == 1 && form.is_pwd != ''" disabled />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="0" class="accent-blue-600" :checked="form.is_pwd == 0 && form.is_pwd != ''" disabled />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="4ps" value="4P's Beneficiary?" :required="true" />
                                            <div class="flex flex-wrap items-center mt-3">
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="1" class="accent-blue-600" :checked="form.is_4ps == 1 && form.is_4ps != ''" disabled />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="0" class="accent-blue-600" :checked="form.is_4ps == 0 && form.is_4ps != ''" disabled />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="gov-id" value="With Government ID?" :required="true" />
                                            <div class="flex flex-wrap items-center mt-3">
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="1" class="accent-blue-600" :checked="form.has_gov_id == 1 && form.has_gov_id != ''" disabled />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="0" class="accent-blue-600" :checked="form.has_gov_id == 0 && form.has_gov_id != ''" disabled />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>

                                            <div class="mt-4" v-if="form.has_gov_id == 1">
                                                <InputLabel for="specify_id" value="Specify ID number" :required="true" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ form.gov_id_no ? form.gov_id_no : '&nbsp;' }}</p>
                                            </div>
                                        </div>
                                        <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                            <InputLabel for="gov-id" value="Member of any Farmers Association / Cooperative?" :required="true" />
                                            <div class="flex flex-wrap items-center mt-3">
                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="1" class="accent-blue-600" :checked="form.is_farmer_member == 1 && form.is_farmer_member != ''" disabled />
                                                    <span class="text-gray-700">Yes</span>
                                                </label>

                                                <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                    <TextInput type="radio" value="0" class="accent-blue-600" :checked="form.is_farmer_member == 0 && form.is_farmer_member != ''" disabled />
                                                    <span class="text-gray-700">No</span>
                                                </label>
                                            </div>

                                            <div class="mt-4" v-if="form.is_farmer_member == 1">
                                                <InputLabel for="specify_farmer_asso" value="Specify" :required="true" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ form.asocc_name ? form.asocc_name : '&nbsp;' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-500" />

                                    <div class="sm:w-full md:w-10/12 lg:w-10/12 xl:w-8/12 2xl:w-6/12 mx-auto mb-4">
                                        <div class="flex flex-wrap items-start justify-between">
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="person-emergency" value="Person to notify in case of Emergency" :required="true" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ form.person_emergency ? form.person_emergency : '&nbsp;' }}</p>
                                            </div>
                                            <div class="sm:w-full md:w-[49%]">
                                                <InputLabel for="contact-emergency-no" value="Contact No." :required="true" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ form.contact_emergency ? form.contact_emergency : '&nbsp;' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-500" />

                                    <div class="flex flex-wrap w-full mb-4">
                                        <div class="w-full">
                                            <h3 class="font-bold text-lg">PART II: FARM PROFILE</h3>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <div class="flex flex-wrap w-full mb-4">
                                            <div class="w-full">
                                                <h3 class="font-bold text-md">MAIN LIVELIHOOD</h3>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap mb-4 justify-center">
                                            <div class="sm:w-full md:w-11/12 lg:w-11/12 xl:w-10/12 2xl:w-9/12">
                                                <div class="flex flex-wrap items-center justify-center sm:gap-x-6 md:gap-x-7 lg:gap-x-10 xl:gap-x-15 2xl:gap-x-32">
                                                    <div v-for="option in main_livelihood" :key="option.value" class="inline-flex items-center space-x-2" >
                                                        <TextInput type="checkbox" :id="option.value" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 " :checked="form.main_livelihood.includes(option.value)" disabled />
                                                        <InputLabel :for="option.value" :value="option.label" class="text-sm text-gray-700" />
                                                    </div>
                                                </div>
                                                <p v-if="hasError('main_livelihood')" class="text-red-500 text-sm text-center">
                                                    <span class="text-red-500 text-sm" v-if="v$.main_livelihood.required?.$invalid">Main Livelihood is required. Select atleast 1 livelihood.</span>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap lg:justify-start xl:justify-center 2xl:justify-center items-stretch gap-x-3">
                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="form.main_livelihood.includes('farmer')"
                                                :class="{
                                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : form.main_livelihood.length >= 1 && form.main_livelihood.length <= 2,
                                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : form.main_livelihood.length == 3,
                                                    'md:w-[49%] lg:w-[32%] xl:w-[30%] 2xl:w-[24%]' : form.main_livelihood.length == 4
                                                }"
                                            >
                                                <div class="p-4 lg:p-6 bg-white">
                                                    <h4 class="text-center font-bold italic text-lg mb-3">For Farmers:</h4>

                                                    <h5 class="font-bold text-md mb-2">Type of Farming Activity</h5>
                                                    <div class="flex flex-wrap">
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.farmer.includes('rice')" value="rice" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="rice" value="Rice" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.farmer.includes('corn')" value="corn" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="corn" value="Corn" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" id="crops" :checked="form.farmer.includes('crops')" value="crops" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="crops" value="Other crops" class="text-sm text-gray-700" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="form.farmer.includes('crops')">
                                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                                <div class="rounded-md block mt-1 w-full">
                                                                    <!-- not done here -->
                                                                    <Select2 class="uppercase" :options="data" :settings="{ placeholder: 'Select An Option', width: '100%', multiple: true }" @select="handleCrops" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" id="livestock" :checked="form.farmer.includes('livestock')" value="livestock" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="livestock" value="Livestock" class="text-sm text-gray-700" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="form.farmer.includes('livestock')">
                                                                <InputLabel for="livestock-specify" value="Specify: " class="me-4" />
                                                                <div class="rounded-md block mt-1 w-full">
                                                                    <!-- not done here -->
                                                                    <Select2 class="uppercase" :options="data" :settings="{ placeholder: 'Select An Option', width: '100%', multiple: true }" @select="handleLivestock" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="w-full">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" id="poultry" :checked="form.farmer.includes('poultry')" value="poultry" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="poultry" value="Poultry" class="text-sm text-gray-700" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="form.farmer.includes('poultry')">
                                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                                <div class="rounded-md block mt-1 w-full">
                                                                    <!-- not done here -->
                                                                    <Select2 class="uppercase" :options="data" :settings="{ placeholder: 'Select An Option', width: '100%', multiple: true }" @select="handlePoultry" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="form.main_livelihood.includes('farm_worker')"
                                                :class="{
                                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : form.main_livelihood.length >= 1 && form.main_livelihood.length <= 2,
                                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : form.main_livelihood.length == 3,
                                                    'md:w-[49%] lg:w-[28%] xl:w-[28%] 2xl:w-[22%]' : form.main_livelihood.length == 4
                                                }"
                                            >
                                                <div class="p-4 lg:p-6 bg-white">
                                                    <h4 class="text-center font-bold italic text-lg mb-3">For Farmworkers:</h4>

                                                    <h5 class="font-bold text-md mb-2">Kind of Work</h5>

                                                    <div class="flex flex-wrap">
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.farm_worker.includes('Land Preparation')" value="Land Preparation" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="land-preparation" value="Land Preparation" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.farm_worker.includes('Planting / Transplanting')" value="Planting / Transplanting" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 " disabled />
                                                                <InputLabel for="planting" value="Planting / Transplanting" class="text-sm text-gray-700 " />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.farm_worker.includes('Cultivation')" value="Cultivation" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="cultivation" value="Cultivation" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.farm_worker.includes('Harvesting')" value="Harvesting" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="harvesting" value="Harvesting" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.farm_worker.includes('Others')" value="Others" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="farmworker-other" value="Others" class="text-sm text-gray-700" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="form.farm_worker.includes('Others')">
                                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                                <p class="border rounded block p-2 w-full uppercase">{{ form.farm_worker_others ? form.farm_worker_others : '&nbsp;' }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="form.main_livelihood.includes('fisherfolks')"
                                                :class="{
                                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : form.main_livelihood.length >= 1 && form.main_livelihood.length <= 2,
                                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : form.main_livelihood.length == 3,
                                                    'md:w-[49%] lg:w-[36%] xl:w-[36%] 2xl:w-[26%]' : form.main_livelihood.length == 4
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
                                                                    <TextInput type="checkbox" :checked="form.fisherfolks.includes('Fish Capture')" value="Fish Capture" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                    <InputLabel for="fish-capture" value="Fish Capture" class="text-sm text-gray-700" />
                                                                </div>
                                                            </div>
                                                            <div class="md:w-[49%] sm:w-full">
                                                                <div class="inline-flex items-center space-x-2">
                                                                    <TextInput type="checkbox" :checked="form.fisherfolks.includes('Fish Processing')" value="Fish Processing" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                    <InputLabel for="fish-processing" value="Fish Processing" class="text-sm text-gray-700" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-wrap items-center justify-between w-full mb-2">
                                                            <div class="md:w-[49%] sm:w-full">
                                                                <div class="inline-flex items-center space-x-2">
                                                                    <TextInput type="checkbox" :checked="form.fisherfolks.includes('Aquaculture')" value="Aquaculture" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                    <InputLabel for="aquaculture" value="Aquaculture" class="text-sm text-gray-700" />
                                                                </div>
                                                            </div>
                                                            <div class="md:w-[49%] sm:w-full">
                                                                <div class="inline-flex items-center space-x-2">
                                                                    <TextInput type="checkbox" :checked="form.fisherfolks.includes('Fish Vending')" value="Fish Vending" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                    <InputLabel for="fish-vending" value="Fish Vending" class="text-sm text-gray-700" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-wrap items-center justify-between w-full mb-2">
                                                            <div class="md:w-[49%] sm:w-full">
                                                                <div class="inline-flex items-center space-x-2">
                                                                    <TextInput type="checkbox" :checked="form.fisherfolks.includes('Gleaning')" value="Gleaning" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                    <InputLabel for="gleaning" value="Gleaning" class="text-sm text-gray-700" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.fisherfolks.includes('Others')" value="Others" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="fisherfolk-other" value="Others" class="text-sm text-gray-700" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="form.fisherfolks.includes('Others')">
                                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                                <p class="border rounded block p-2 w-full uppercase">{{ form.fisherfolks_others ? form.fisherfolks_others : '&nbsp;' }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="form.main_livelihood.includes('agri_youth')"
                                                :class="{
                                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : form.main_livelihood.length >= 1 && form.main_livelihood.length <= 2,
                                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : form.main_livelihood.length == 3,
                                                    'md:w-[49%] lg:w-[33%] xl:w-[33%] 2xl:w-[24%]' : form.main_livelihood.length == 4
                                                }"
                                            >
                                                <div class="p-4 lg:p-6 bg-white">
                                                    <h4 class="text-center font-bold italic text-lg mb-3">For Agri Youth:</h4>

                                                    <p class="mb-3">For the purposes of trainings, financial assistance, and either programs and catered to the youth with involvement to any agriculture activity.</p>

                                                    <h5 class="font-bold text-md mb-2">Type of Involvement</h5>

                                                    <div class="flex flex-wrap">
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.agri_youth.includes('Part of a farming household')" value="Part of a farming household" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="part-of-farming-household" value="Part of a farming household" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.agri_youth.includes('Attending / attended formal agri-fishery related course')" value="Attending / attended formal agri-fishery related course" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="attended-formal-agri-fishery" value="Attending / attended formal agri-fishery related course" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.agri_youth.includes('Attending / attended non-formal agri-fishery related course')" value="Attending / attended non-formal agri-fishery related course" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="attended-non-formal-agri-fishery" value="Attending / attended non-formal agri-fishery related course" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full mb-2">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.agri_youth.includes('Participated a any agircultural activity / program')" value="Participated a any agircultural activity / program" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="participated-any-agri-activity" value="Participated a any agircultural activity / program" class="text-sm text-gray-700" />
                                                            </div>
                                                        </div>
                                                        <div class="w-full">
                                                            <div class="inline-flex items-center space-x-2">
                                                                <TextInput type="checkbox" :checked="form.agri_youth.includes('Others')" value="Others" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" disabled />
                                                                <InputLabel for="youth-others" value="Others" class="text-sm text-gray-700" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center ms-4" v-if="form.agri_youth.includes('Others')">
                                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                                <p class="border rounded block p-2 w-full uppercase">{{ form.agri_youth_others ? form.agri_youth_others : '&nbsp;' }}</p>
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
                                                        <InputLabel for="farming" value="Farming" :required="true" />
                                                        <p class="border rounded block p-2 w-full uppercase">{{ form.farming_gross_income ? form.farming_gross_income : '&nbsp;' }}</p>
                                                    </div>
                                                    <div class="sm:w-full md:w-[49%]">
                                                        <InputLabel for="non-farming" value="Non-farming" :required="true" />
                                                        <p class="border rounded block p-2 w-full uppercase">{{ form.non_farming_gross_income ? form.non_farming_gross_income : '&nbsp;' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-500" />

                                    <div class="mb-6">
                                        <div class="flex flex-wrap items-center justify-between">
                                            <div class="sm:w-full md:w-6/12 lg:w-6/12 xl:w-6/12 2xl:w-5/12">
                                                <div class="flex flex-wrap items-center">
                                                    <InputLabel for="farm-parcels" class="lg:w-[36%] xl:w-4/12 2xl:w-4/12" value="No. of Farm Parcels" :required="true" />
                                                    <p class="border rounded block p-2 uppercase lg:w-2/12 xl:w-2/12 2xl:w-2/12">{{ form.farm_parcel_no ? form.farm_parcel_no : '&nbsp;' }}</p>
                                                </div>
                                            </div>
                                            <div class="sm:w-full md:w-6/12 lg:w-6/12 xl:w-6/12 2xl:w-7/12">
                                                <div class="flex flex-wrap items-center">
                                                    <InputLabel for="farm-parcels" class="lg:w-[36%] xl:w-4/12 2xl:w-4/12 me-4" value="Agrarian Reform Beneficiary (ARB)" :required="true" />
                                                    <div class="flex flex-wrap items-center lg:w-4/12 xl:w-4/12 2xl:w-5/12 space-x-3">
                                                        <label class="lg:w-[40%] xl:w-[25%] 2xl:w-[20%] flex items-center space-x-2 cursor-pointer">
                                                            <TextInput type="radio" name="is_arb" :checked="form.is_arb == 1 && form.is_arb != ''" value="1" class="accent-blue-600" disabled />
                                                            <span class="text-gray-700">Yes</span>
                                                        </label>

                                                        <label class="lg:w-[40%] xl:w-[25%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                                            <TextInput type="radio" name="is_arb" :checked="form.is_arb == 0 && form.is_arb != ''" value="0" class="accent-blue-600" disabled />
                                                            <span class="text-gray-700">No</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end">
                                <button type="button" class="inline-flex items-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded me-2" @click="goToPrevious" v-if="step > 0" > 
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="me-1 size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" />
                                    </svg>
                                    <strong>PREVIOUS</strong>
                                </button>
    
                                <button v-if="step < stepLabels.length - 1" type="submit" class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded" @click="goToNext"> 
                                    <strong>NEXT</strong>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="ms-1 size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                                    </svg>
                                </button>
    
                                <button v-else class="bg-green-700 hover:bg-green-600 text-white px-4 py-2 rounded" @click="submitForm" :disabled="form.processing" >
                                    <strong>SUBMIT</strong> 
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>