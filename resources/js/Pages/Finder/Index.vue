<script setup>
    import useValidationHelpers from '@/composables/useValidationHelpers';
    import { ref, reactive, computed, getCurrentInstance, watch, onMounted, nextTick, onBeforeUpdate } from 'vue';
    import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
    import useVuelidate from '@vuelidate/core';
    import { required, email, minLength, requiredIf, numeric, helpers } from '@vuelidate/validators';
    import AuthenticationCard from '@/Components/AuthenticationCard.vue';
    import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
    import Checkbox from '@/Components/Checkbox.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import SecondaryButton from '@/Components/SecondaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import DialogModal from '@/Components/DialogModal.vue';

    import Select2 from 'vue3-select2-component';
    import axios from 'axios';
    import moment from 'moment';
    import Swal from 'sweetalert2';
    import daterangepicker from 'daterangepicker';
    
    import $ from 'jquery';

    const props = defineProps({
        data: {
            type: Object,
            default: () => ({}),
        },
        response: {
            type: Boolean,
            default: false
        },
        auth: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        firstname: '',
        lastname: '',
        middlename: '',
        extension: '',
        gender: '',
        birth: ''
    });

    const rules = computed(() => {
        return {
            firstname: { required },
            lastname: { required },
            middlename: { },
            extension: { },
            gender: { required },
            birth: { required },
        }
    });

    const v$ = useVuelidate(rules, form, {
        $autoDirty: false
    })

    onMounted(() => {
        $('#birth').daterangepicker({
            opens: 'left',
            drops: 'up',
            locale: {
                format: 'YYYY/MM/DD',
            },
            singleDatePicker: true,
            showDropdowns: true,
            autoUpdateInput: false,
        }).on('apply.daterangepicker', function(ev, picker){
            $(this).val(picker.startDate.format('YYYY/MM/DD'))

            form.birth = moment(picker.startDate.format('YYYY/MM/DD')).format('YYYY/MM/DD');
        });
    })

    const { hasError, inputBorderClass, getFieldState } = useValidationHelpers(v$, form, { autoTouch: true })
    let farmerModal = ref(false);
    const farmer = ref([]);

    const submit = () => {
        form.post(route('finder'), {
            preserveScroll: true,
            onProgress: () => processing.value = true,

            onSuccess: () => {
                const response = props.response;

                if (response) {
                    console.log(response);
                    farmerModal.value = true;
                    farmer.value = props.data;
                }
            }
        });
    };

    const resetForm = () => {
        form.reset();
        v$.value.$reset();
    }

    const closeModal = () => {
        farmerModal.value = false;
        farmer.value = [];
    }
</script>

<style>
    #basic-info .col-md-6{
        flex: 0 0 49% !important;
    }

    #basic-info .col-md-4 {
        flex: 0 0 32% !important
    }

    #farmer .mb-6.bg-white.rounded-lg.overflow-hidden.shadow-xl.transform.transition-all.sm\:w-full.sm\:mx-auto.sm\:max-w-3xl {
        margin-top: 10vh
    }
</style>

