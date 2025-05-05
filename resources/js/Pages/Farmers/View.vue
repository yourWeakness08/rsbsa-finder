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
    
</script>

<template>
    <AppLayout title="View Farmer Information">
        <template #header>
            <h2 class="font-semibold text-md text-gray-800 leading-tight">
                FARMERS / ViEW FARMER
            </h2>
        </template>
    </AppLayout>
</template>