<template>
    <Head title="Finder" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div class="flex flex-wrap items-center justify-between gap-x-5">
            <div>
                <img src="/storage/images/DA-LOGO-1024x1024.png" alt="logo" class="h-36 w-36 mx-auto">
            </div>

            <div>
                <h4 class="font-semibold uppercase text-center">Registry System for Basic Sectors <br> in Agriculture</h4>
                <h3 class="font-bold uppercase mt-3 text-2xl text-center">RSBSA - Finder</h3>
            </div>
        </div>

        <div class="w-full  md:w-[30%] mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <form @submit.prevent="submit" autocomplete="off">
                <div id="basic-info" class="flex flex-wrap justify-between items-start mb-4">
                    <div class="col-md-6">
                        <InputLabel for="firstname" value="First name" :required="true" />
                        <TextInput id="firstname" v-model="form.firstname" type="text" class="mt-1 block w-full" autocomplete="firstname" 
                            @blur="v$.firstname.$touch()"
                            :class="inputBorderClass('firstname')"
                        />
                        <p v-if="hasError('firstname')" class="text-red-500 text-sm">
                            <span class="text-red-500 text-sm" v-if="v$.firstname.required?.$invalid">First name is required.</span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <InputLabel for="middlename" value="Middle name" :optional="true" />
                        <TextInput id="middlename" v-model="form.middlename" type="text" class="mt-1 block w-full" autocomplete="middlename" />
                    </div>
                </div>

                <div class="w-full mb-4">
                    <InputLabel for="lastname" value="Last name" :required="true" />
                    <TextInput id="lastname" v-model="form.lastname" type="text" class="mt-1 block w-full" autocomplete="lastname" 
                        @blur="v$.lastname.$touch()"
                        :class="inputBorderClass('lastname')"
                    />
                    <p v-if="hasError('lastname')" class="text-red-500 text-sm">
                        <span class="text-red-500 text-sm" v-if="v$.lastname.required?.$invalid">Last name is required.</span>
                    </p>
                </div>

                <div id="basic-info" class="flex flex-wrap justify-between mb-4">
                    <div class="col-md-4">
                        <InputLabel for="extension" value="Extension" :optional="true" />
                        <TextInput id="extension" v-model="form.extension" type="text" class="mt-1 block w-full" autocomplete="extension" />
                    </div>
                    <div class="col-md-4">
                        <InputLabel for="gender" value="Gender" :required="true" />
                        <div class="mt-1 rounded-md block w-full">
                            <Select2 class="h-10 uppercase" v-model="form.gender" :options="[{id: 'male', text: 'MALE'}, {id: 'female', text: 'FEMALE'}]" :settings="{ placeholder: 'Select Option', width: '100%' }" />
                        </div>
                        <p v-if="hasError('gender')" class="text-red-500 text-sm">
                            <span class="text-red-500 text-sm" v-if="v$.gender.required?.$invalid">Gender is required.</span>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <InputLabel for="birth" value="Birth Date" :required="true" />
                        <TextInput id="birth" v-model="form.birth" type="text" class="mt-1 block w-full" autocomplete="birth" readonly 
                            @blur="v$.birth.$touch()"
                            :class="inputBorderClass('birth')"
                        />
                        <p v-if="hasError('birth')" class="text-red-500 text-sm">
                            <span class="text-red-500 text-sm" v-if="v$.birth.required?.$invalid">Birth Date is required.</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-x-1">
                    <PrimaryButton class="bg-green-600 active:bg-green-600 hover:bg-green-800" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        SUBMIT
                    </PrimaryButton>
                    <PrimaryButton class="bg-red-600 active:bg-red-600 hover:bg-red-800" @click="resetForm" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        RESET
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </div>

    <DialogModal id="farmer" :show="farmerModal" :max-width="'3xl'" @close="closeModal">
        <template #title>
            Result
        </template>
        <template #content>
            <div class="mb-4">
                <div class="w-full max-w-3xl mx-auto aspect-[847/534] relative text-black font-sans">
                    <img src="/storage/images/rsbsa-card-v2.png" alt="RSBSA Background" class="absolute inset-0 w-full h-full object-cover z-0 rounded-md" />

                    <div class="absolute left-[8%] top-[45%] w-[25%] aspect-square bg-white z-10 overflow-hidden rounded">
                        <img src="/storage/images/male-farmer.png" alt="Photo" class="w-full h-full object-cover"/>
                    </div>

                    <div class="absolute left-[40%] top-[55%] z-10">
                        <h3 id="farmer-name" class="uppercase text-xl font-bold">{{ farmer.full_name }}</h3>
                    </div>

                    <div class="absolute left-[40%] top-[75%] z-10">
                        <h3 id="ref-no" class="uppercase text-xl font-bold">{{ farmer.ref_no }}</h3>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="closeModal">Close</SecondaryButton>
        </template>
    </DialogModal>
</template>
