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
        types: {
            type: Object,
            default: () => ({})
        },
        history: {
            type: Object,
            default: () => ({})
        },
        assistance: {
            type: Object,
            default: () => ({})
        },
        allassistance: {
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

    const _main_livelihood = ref([
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

    const civil_status = ref([
        { id: 'Single', text: 'Single' },
        { id: 'Married', text: 'Married' },
        { id: 'Widowed', text: 'Widowed' },
        { id: 'Seperated', text: 'Seperated' },
    ]);

    const education = ref([
        { id: 'None', text: 'None' },
        { id: 'Elementary', text: 'Elementary' },
        { id: 'High School', text: 'High School' },
        { id: 'Vocational', text: 'Vocational' },
        { id: 'College', text: 'College' },
        { id: 'Post Graduate', text: 'Post Graduate' },
    ])

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

    const viewAttachment = (type = 'document', path) => {
        if (path == 'Document not found.') {
            Swal.fire({
                'icon': 'warning',
                'title': type == 'document' ? 'Ownership Document' : 'Attachments',
                'text': type == 'document' ? 'Document not found.' : 'Attachment not found.'
            })
        } else {
            window.open(path);
        }
    }

    const dateFormat = (date) => {
        return moment(date).format('MM/DD/YYYY');
    }

    const editPersonalDialog = ref(false);
    const editMainLivelihoodDialog = ref(false);
    const editFarmParcelDialog = ref(false);

    const form = useForm({
        id: '',
        lastname: '', 
        firstname: '', 
        middlename: '', 
        suffix: '', 
        gender: '',
        lot_block_no: '',
        street: '',
        brgy: '',
        city: '',
        province: '',
        region: '',
        mobile_no: '',
        date_of_birth: '', 
        place_of_birth: '', 
        religion: '',
        civil_status: '', 
        spouse_name_if_married: '',
        mothers_maiden_name: '',
        is_household_head: '',
        name_if_not_head: '',
        is_not_head_relationship: '',
        no_of_living_members: '',
        no_of_male: '',
        no_of_female: '',
        highest_formal_education: '',
        is_pwd: '',
        is_4ps: '',
        has_gov_id: '',
        id_no: '',
        is_farmer_coop_mem: '',
        is_farmer_mem: '',
        contact_emergency: '',
        contact_no: '',
        user_id: 0,
        submit_type: ''
    });

    const phoneFormat = helpers.regex(/^\(09\) \d{4}-\d{5}$/)

    let is_household_head = ref('');
    let gov_id = ref('');
    let is_mem = ref('');

    watch(() => form.is_household_head, (val) => {
        is_household_head.value = val == 0 ? true : false;
        
        if(is_household_head) {
            v$.value.name_if_not_head.$reset()
            v$.value.is_not_head_relationship.$reset()    
        }
        
        if(val == 0) {
            form.name_if_not_head = ''
            form.is_not_head_relationship = ''
        }
    })

    watch(() => form.has_gov_id, (val) => {
        gov_id.value = val == 1 ? true : false;
        
        v$.value.id_no.$reset()

        if (val == 0) {
            form.id_no = ''
        }
    })

    const personalRules = computed(() => {
        return {
            lastname: { required },
            firstname: { required },
            middlename: {},
            suffix: {},
            gender: { required },
            lot_block_no: { required },
            street: { required },
            brgy: { required },
            city: { required },
            province: { required },
            region: { required },
            mobile_no: { required, exactLength: minLength(11), phoneFormat },
            date_of_birth: { required },
            place_of_birth: { required },
            religion: {},
            civil_status: { required },
            spouse_name_if_married: {
                required: requiredIf(() =>
                    ['Married', 'Widowed'].includes(form.civil_status)
                )
            },
            mothers_maiden_name: { required },
            is_household_head: { required },
            name_if_not_head: {
                required: requiredIf(is_household_head)
            },
            is_not_head_relationship: {
                required: requiredIf(is_household_head)
            },
            no_of_living_members: { required },
            no_of_male: { required },
            no_of_female: { required },
            highest_formal_education: { required },
            is_pwd: { required },
            is_4ps: { required },
            has_gov_id: { required },
            id_no: {
                required: requiredIf(gov_id)
            },
            is_farmer_coop_mem: { required },
            is_farmer_mem: {
                required: requiredIf(is_mem)
            },
            contact_emergency: { required },
            contact_no: { required, exactLength: minLength(11), phoneFormat },
        }
    });

    const v$ = useVuelidate(personalRules, form, {
        $autoDirty: false
    })

    const { hasError, inputBorderClass, getFieldState } = useValidationHelpers(v$, form, { autoTouch: true })
    let contact = ref(null);
    let emergency = ref(null);

    const setPersonalFormType = (farmer) => {
        editPersonalDialog.value = true;
        const capitalizeFirst = str => str.charAt(0).toUpperCase() + str.slice(1);

        form.id = farmer.id;
        form.lastname = farmer.lastname; 
        form.firstname = farmer.firstname; 
        form.middlename = farmer.middlename; 
        form.suffix = farmer.suffix; 
        form.gender = farmer.gender;
        form.lot_block_no = farmer.lot_block_no;
        form.street = farmer.street;
        form.brgy = farmer.brgy;
        form.city = farmer.city;
        form.province = farmer.province;
        form.region = farmer.region;
        form.mobile_no = formatPhoneNumber(farmer.mobile_no);
        form.date_of_birth = dateFormat(farmer.date_of_birth); 
        form.place_of_birth = farmer.place_of_birth; 
        form.religion = farmer.religion;
        form.civil_status = capitalizeFirst(farmer.civil_status); 
        form.spouse_name_if_married = farmer.spouse_name_if_married;
        form.mothers_maiden_name = farmer.mothers_maiden_name;
        form.is_household_head = parseInt(farmer.is_household_head);
        form.name_if_not_head = farmer.name_if_not_head;
        form.is_not_head_relationship = farmer.is_not_head_relationship;
        form.no_of_living_members = farmer.no_of_living_members;
        form.no_of_male = farmer.no_of_male;
        form.no_of_female = farmer.no_of_female;
        form.highest_formal_education = farmer.highest_formal_education;
        form.is_pwd = farmer.is_pwd;
        form.is_4ps = farmer.is_4ps;
        form.has_gov_id = farmer.has_gov_id;
        form.id_no = farmer.id_no;
        form.is_farmer_coop_mem = farmer.is_farmer_coop_mem;
        form.is_farmer_mem = farmer.is_farmer_mem;
        form.contact_emergency = farmer.contact_emergency;
        form.contact_no = formatPhoneNumber(farmer.contact_no);

        setTimeout( function() {
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
        }, 350);
    }

    const memberNumber = (e) => {
        const male = form.no_of_male != '' ? parseInt(form.no_of_male) : 0;
        const female = form.no_of_female != '' ? parseInt(form.no_of_female) : 0;
        form.no_of_living_members = male + female;
    }

    const closePersonalEditModal = () => {
        editPersonalDialog.value = false;
    }

    const isMarried = ref(false);
    const civilType = (event) => {
        const selectedValue = event.id;

        isMarried.value = (selectedValue == 'Married' || selectedValue == 'Widowed') ? true : false;

        v$.value.spouse.$reset();
        form.spouse = ''
    }

    const datepicker = () => {
        $('#birth').daterangepicker({
            opens: 'left',
            locale: {
                format: 'MM/DD/YYYY',
            },
            singleDatePicker: true,
            showDropdowns: true,
            autoUpdateInput: false,
            parentEl: $("#editPersonalInfo")
        }).on('apply.daterangepicker', function(ev, picker){
            $(this).val(picker.startDate.format('MM/DD/YYYY'))

            form.date_of_birth = moment(picker.startDate.format('MM/DD/YYYY')).format('MM/DD/YYYY');
        });
    }

    function formatPhoneNumber(number) {
        const match = number ? number.match(/^09(\d{4})(\d{5})$/) : false;
        if (!match) return number;
        return `(09) ${match[1]}-${match[2]}`;
    }

    let processing = ref(false);
    let recentlySuccessful = ref(false);

    const submitEditPersonal = () => {
        const { id } = props.auth.user;

        processing.value = true;
        form.user_id = id;
        form.submit_type = 'personal';

        v$.value.$touch();
        if (!v$.value.$invalid) {
            form.put(route('farmers.update', form.id), {
                preserveScroll: true,
                onSuccess: () => {
                    const page = usePage();
                    const response = page.props.flash?.response;
                    processing.value = false;

                    setTimeout(() => { recentlySuccessful.value = false; }, 1500);
                    setTimeout(() => { closePersonalEditModal(); form.reset(); }, 800);

                    if (response.state) {
                        recentlySuccessful.value = true;

                        setTimeout(() => { recentlySuccessful.value = false; }, 1500);
                        setTimeout(() => { closePersonalEditModal(); form.reset(); v$.value.$reset(); }, 800);
                    } else {
                        recentlyFailed.value = true;
                        setTimeout(() => { recentlyFailed.value = false; }, 1500);
                    }
                },
                onError: (errors) => {
                    console.log(errors);
                }
            });
        } else {
            processing.value = false;
        }
    }

    const livelihoodForm = useForm({
        farmer_id: 0,
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
        farming_gross: 0,
        no_farming_gross: 0,
        submit_type: 'livelihood'
    })

    const LivelihoodRules = computed(() => {
        return {
            main_livelihood: { required, minLength: minLength(1) },
            farmer: { 
                required: requiredIf(() => livelihoodForm.main_livelihood.includes('farmer')),
                minLength: minLength(1)
            },
            crops: {
                required: requiredIf(() => livelihoodForm.farmer.includes('crops')),
                minLength: minLength(1)
            },
            livestock: {
                required: requiredIf(() => livelihoodForm.farmer.includes('livestock')),
                minLength: minLength(1)
            },
            poultry: {
                required: requiredIf(() => livelihoodForm.farmer.includes('poultry')),
                minLength: minLength(1)
            },
            farm_worker: { 
                required: requiredIf(() => livelihoodForm.main_livelihood.includes('farm_worker')),
                minLength: minLength(1) 
            },
            farm_worker_others: {
                required: requiredIf(() => livelihoodForm.farm_worker.includes('Others'))
            },
            fisherfolks: { 
                required: requiredIf(() => livelihoodForm.main_livelihood.includes('fisherfolks')),
                minLength: minLength(1) 
            },
            fisherfolks_others: {
                required: requiredIf(() => livelihoodForm.fisherfolks.includes('Others'))
            },
            agri_youth: { 
                required: requiredIf(() => livelihoodForm.main_livelihood.includes('agri_youth')),
                minLength: minLength(1) },
            agri_youth_others: {
                required: requiredIf(() =>  livelihoodForm.agri_youth.includes('Others'))
            },
            farming_gross: { required },
            no_farming_gross: { required },
        }
    });

    const y$ = useVuelidate(LivelihoodRules, livelihoodForm, {
        $autoDirty: false
    })

    const { hasError: hasLivelihoodError, inputBorderClass: livelihoodInputBorderClass, getFieldState: getLivelihoodFieldState } = useValidationHelpers(y$, livelihoodForm, { autoTouch: true })

    const setMainLivelihoodFormType = (farmer) => {
        editMainLivelihoodDialog.value = true;

        livelihoodForm.farmer_id = farmer.id;
        livelihoodForm.main_livelihood = Object.assign([], farmer.main_livelihood);
        livelihoodForm.farming_gross = farmer.farming_gross;
        livelihoodForm.no_farming_gross = farmer.no_farming_gross;
        livelihoodForm.farmer = [];
        livelihoodForm.farm_worker = [];
        livelihoodForm.fisherfolks = [];
        livelihoodForm.agri_youth = [];

        if(!isEmpty(farmer.main_livelihood_info.farmer)) {
            $.each(farmer.main_livelihood_info.farmer, function(index, item) {
                livelihoodForm.farmer.push(item.meta);

                if (isNumber(item.value)) {
                    if (item.meta == 'crops') {
                        livelihoodForm.crops.push(item.value);
                    }

                    if (item.meta == 'livestock') {
                        livelihoodForm.livestock.push(item.value);
                    }

                    if (item.meta == 'poultry') {
                        livelihoodForm.poultry.push(item.value);
                    }
                }
            });
        }

        if (!isEmpty(farmer.main_livelihood_info.farm_worker)) {
            $.each(farmer.main_livelihood_info.farm_worker, function(index, item) {
                livelihoodForm.farm_worker.push(item.meta);

                if (item.meta == 'Others') {
                    livelihoodForm.farm_worker_others = item.value;
                }
            });
        }

        if (!isEmpty(farmer.main_livelihood_info.fisherfolks)) {
            $.each(farmer.main_livelihood_info.fisherfolks, function(index, item) {
                livelihoodForm.fisherfolks.push(item.meta);

                if (item.meta == 'Others') {
                    livelihoodForm.fisherfolks_others = item.value;
                }
            });
        }

        if (!isEmpty(farmer.main_livelihood_info.agri_youth)) {
            $.each(farmer.main_livelihood_info.agri_youth, function(index, item) {
                livelihoodForm.agri_youth.push(item.meta);

                if (item.meta == 'Others') {
                    livelihoodForm.agri_others = item.value;
                }
            });
        }
    }

    const handleLivelihood = (e) => {
        const selectedValue = e.target.value;

        if (livelihoodForm.main_livelihood.includes(selectedValue)) {
            const index = livelihoodForm.main_livelihood.indexOf(selectedValue);
            livelihoodForm.main_livelihood.splice(index, 1);
            livelihoodForm[selectedValue] = [];

            if (selectedValue == 'farm_worker') { livelihoodForm['farm_worker_others'] = []; }
            if (selectedValue == 'fisherfolks') { livelihoodForm['fisherfolks_others'] = []; }
            if (selectedValue == 'agri_youth') { livelihoodForm['agri_others'] = []; }
        } else {
            livelihoodForm.main_livelihood.push(selectedValue);
        }
    }

    const handleFarmer = (e) => {
        const selectedValue = e.target.value;

        if (livelihoodForm.farmer.includes(selectedValue)) {
            const index = livelihoodForm.farmer.indexOf(selectedValue);
            livelihoodForm.farmer.splice(index, 1);

            const _index = mergeTypes.findIndex(item => item.text.toLowerCase() == 'rice' || item.text.toLowerCase() == 'corn');
            if (_index !== -1) {
                mergeTypes.splice(_index, 1);
            }
        } else {
            livelihoodForm.farmer.push(selectedValue);

            if(selectedValue.toLowerCase() == 'rice' || selectedValue.toLowerCase() == 'corn') {
                mergeTypes.push({ id: selectedValue, text: selectedValue.toUpperCase() })
                mergeTypes.sort((a, b) => a.text.localeCompare(b.text));
            }
        }
    }

    const handleCrops = (event) => {
        const selectedValue = parseInt(event.id);

        if (livelihoodForm.crops.includes(selectedValue)) {
            const index = livelihoodForm.crops.indexOf(selectedValue);
            livelihoodForm.crops.splice(index, 1);
        } else {
            livelihoodForm.crops.push(selectedValue);
        }
    }
    
    const handleLivestock = (event) => {
        const selectedValue = parseInt(event.id);

        if (livelihoodForm.livestock.includes(selectedValue)) {
            const index = livelihoodForm.livestock.indexOf(selectedValue);
            livelihoodForm.livestock.splice(index, 1);
        } else {
            livelihoodForm.livestock.push(selectedValue);
        }
    }
    
    const handlePoultry = (event) => {
        const selectedValue = parseInt(event.id);

        if (livelihoodForm.poultry.includes(selectedValue)) {
            const index = livelihoodForm.poultry.indexOf(selectedValue);
            livelihoodForm.poultry.splice(index, 1);
        } else {
            livelihoodForm.poultry.push(selectedValue);
        }
    }

    const handleFarmWorker = (e) => {
        const selectedValue = e.target.value;

        if (livelihoodForm.farm_worker.includes(selectedValue)) {
            const index = livelihoodForm.farm_worker.indexOf(selectedValue);
            livelihoodForm.farm_worker.splice(index, 1);
        } else {
            livelihoodForm.farm_worker.push(selectedValue);
        }
    }

    const handleFisherFolks = (e) => {
        const selectedValue = e.target.value;

        if (livelihoodForm.fisherfolks.includes(selectedValue)) {
            const index = livelihoodForm.fisherfolks.indexOf(selectedValue);
            livelihoodForm.fisherfolks.splice(index, 1);
        } else {
            livelihoodForm.fisherfolks.push(selectedValue);
        }
    }
    
    const handleAgriYouth = (e) => {
        const selectedValue = e.target.value;

        if (livelihoodForm.agri_youth.includes(selectedValue)) {
            const index = livelihoodForm.agri_youth.indexOf(selectedValue);
            livelihoodForm.agri_youth.splice(index, 1);
        } else {
            livelihoodForm.agri_youth.push(selectedValue);
        }
    }

    const closeLivelihoodEditModal = () => {
        editMainLivelihoodDialog.value = false;
    }

    const submitEditLivelihood = () => {
        const { id } = props.auth.user;

        processing.value = false;

        y$.value.$touch();
        if (!y$.value.$invalid) {
            livelihoodForm.put(route('farmers.update', livelihoodForm.farmer_id), {
                preserveScroll: true,
                onSuccess: () => {
                    const page = usePage();
                    const response = page.props.flash?.response;
                    processing.value = false;

                    setTimeout(() => { recentlySuccessful.value = false; }, 1500);
                    setTimeout(() => { closeLivelihoodEditModal(); form.reset(); }, 800);

                    if (response.state) {
                        recentlySuccessful.value = true;

                        setTimeout(() => { recentlySuccessful.value = false; }, 1500);
                        setTimeout(() => { closeLivelihoodEditModal(); livelihoodForm.reset(); y$.value.$reset(); }, 800);
                    } else {
                        recentlyFailed.value = true;
                        setTimeout(() => { recentlyFailed.value = false; }, 1500);
                    }
                },
                onError: (errors) => {
                    console.log(errors);
                }
            });
        } else {
            processing.value = false;
        }
    }

    const isEmpty = (arr) => {
        return jQuery.isEmptyObject(arr);
    }

    const isNumber = (val) => {
        return !isNaN(val);
    }

    const numberFormat = (val) => {
        const options = { 
            minimumFractionDigits: 2,    
            maximumFractionDigits: 2 
        };

        return val.toLocaleString('en', options);
    }

    const pageValue = ref(null);
    const searchValue = ref(null);
    const debouncedSearch = ref('');

    const pages = ref([ 10, 25, 50, 100, 200, 'All']);

    const handleSearch = proxy.$debounce((val) => {
        const { value } = pageValue;

        debouncedSearch.value = val;
        let formData = {};
        if (value) { formData.paginate = value };
        formData.search = debouncedSearch.value ? val : '';
        searchValue.value = debouncedSearch.value ? val : '';
        
        // router.visit('/farmers', {
        //     method: 'get',
        //     data: formData,
        //     preserveState: true,
        //     only: ['farmer', 'filter']
        // });
    }, 1000);

    watch(searchValue, (val) => {
        handleSearch(val)
    });

    const tableShow = () => {
        const { value } = pageValue;

        let formData = {};
        if (value) { formData.paginate = value };
        if (searchValue.value) { formData.search = searchValue.value; }

        // router.visit('/farmers', {
        //     method: 'get',
        //     data: formData,
        //     preserveState: true,
        //     only: ['farmer', 'filter']
        // });
    }

    const createAssistanceDialog = ref(false);
    const history_main_livelihood = ref([]);
    const availableAssistance = ref([]);
    const isCashAssist = ref(false)

    const historyForm = useForm({
        farmer_id: 0,
        assistance: '',
        livelihood: '',
        amount: '',
        remarks: '',
        user_id: 0,
    });

    const historyRules = computed(() => {
        return {
            assistance : { required },
            livelihood: { required },
            amount: { 
                required: requiredIf(isCashAssist)
            },
            remarks: { required },
            user_id: {}
        }
    });

    const history$ = useVuelidate(historyRules, historyForm, {
        $autoDirty: false
    })

    const { hasError: historyError, inputBorderClass: historyInputBorderClass } = useValidationHelpers(history$, historyForm)

    const closeAssistanceModal = () => {
        createAssistanceDialog.value = false;
        processing.value = false;
    }
    
    const assistanceLivelihood = () => {
        let temp = props.farmer.main_livelihood;

        $.each(main_livelihood.value, function(index, item) {
            if (temp.includes(item.value)) {
                const _temp = Object.assign({}, {id: item.value, text: item.label.toUpperCase() });
                history_main_livelihood.value.push(_temp);
            }
        });
    }

    const getAvailableAssistance = () => {
        let temp = props.farmer.main_livelihood;

        $.each(props.assistance, function(index, item) {
            $.each(item.livelihoods, function(i, v) {
                if (temp.includes(v)) {
                    const _temp = Object.assign({}, {id: item.id, text: item.name.toUpperCase() });
                    const check = availableAssistance.value.some(obj => obj.id === item.id);

                    if (!check) {
                        availableAssistance.value.push(_temp);
                    }
                }
            })
        }); 
    }

    assistanceLivelihood();
    // getAvailableAssistance();

    const handleAssistanceLivelihood = (e) => {
        availableAssistance.value = [];
        $.each(props.assistance, function(index, item) {
            $.each(item.livelihoods, function(i, v) {
                if (v === e.id) {
                    const _temp = Object.assign({}, {id: item.id, text: item.name.toUpperCase() });
                    availableAssistance.value.push(_temp);
                }
            })
        })
    }

    const handleAssistance = (e) => {
        const selectedValue  = e.text.toLowerCase();
        isCashAssist.value = selectedValue.includes('cash');
    }

    const submitNewAssistance = () => {
        const { id } = props.auth.user;

        historyForm.farmer_id = props.farmer.id;
        historyForm.user_id = id;

        history$.value.$touch();
        if (!history$.value.$invalid) {

            historyForm.post(route('assistance.save_assistance'), {
                preserveScroll: true,
                onProgress: () => processing.value = true,
                onSuccess: () => {
                    const page = usePage();
                    const response = page.props.flash?.response;
                    processing.value = false;

                    setTimeout(() => { recentlySuccessful.value = false; }, 1500);
                    setTimeout(() => { closeAssistanceModal(); form.reset(); }, 800);

                    if (response.state) {
                        recentlySuccessful.value = true;

                        setTimeout(() => { recentlySuccessful.value = false; }, 1500);
                        setTimeout(() => { 
                            closeAssistanceModal(); 

                            historyForm.reset(); 
                            history$.value.$reset(); 
                            isCashAssist.value = false;

                            historyForm.farmer_id = 0
                            historyForm.assistance = ''
                            historyForm.livelihood = ''
                            historyForm.amount = ''
                            historyForm.remarks = ''
                            historyForm.user_id =0
                            
                        }, 800);
                    } else {
                        recentlyFailed.value = true;
                        setTimeout(() => { recentlyFailed.value = false; }, 1500);
                    }
                },
                onError: (errors) => {
                    processing.value = false;
                    console.log(errors);
                }
            });
        }
    }

    const dateTimeFormat = (date) => {
        return moment(date).format('MMM. DD, YYYY hh:mm A');
    }

    const assistanceFormat = (val) => {
        const temp = props.allassistance;
        const index = temp.map(obj => obj.id).indexOf(val);
        const text = temp[index] !== 'undefined' ? temp[index].name : '---';
        
        return text;
    }

    const setFarmParcel = (farmer) => {
        editFarmParcelDialog = true;
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

                        <div class="flex flex-wrap justify-between mb-4">
                            <div class="w-6/12">
                                <p class="text-sm font-bold">Reference No:</p>
                            </div>
                            <div class="w-6/12">
                                <p class="text-sm text-right">{{ farmer.ref_no }}</p>
                            </div>
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
                                        <div class="w-3/12">
                                            <h3 class="font-bold text-md">Personal Information</h3>
                                        </div>
                                        <div class="w-3/12 text-right">
                                            <PrimaryButton class="bg-yellow-500 hover:bg-yellow-700 text-white" @click="setPersonalFormType(farmer)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
                                                <svg class="w-4 h-4 mr-1" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#fff">
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
                                                Edit
                                            </PrimaryButton>
                                        </div>
                                    </div>
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
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.middlename ? farmer.middlename : '&nbsp;' }}</p>
                                        </div>
                                        <div class="sm:w-full md:w-[17%]">
                                            <InputLabel for="extension" value="Extension" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.suffix ? farmer.suffix : '&nbsp;' }}</p>
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
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.lot_block_no ? farmer.lot_block_no : '&nbsp;' }}</p>
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
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.city ? farmer.city : '&nbsp;' }}</p>
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
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.date_of_birth ? dateFormat(farmer.date_of_birth) : '&nbsp;' }}</p>
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
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.no_of_living_members ? farmer.no_of_living_members : 0 }}</p>
                                            </div>
                                            <div class="sm:w-full md:w-[18%]">
                                                <InputLabel for="no-of-male" value="No. of Male" />
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.no_of_male > 0 ? farmer.no_of_male : 0 }}</p>
                                            </div>
                                            <div class="sm:w-full md:w-[18%]">
                                                <InputLabel for="no-of-female" value="No. of Female" />
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
                                    <div class="flex flex-wrap w-full mb-6">
                                        <div class="flex flex-wrap w-full justify-between mb-4">
                                            <div class="w-3/12">
                                                <h3 class="font-bold text-md">MAIN LIVELIHOOD</h3>
                                            </div>
                                            <div class="w-6/12 text-right">
                                                <PrimaryButton class="bg-yellow-500 hover:bg-yellow-700 text-white" @click="setMainLivelihoodFormType(farmer)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
                                                    <svg class="w-4 h-4 mr-1" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#fff">
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
                                                    Edit
                                                </PrimaryButton>
                                            </div>
                                        </div>
                                        <div class="w-full mx-auto">
                                            <div class="flex flex-wrap items-center justify-center sm:gap-x-6 md:gap-x-7 lg:gap-x-10 xl:gap-x-15 2xl:gap-x-32">
                                                <div v-for="option in main_livelihood" class="inline-flex items-center space-x-2" >
                                                    <TextInput type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" :checked="farmer.main_livelihood.includes(option.value)" disabled />
                                                    <InputLabel :for="option.value" :value="option.label" class="text-sm text-gray-700" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap w-full lg:justify-start xl:justify-center 2xl:justify-center items-stretch gap-x-3 mb-4">
                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="farmer.main_livelihood.includes('farmer') && farmer.main_livelihood_info['farmer'].length > 0"
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

                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="farmer.main_livelihood.includes('farm_worker') && farmer.main_livelihood_info['farm_worker'].length > 0"
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

                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="farmer.main_livelihood.includes('fisherfolks') && farmer.main_livelihood_info['fisherfolks'].length > 0"
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

                                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="farmer.main_livelihood.includes('agri_youth') && farmer.main_livelihood_info['agri_youth'].length > 0"
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
                                                        <!-- here -->
                                                        <p class="border rounded block p-2 w-full uppercase">{{ farmer.farming_gross > 0 ? numberFormat(farmer.farming_gross) : numberFormat(0) }}</p>
                                                    </div>
                                                    <div class="sm:w-full md:w-[49%]">
                                                        <InputLabel for="non-farming" value="Non-farming" />
                                                        <p class="border rounded block p-2 w-full uppercase">{{ farmer.no_farming_gross > 0 ? numberFormat(farmer.no_farming_gross) : numberFormat(0) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-6 border-t border-gray-300" />

                                    <div class="flex flex-wrap w-full justify-between mb-4">
                                        <div class="w-3/12">
                                            <h3 class="font-bold text-md">FARM PARCELS</h3>
                                        </div>
                                        <div class="w-6/12 text-right">
                                            <PrimaryButton class="bg-yellow-500 hover:bg-yellow-700 text-white" @click="setFarmParcel(farmer)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
                                                <svg class="w-4 h-4 mr-1" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#fff">
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
                                                Edit
                                            </PrimaryButton>
                                        </div>
                                    </div>

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
                                            <template v-if="farmer.farm_parcel.length > 0">
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
                                                            <div class="flex flex-wrap w-full mb-4">
                                                                <div class="w-full">
                                                                    <InputLabel for="ownership_doc" value="Ownership Document" />
                                                                    <div class="h-32 border rounded-lg p-6 mb-2 text-center uppercase" style="border: 1px solid rgba(0,0,0,.8) !important">
                                                                        <div @click="viewAttachment(item.document_path)" class="cursor-pointer">
                                                                            <svg class="h-14 w-14 mx-auto" version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000">
                                                                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                                                <g id="SVGRepo_iconCarrier"> 
                                                                                    <g> 
                                                                                        <path class="st0" d="M378.413,0H208.297h-13.182L185.8,9.314L57.02,138.102l-9.314,9.314v13.176v265.514 c0,47.36,38.528,85.895,85.896,85.895h244.811c47.353,0,85.881-38.535,85.881-85.895V85.896C464.294,38.528,425.766,0,378.413,0z M432.497,426.105c0,29.877-24.214,54.091-54.084,54.091H133.602c-29.884,0-54.098-24.214-54.098-54.091V160.591h83.716 c24.885,0,45.077-20.178,45.077-45.07V31.804h170.116c29.87,0,54.084,24.214,54.084,54.092V426.105z"></path> 
                                                                                        <path class="st0" d="M171.947,252.785h-28.529c-5.432,0-8.686,3.533-8.686,8.825v73.754c0,6.388,4.204,10.599,10.041,10.599 c5.711,0,9.914-4.21,9.914-10.599v-22.406c0-0.545,0.279-0.817,0.824-0.817h16.436c20.095,0,32.188-12.226,32.188-29.612 C204.136,264.871,192.182,252.785,171.947,252.785z M170.719,294.888h-15.208c-0.545,0-0.824-0.272-0.824-0.81v-23.23 c0-0.545,0.279-0.816,0.824-0.816h15.208c8.42,0,13.447,5.027,13.447,12.498C184.167,290,179.139,294.888,170.719,294.888z"></path> 
                                                                                        <path class="st0" d="M250.191,252.785h-21.868c-5.432,0-8.686,3.533-8.686,8.825v74.843c0,5.3,3.253,8.693,8.686,8.693h21.868 c19.69,0,31.923-6.249,36.81-21.324c1.76-5.3,2.723-11.681,2.723-24.857c0-13.175-0.964-19.557-2.723-24.856 C282.113,259.034,269.881,252.785,250.191,252.785z M267.856,316.896c-2.318,7.331-8.965,10.459-18.21,10.459h-9.23 c-0.545,0-0.824-0.272-0.824-0.816v-55.146c0-0.545,0.279-0.817,0.824-0.817h9.23c9.245,0,15.892,3.128,18.21,10.46 c0.95,3.128,1.62,8.56,1.62,17.93C269.476,308.336,268.805,313.768,267.856,316.896z"></path> 
                                                                                        <path class="st0" d="M361.167,252.785h-44.812c-5.432,0-8.7,3.533-8.7,8.825v73.754c0,6.388,4.218,10.599,10.055,10.599 c5.697,0,9.914-4.21,9.914-10.599v-26.351c0-0.538,0.265-0.81,0.81-0.81h26.086c5.837,0,9.23-3.532,9.23-8.56 c0-5.028-3.393-8.553-9.23-8.553h-26.086c-0.545,0-0.81-0.272-0.81-0.817v-19.425c0-0.545,0.265-0.816,0.81-0.816h32.733 c5.572,0,9.245-3.666,9.245-8.553C370.411,256.45,366.738,252.785,361.167,252.785z"></path> 
                                                                                    </g> 
                                                                                </g>
                                                                            </svg>
                                                                            <p>{{ item.document }}</p>
                                                                        </div>
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
                                            </template>
                                            <template v-else>
                                                <div class="text-center mt-4">
                                                    No Farm Parcel Found.
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template #other-information>
                                <div class="p-3">
                                    <div class="flex flex-wrap w-full mb-6">
                                        <div class="flex flex-wrap w-full justify-between mb-4">
                                            <div class="w-3/12">
                                                <h3 class="font-bold text-md">Uploaded Files</h3>
                                            </div>
                                            <div class="w-6/12 text-right">
                                                <PrimaryButton class="bg-yellow-500 hover:bg-yellow-700 text-white" @click="addAttachments(farmer)" style="padding-left: 0.75rem !important; padding-right: 0.75rem !important;">
                                                    <svg class="w-4 h-4 mr-1" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#fff">
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
                                                    Edit
                                                </PrimaryButton>
                                            </div>
                                        </div>
                                        <div class="w-full">
                                            <table class="w-full text-sm text-left text-gray-500">
                                                <thead class="text-xs text-gray-700 uppercase">
                                                    <tr>
                                                        <th class="px-4 py-3">Filename</th>
                                                        <th class="px-4 py-3"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template v-if="farmer.attachments.length > 0">
                                                        <template v-for="(item, index) in farmer.attachments" :key="index">
                                                            <tr class="bg-white border-b">
                                                                <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">{{ item.filename }}</td>
                                                                <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap text-right">
                                                                    <svg @click="viewAttachment('attachments', item.filepath)" class="cursor-pointer h-10 w-10 mx-auto" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                                        <g id="SVGRepo_iconCarrier"> 
                                                                            <path d="M15.0007 12C15.0007 13.6569 13.6576 15 12.0007 15C10.3439 15 9.00073 13.6569 9.00073 12C9.00073 10.3431 10.3439 9 12.0007 9C13.6576 9 15.0007 10.3431 15.0007 12Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> 
                                                                            <path d="M12.0012 5C7.52354 5 3.73326 7.94288 2.45898 12C3.73324 16.0571 7.52354 19 12.0012 19C16.4788 19 20.2691 16.0571 21.5434 12C20.2691 7.94291 16.4788 5 12.0012 5Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                        </g>
                                                                    </svg>
                                                                </td>
                                                            </tr>
                                                        </template>
                                                    </template>
                                                    <template v-else>
                                                        <tr class="bg-white border-b">
                                                            <td colspan="2" id="no-data-found" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap text-center uppercase">No Data Found.</td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap justify-center mb-4">
                                        <div class="w-3/12">
                                            <InputLabel for="firstname" value="Form Date" />
                                            <p class="border rounded block p-2 w-full uppercase">{{ farmer.paper_date ? dateFormat(farmer.paper_date) : 'No Form Date' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap justify-center">
                                        <div class="w-full mb-3">
                                            <h3 class="font-bold text-md">Verified true and Corrected by:</h3>
                                        </div>

                                        <div class="flex flex-wrap justify-between">
                                            <div class="w-[32%]">
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.official ? farmer.official : 'No Official Name' }}</p>
                                                <InputLabel class="uppercase" value="Barangay Chairman / City / Mun. Veterinarian (livestock) / Mill District Office (Sugarcane) / IP Leader / c/m/Paro (arb)" />
                                            </div>
                                            <div class="w-[32%]">
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.muni_city_official ? farmer.muni_city_official : 'No Official Name' }}</p>
                                                <InputLabel class="uppercase" value="City / Municipal Agriculture Office" />
                                            </div>
                                            <div class="w-[32%]">
                                                <p class="border rounded block p-2 w-full uppercase">{{ farmer.cafc_chairman ? farmer.cafc_chairman : 'No Official Name' }}</p>
                                                <InputLabel class="uppercase" value="CAFC / MAFC Chairmain" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template #assistance>
                                <div class="p-3">
                                    <div class="w-full">
                                        <div class="flex flex-row justify-between align-center">
                                            <div class="md:w-1/6">
                                                <PrimaryButton class="bg-blue-500 hover:bg-blue-700 text-white px-2 py-3 mr-3" @click="createAssistanceDialog = true">
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
                                        <table class="w-full text-sm text-left text-gray-500">
                                            <thead class="text-xs text-gray-700 uppercase">
                                                <tr>
                                                    <th class="px-4 py-3">Assistance</th>
                                                    <th class="px-4 py-3">Livelihood</th>
                                                    <th class="px-4 py-3">Amount</th>
                                                    <th class="px-4 py-4">Remarks</th>
                                                    <th class="px-4 py-3">Created By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template v-if="history.total > 0">
                                                    <template v-for="(item, index) in history.data" :key="index">
                                                        <tr class="bg-white border-b">
                                                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">{{ assistanceFormat(item.assistance_id) }}</td>
                                                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">{{ item.livelihood }}</td>
                                                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">{{ item.amount ? item.amount : ' --- '}}</td>
                                                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">{{ item.remarks }}</td>
                                                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">
                                                                <p class="font-semibold">{{ item.created_name }}</p>
                                                                <small>{{ dateTimeFormat(item.created_at) }}</small>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </template>
                                                <template v-else-if="history.length > 0">
                                                    <template v-for="(item, index) in history" :key="index">
                                                        <tr class="bg-white border-b">
                                                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">{{ assistanceFormat(item.assistance_id) }}</td>
                                                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">{{ item.livelihood }}</td>
                                                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">{{ item.amount ? item.amount : ' --- ' }}</td>
                                                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">{{ item.remarks }}</td>
                                                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">
                                                                <p class="font-semibold">{{ item.created_name }}</p>
                                                                <small>{{ dateTimeFormat(item.created_at) }}</small>
                                                            </td>
                                                        </tr>
                                                    </template>
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
                                                        <li v-for="(link, index) in history.links" :key="index">
                                                            <template v-if="index == '0'">
                                                                <Link :href="link.url || '#'" :key="link.label" v-html="link.label" class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700" :class="{ 'text-gray-500 pointer-events-none': !link.url }" />
                                                            </template>
                                                            <template v-else-if="index == history.links.length - 1">
                                                                <Link :href="link.url || '#'" :key="link.label" v-html="link.label" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700" :class="{ 'text-gray-500 pointer-events-none': !link.url }" />
                                                            </template>
                                                            <template v-else>
                                                                <Link :href="link.url || '#'" :key="link.label" v-html="link.label" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700" :class="{ 'text-gray-500 pointer-events-none': !link.url }" />
                                                            </template>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </FarmerTabs>
                    </div>
                </div>
            </div>
        </div>

        <DialogModal id="editPersonalInfo" :show="editPersonalDialog" :max-width="'5xl'" @close="closePersonalEditModal">
            <template #title>
                Edit Personal Information
            </template>
            <template #content>
                <div>
                    <div class="sm:w-full">
                        <div class="flex flex-wrap justify-between mb-4">
                            <div class="sm:w-full md:w-[49%]">
                                <InputLabel for="firstname" value="First name" :required="true" />
                                <TextInput type="text" v-model="form.firstname" class="mt-1 block w-full uppercase" autocomplete="off"
                                    @blur="v$.firstname.$touch()"
                                    :class="!v$.firstname.$dirty && form.firstname ? 'border-gray-300' : inputBorderClass('firstname')"
                                />
                                <p v-if="hasError('firstname')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.firstname.required?.$invalid">First name is required.</span>
                                </p>
                            </div>
                            <div class="sm:w-full md:w-[49%]">
                                <InputLabel for="lastname" value="Last name" :required="true" />
                                <TextInput type="text" v-model="form.lastname" class="mt-1 block w-full uppercase" autocomplete="off"
                                    @blur="v$.lastname.$touch()"
                                    :class="!v$.lastname.$dirty && form.lastname ? 'border-gray-300' : inputBorderClass('lastname')"
                                />
                                <p v-if="hasError('lastname')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.lastname.required?.$invalid">Last name is required.</span>
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
                                    <label class="sm:w-[49%] md:w-[49%] lg:w-[40%] 2xl:w-[39%] flex items-center space-x-2 cursor-pointer">
                                        <input type="radio" name="gender" v-model="form.gender" value="male" class="accent-blue-600" />
                                        <span class="text-gray-700">Male</span>
                                    </label>

                                    <label class="sm:w-[49%] md:w-[49%] lg:w-[40%] 2xl:w-[40%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                        <input type="radio" name="gender" v-model="form.gender" value="female" class="accent-blue-600" />
                                        <span class="text-gray-700">Female</span>
                                    </label>
                                </div>
                                <span class="text-red-500 text-sm" v-if="v$.gender.$error">Gender is required.</span>
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
                                <InputLabel for="house" value="House / Lot / Bldg. No." :required="true" />
                                <TextInput type="text" v-model="form.lot_block_no" class="mt-1 block w-full uppercase" autocomplete="off" 
                                    @blur="v$.lot_block_no.$touch()"
                                    :class="!v$.lot_block_no.$dirty && form.lot_block_no ? 'border-gray-300' : inputBorderClass('lot_block_no')"
                                />
                                <p v-if="hasError('lot_block_no')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.lot_block_no.required?.$invalid">House / Lot is required.</span>
                                </p>
                            </div>
                            <div class="md:w-[32.10%] sm:w-full">
                                <InputLabel for="street" value="Street / Sitio / Subdv." :required="true" />
                                <TextInput type="text" v-model="form.street" class="mt-1 block w-full uppercase" autocomplete="off" 
                                    @blur="v$.street.$touch()"
                                    :class="!v$.street.$dirty && form.street ? 'border-gray-300' : inputBorderClass('street')"
                                />
                                <p v-if="hasError('street')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.street.required?.$invalid">Street / Sitio / Subdv. is required.</span>
                                </p>
                            </div>
                            <div class="md:w-[32.10%] sm:w-full">
                                <InputLabel for="barangay" value="Barangay" :required="true" />
                                <TextInput type="text" v-model="form.brgy" class="mt-1 block w-full uppercase" autocomplete="off"
                                    @blur="v$.brgy.$touch()"
                                    :class="!v$.brgy.$dirty && form.brgy ? 'border-gray-300' : inputBorderClass('brgy')"
                                />
                                <p v-if="hasError('brgy')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.brgy.required?.$invalid">Barangay is required.</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-start justify-between">
                            <div class="md:w-[39.10%] sm:w-full">
                                <InputLabel for="municipality" value="Municipality / City" :required="true" />
                                <TextInput type="text" v-model="form.city" class="mt-1 block w-full uppercase" autocomplete="off" 
                                    @blur="v$.city.$touch()"
                                    :class="!v$.city.$dirty && form.city ? 'border-gray-300' : inputBorderClass('city')"
                                />
                                <p v-if="hasError('muni_city')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.muni_city.required?.$invalid">Municipality / City is required.</span>
                                </p>
                            </div>
                            <div class="md:w-[39.10%] sm:w-full">
                                <InputLabel for="province" value="Province" :required="true" />
                                <TextInput type="text" v-model="form.province" class="mt-1 block w-full uppercase" autocomplete="off" 
                                    @blur="v$.province.$touch()"
                                    :class="!v$.province.$dirty && form.province ? 'border-gray-300' : inputBorderClass('province')"
                                />
                                <p v-if="hasError('province')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.province.required?.$invalid">Province is required.</span>
                                </p>
                            </div>
                            <div class="md:w-[18%] sm:w-full">
                                <InputLabel for="region" value="Region" :required="true" />
                                <TextInput type="text" v-model="form.region" class="mt-1 block w-full uppercase" autocomplete="off" 
                                    @blur="v$.region.$touch()"
                                    :class="!v$.region.$dirty && form.region ? 'border-gray-300' : inputBorderClass('region')"
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
                                <TextInput type="text" class="mt-1 block w-full uppercase" id="contact" v-model="form.mobile_no" autocomplete="off" 
                                    @blur="v$.mobile_no.$touch()"
                                    :class="!v$.mobile_no.$dirty && form.mobile_no ? 'border-gray-300' : inputBorderClass('mobile_no')"
                                />
                                <p v-if="hasError('mobile_no')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.mobile_no.required?.$invalid">Contact No. is required.</span>
                                    <span class="text-red-500 text-sm" v-if="v$.mobile_no.phoneFormat?.$invalid">Invalid phone format. Use (09) 1234-56789</span>
                                </p>
                            </div>
                            <div class="md:w-[24%] sm:w-full">
                                <InputLabel for="birth" value="Date of Birth" :required="true" />
                                <TextInput type="text" class="mt-1 block w-full uppercase" id="birth" placeholder="MM/DD/YYYY" v-model="form.date_of_birth" autocomplete="off" 
                                    @blur="v$.date_of_birth.$touch()"
                                    :class="!v$.date_of_birth.$dirty && form.date_of_birth ? 'border-gray-300' : inputBorderClass('date_of_birth')"
                                />
                                <p v-if="hasError('date_of_birth')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.date_of_birth.required?.$invalid">Date of Birth is required.</span>
                                </p>
                            </div>
                            <div class="md:w-[24%] sm:w-full">
                                <InputLabel for="birth-place" value="Place of Birth" :required="true" />
                                <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.place_of_birth" autocomplete="off"
                                    @blur="v$.place_of_birth.$touch()"
                                    :class="!v$.place_of_birth.$dirty && form.place_of_birth ? 'border-gray-300' : inputBorderClass('place_of_birth')"
                                />
                                <p v-if="hasError('place_of_birth')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.place_of_birth.required?.$invalid">Place of Birth is required.</span>
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
                                    <Select2 class="h-10 uppercase" v-model="form.civil_status" :options="civil_status" :settings="{ placeholder: 'Select An Option', width: '100%', dropdownParent: $('#editPersonalInfo') }" @select="civilType" />
                                    <span class="text-red-500 text-sm" v-if="v$.civil_status.$error">Civil Status is required.</span>
                                </div>
                                <div class="mt-4" v-if="isMarried">
                                    <InputLabel for="spouse-name" value="Name of Spouse" :required="true" />
                                    <TextInput type="text" v-model="form.spouse_name_if_married" class="mt-1 block w-full uppercase" autocomplete="off" 
                                        @blur="v$.spouse_name_if_married.$touch()"
                                        :class="!v$.spouse_name_if_married.$dirty && form.spouse_name_if_married ? 'border-gray-300' : inputBorderClass('spouse_name_if_married')"
                                    />
                                    <p v-if="hasError('spouse_name_if_married')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.spouse_name_if_married.required?.$invalid">Name of Spouse is required.</span>
                                    </p>
                                </div>
                            </div>
                            <div class="md:w-[32%] sm:w-full">
                                <InputLabel for="mother-name" value="Mothers' Maiden Name" :required="true" />
                                <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.mothers_maiden_name" autocomplete="off" 
                                    @blur="v$.mothers_maiden_name.$touch()"
                                    :class="!v$.mothers_maiden_name.$dirty && form.mothers_maiden_name ? 'border-gray-300' : inputBorderClass('mothers_maiden_name')"
                                />
                                <p v-if="hasError('mothers_maiden_name')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.mothers_maiden_name.required?.$invalid">Mothers' Maiden Name is required.</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-x-4 items-start justify-center mb-4">
                            <div class="w-full">
                                <div class="flex items-center gap-6 mt-3">
                                    <InputLabel for="household-head" value="Household Head?" :required="true" class="mb-0" />

                                    <div class="flex items-center gap-4">
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="radio" name="is_household_head" v-model="form.is_household_head" :value="1" class="accent-blue-600" />
                                            <span class="text-gray-700">Yes</span>
                                        </label>

                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="radio" name="is_household_head" v-model="form.is_household_head" :value="0" class="accent-blue-600" />
                                            <span class="text-gray-700">No</span>
                                        </label>
                                    </div>
                                </div>
                                <p v-if="hasError('is_household_head')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.is_household_head.required?.$invalid">Household head is required.</span>
                                </p>
                            </div>
                        </div>

                        <div class="sm:w-full md:w-10/12 lg:w-8/12 xl:w-6/12 2xl:w-6/12 mx-auto mb-4" v-if="form.is_household_head == 0">
                            <div class="flex flex-wrap align-start justify-between">
                                <div class="sm:w-full md:w-[49%]">
                                    <InputLabel for="household-head" value="Name of Household Head" :required="true" />
                                    <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.name_if_not_head"
                                        @blur="v$.name_if_not_head.$touch()"
                                        :class="!v$.name_if_not_head.$dirty && form.name_if_not_head ? 'border-gray-300' : inputBorderClass('name_if_not_head')"
                                    />
                                    <p v-if="hasError('name_if_not_head')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.name_if_not_head.required?.$invalid">Name of Household head is required.</span>
                                    </p>
                                </div>
                                <div class="sm:w-full md:w-[49%]">
                                    <InputLabel for="relationship" value="Relationship" :required="true" />
                                    <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.is_not_head_relationship" 
                                        @blur="v$.is_not_head_relationship.$touch()"
                                        :class="!v$.is_not_head_relationship.$dirty && form.is_not_head_relationship ? 'border-gray-300' : inputBorderClass('is_not_head_relationship')"
                                    />
                                    <p v-if="hasError('is_not_head_relationship')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.is_not_head_relationship.required?.$invalid">Relationship is required.</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="sm:w-full md:w-full lg:w-12/12 xl:w-11/12 2xl:w-10/12 mx-auto mb-4">
                            <div class="flex flex-wrap items-start justify-between">
                                <div class="sm:w-full md:w-[32%]">
                                    <InputLabel for="living-household-members" value="No. of living household members" :required="true" />
                                    <TextInput type="number" v-model="form.no_of_living_members" min="0" class="mt-1 block w-full uppercase" autocomplete="off"
                                        @blur="v$.no_of_living_members.$touch()"
                                        :class="!v$.no_of_living_members.$dirty && form.no_of_living_members ? 'border-gray-300' : inputBorderClass('no_of_living_members')"
                                    />
                                    <p v-if="hasError('no_of_living_members')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.no_of_living_members.required?.$invalid">No. of members is required.</span>
                                    </p>
                                </div>
                                <div class="sm:w-full md:w-[32%]">
                                    <InputLabel for="no-of-male" value="No. of Male" :required="true" />
                                    <TextInput type="number" v-model="form.no_of_male" min="0" class="mt-1 block w-full uppercase" @input="memberNumber" autocomplete="off"
                                        @blur="v$.no_of_male.$touch()"
                                        :class="!v$.no_of_male.$dirty && form.no_of_male ? 'border-gray-300' : inputBorderClass('no_of_male')"
                                    />
                                    <p v-if="hasError('no_of_male')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.no_of_male.required?.$invalid">No. of Male is required.</span>
                                    </p>
                                </div>
                                <div class="sm:w-full md:w-[32%]">
                                    <InputLabel for="no-of-female" value="No. of Female" :required="true" />
                                    <TextInput type="number" v-model="form.no_of_female" min="0" class="mt-1 block w-full uppercase" @input="memberNumber" autocomplete="off"
                                        @blur="v$.no_of_female.$touch()"
                                        :class="!v$.no_of_female.$dirty && form.no_of_female ? 'border-gray-300' : inputBorderClass('no_of_female')"
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
                                    <Select2 class="h-10 uppercase" v-model="form.highest_formal_education" :options="education" :settings="{ placeholder: 'Select An Option', width: '100%', dropdownParent: $('#editPersonalInfo') }" />
                                    <p v-if="hasError('highest_formal_education')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.highest_formal_education.required?.$invalid">Education is required.</span>
                                    </p>
                                </div>
                            </div>
                            <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[30%] md:mb-4">
                                <InputLabel for="pwd" value="Person with Disability (PWD)" :required="true" />
                                <div class="flex flex-wrap items-center mt-3">
                                    <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2 cursor-pointer">
                                        <input type="radio" name="is_pwd" v-model="form.is_pwd" :value="1" class="accent-blue-600" />
                                        <span class="text-gray-700">Yes</span>
                                    </label>

                                    <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                        <input type="radio" name="is_pwd" v-model="form.is_pwd" :value="0" class="accent-blue-600" />
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
                                        <input type="radio" name="is_4ps" v-model="form.is_4ps" :value="1" class="accent-blue-600" />
                                        <span class="text-gray-700">Yes</span>
                                    </label>

                                    <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                        <input type="radio" name="is_4ps" v-model="form.is_4ps" :value="0" class="accent-blue-600" />
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
                                        <input type="radio" name="has_gov_id" v-model="form.has_gov_id" :value="1" class="accent-blue-600" />
                                        <span class="text-gray-700">Yes</span>
                                    </label>

                                    <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                        <input type="radio" name="has_gov_id" v-model="form.has_gov_id" :value="0" class="accent-blue-600" />
                                        <span class="text-gray-700">No</span>
                                    </label>
                                </div>
                                <p v-if="hasError('has_gov_id')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.has_gov_id.required?.$invalid">Government ID is required.</span>
                                </p>
                                <div class="mt-4" v-if="form.has_gov_id == 1">
                                    <InputLabel for="specify_id" value="Specify ID number" :required="true" />

                                    <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.id_no" autocomplete="off" 
                                        @blur="v$.id_no.$touch()"
                                        :class="!v$.id_no.$dirty && form.id_no ? 'border-gray-300' : inputBorderClass('id_no')"
                                    />
                                    <p v-if="hasError('id_no')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.id_no.required?.$invalid">Government ID is required.</span>
                                    </p>
                                </div>
                            </div>
                            <div class="sm:w-full md:w-[49%] lg:w-[40%] xl:w-[32%] 2xl:w-[40%] md:mb-4">
                                <InputLabel for="gov-id" value="Member of any Farmers Association / Cooperative?" :required="true" />
                                <div class="flex flex-wrap items-center mt-3">
                                    <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center space-x-2 cursor-pointer">
                                        <input type="radio" name="is_farmer_coop_mem" v-model="form.is_farmer_coop_mem" :value="1" class="accent-blue-600" />
                                        <span class="text-gray-700">Yes</span>
                                    </label>

                                    <label class="md:w-[30%] lg:w-[26%] xl:w-[24%] 2xl:w-[20%] flex items-center m-y-0 space-x-2 cursor-pointer">
                                        <input type="radio" name="is_farmer_coop_mem" v-model="form.is_farmer_coop_mem" :value="0" class="accent-blue-600" />
                                        <span class="text-gray-700">No</span>
                                    </label>
                                </div>
                                <p v-if="hasError('is_farmer_coop_mem')" class="text-red-500 text-sm">
                                    <span class="text-red-500 text-sm" v-if="v$.is_farmer_coop_mem.required?.$invalid">Farmers Association is required.</span>
                                </p>

                                <div class="mt-4" v-if="form.is_farmer_coop_mem == 1">
                                    <InputLabel for="specify_farmer_asso" value="Specify" :required="true" />
                                    <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.is_farmer_mem" autocomplete="off" 
                                        @blur="v$.is_farmer_mem.$touch()"
                                        :class="!v$.is_farmer_mem.$dirty && form.is_farmer_mem ? 'border-gray-300' : inputBorderClass('is_farmer_mem')"
                                    />
                                    <p v-if="hasError('is_farmer_mem')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.is_farmer_mem.required?.$invalid">Specify Farmers Association is required.</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6 border-t border-gray-500" />

                        <div class="sm:w-full md:w-10/12 lg:w-10/12 xl:w-8/12 2xl:w-8/12 mx-auto mb-4">
                            <div class="flex flex-wrap items-start justify-between">
                                <div class="sm:w-full md:w-[49%]">
                                    <InputLabel for="person-emergency" value="Person to notify in case of Emergency" :required="true" />
                                    <TextInput type="text" class="mt-1 block w-full uppercase" autocomplete="off" v-model="form.contact_emergency"
                                        @blur="v$.contact_emergency.$touch()"
                                        :class="!v$.contact_emergency.$dirty && form.contact_emergency ? 'border-gray-300' : inputBorderClass('contact_emergency')"
                                    />
                                    <p v-if="hasError('contact_emergency')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.contact_emergency.required?.$invalid">Emergency Person is required.</span>
                                    </p>
                                </div>
                                <div class="sm:w-full md:w-[49%]">
                                    <InputLabel for="contact-emergency-no" value="Contact No." :required="true" />
                                    <TextInput type="text" class="mt-1 block w-full uppercase" id="contact-emergency" v-model="form.contact_no" autocomplete="off" 
                                        @blur="v$.contact_no.$touch()"
                                        :class="!v$.contact_no.$dirty && form.contact_no ? 'border-gray-300' : inputBorderClass('contact_no')"
                                    />
                                    <p v-if="hasError('contact_no')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="v$.contact_no.required?.$invalid">Emergency Contact No. is required.</span>
                                        <span class="text-red-500 text-sm" v-if="v$.contact_no.phoneFormat?.$invalid">Invalid phone format. Use (09) 1234-56789</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <ActionMessage :on="recentlySuccessful" class="me-3">
                    Personal Information successfully updated.
                </ActionMessage>
                <PrimaryButton class="bg-blue-500 hover:bg-blue-700 text-white me-2" :class="{ 'opacity-25': processing }" 
                    :disabled="processing" @click="submitEditPersonal">Save</PrimaryButton>
                <SecondaryButton @click="closePersonalEditModal">Close</SecondaryButton>
            </template>
        </DialogModal>

        <DialogModal id="editMainLivelihood" :show="editMainLivelihoodDialog" :max-width="'5xl'" @close="closeLivelihoodEditModal">
            <template #title>
                Edit Main Livelihood
            </template>
            <template #content>
                <div>
                    <div class="sm:w-full">
                        <div class="flex flex-wrap mb-4 justify-center">
                            <div class="sm:w-full md:w-11/12 lg:w-11/12 xl:w-10/12 2xl:w-12/12">
                                <div class="flex flex-wrap items-center justify-center sm:gap-x-6 md:gap-x-7 lg:gap-x-10 xl:gap-x-15 2xl:gap-x-32">
                                    <div v-for="option in _main_livelihood" :key="option.value" class="inline-flex items-center space-x-2" >
                                        <TextInput type="checkbox" :id="option.value" :value="option.value" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleLivelihood" :checked="livelihoodForm.main_livelihood.includes(option.value)" />
                                        <InputLabel :for="option.value" :value="option.label" class="text-sm text-gray-700 cursor-pointer" />
                                    </div>
                                </div>
                                <p v-if="hasLivelihoodError('main_livelihood')" class="text-red-500 text-sm text-center">
                                    <span class="text-red-500 text-sm" v-if="y$.main_livelihood.required?.$invalid">Main Livelihood is required. Select atleast 1 livelihood.</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap lg:justify-start xl:justify-center 2xl:justify-center items-stretch gap-x-3 mb-6">
                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="livelihoodForm.main_livelihood.includes('farmer')"
                                :class="{
                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : livelihoodForm.main_livelihood.length >= 1 && livelihoodForm.main_livelihood.length <= 2,
                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : livelihoodForm.main_livelihood.length == 3,
                                    'md:w-[49%] lg:w-[32%] xl:w-[30%] 2xl:w-[24%]' : livelihoodForm.main_livelihood.length == 4
                                }"
                            >
                                <div class="p-4 lg:p-6 bg-white">
                                    <h4 class="text-center font-bold italic text-lg mb-3">For Farmers:</h4>

                                    <h5 class="font-bold text-md mb-2">Type of Farming Activity</h5>
                                    <div class="flex flex-wrap">
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.farmer.includes('rice')" value="rice" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmer" />
                                                <InputLabel for="rice" value="Rice" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>
                                        </div>
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.farmer.includes('corn')" value="corn" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmer" />
                                                <InputLabel for="corn" value="Corn" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>
                                        </div>
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" id="crops" :checked="livelihoodForm.farmer.includes('crops')" value="crops" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmer" />
                                                <InputLabel for="crops" value="Other crops" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>

                                            <div class="flex flex-wrap items-center ms-4" v-if="livelihoodForm.farmer.includes('crops')">
                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                <div class="rounded-md block mt-1 w-full">
                                                    <Select2 class="uppercase" v-model="livelihoodForm.crops" :options="types.crops" :settings="{ placeholder: 'Select An Option', width: '100%', multiple: true, dropdownParent: $('#editMainLivelihood') }" />
                                                </div>

                                                <p v-if="hasLivelihoodError('crops')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="y$.crops.required?.$invalid">Crops is required. Please select atleast 1 crop.</span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" id="livestock" :checked="livelihoodForm.farmer.includes('livestock')" value="livestock" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmer" />
                                                <InputLabel for="livestock" value="Livestock" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>

                                            <div class="flex flex-wrap items-center ms-4" v-if="livelihoodForm.farmer.includes('livestock')">
                                                <InputLabel for="livestock-specify" value="Specify: " class="me-4" />
                                                <div class="rounded-md block mt-1 w-full">
                                                    <Select2 class="uppercase" v-model="livelihoodForm.livestock" :options="types.livestock" :settings="{ placeholder: 'Select An Option', width: '100%', multiple: true, dropdownParent: $('#editMainLivelihood') }" />
                                                </div>
                                                <p v-if="hasLivelihoodError('livestock')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="y$.livestock.required?.$invalid">Crops is required. Please select atleast 1 livestock.</span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="w-full">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" id="poultry" v-model="livelihoodForm.poultry" :checked="livelihoodForm.farmer.includes('poultry')" value="poultry" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmer" />
                                                <InputLabel for="poultry" value="Poultry" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>

                                            <div class="flex flex-wrap items-center ms-4" v-if="livelihoodForm.farmer.includes('poultry')">
                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                <div class="rounded-md block mt-1 w-full">
                                                    <Select2 class="uppercase" v-model="livelihoodForm.poultry" :options="types.poultry" :settings="{ placeholder: 'Select An Option', width: '100%', multiple: true, dropdownParent: $('#editMainLivelihood') }" />
                                                </div>
                                                <p v-if="hasLivelihoodError('poultry')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="y$.poultry.required?.$invalid">Poultry is required. Please select atleast 1 poultry.</span>
                                                </p>
                                            </div>
                                        </div>

                                        <p v-if="hasLivelihoodError('farmer')" class="text-red-500 text-sm">
                                            <span class="text-red-500 text-sm" v-if="y$.farmer.required?.$invalid">Farming Activity is required. Please select atleast 1.</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="livelihoodForm.main_livelihood.includes('farm_worker')"
                                :class="{
                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : livelihoodForm.main_livelihood.length >= 1 && livelihoodForm.main_livelihood.length <= 2,
                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : livelihoodForm.main_livelihood.length == 3,
                                    'md:w-[49%] lg:w-[28%] xl:w-[28%] 2xl:w-[22%]' : livelihoodForm.main_livelihood.length == 4
                                }"
                            >
                                <div class="p-4 lg:p-6 bg-white">
                                    <h4 class="text-center font-bold italic text-lg mb-3">For Farmworkers:</h4>

                                    <h5 class="font-bold text-md mb-2">Kind of Work</h5>

                                    <div class="flex flex-wrap">
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.farm_worker.includes('Land Preparation')" value="Land Preparation" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmWorker" />
                                                <InputLabel for="land-preparation" value="Land Preparation" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>
                                        </div>
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.farm_worker.includes('Planting / Transplanting')" value="Planting / Transplanting" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmWorker" />
                                                <InputLabel for="planting" value="Planting / Transplanting" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>
                                        </div>
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.farm_worker.includes('Cultivation')" value="Cultivation" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmWorker" />
                                                <InputLabel for="cultivation" value="Cultivation" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>
                                        </div>
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.farm_worker.includes('Harvesting')" value="Harvesting" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmWorker" />
                                                <InputLabel for="harvesting" value="Harvesting" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>
                                        </div>
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.farm_worker.includes('Others')" value="Others" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFarmWorker" />
                                                <InputLabel for="farmworker-other" value="Others" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>

                                            <div class="flex flex-wrap items-center ms-4" v-if="livelihoodForm.farm_worker.includes('Others')">
                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                <div class="rounded-md block mt-1 w-full">
                                                    <TextInput type="text" name="suffix" v-model="livelihoodForm.farm_worker_others" class="mt-1 block w-full uppercase" autocomplete="off" 
                                                        @blur="y$.farm_worker_others.$touch()"
                                                        :class="!y$.farm_worker_others.$dirty && livelihoodForm.farm_worker_others ? 'border-gray-300' : livelihoodInputBorderClass('farm_worker_others')"
                                                    />
                                                </div>
                                                <p v-if="hasLivelihoodError('farm_worker_others')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="y$.farm_worker_others.required?.$invalid">Value is required.</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <p v-if="hasLivelihoodError('farm_worker')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="y$.farm_worker.required?.$invalid">Kind of work for farmworkers is required. Please select atleast 1.</span>
                                    </p>
                                </div>
                            </div>
                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="livelihoodForm.main_livelihood.includes('fisherfolks')"
                                :class="{
                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : livelihoodForm.main_livelihood.length >= 1 && livelihoodForm.main_livelihood.length <= 2,
                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : livelihoodForm.main_livelihood.length == 3,
                                    'md:w-[49%] lg:w-[36%] xl:w-[36%] 2xl:w-[26%]' : livelihoodForm.main_livelihood.length == 4
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
                                                    <TextInput type="checkbox" :checked="livelihoodForm.fisherfolks.includes('Fish Capture')" value="Fish Capture" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFisherFolks" />
                                                    <InputLabel for="fish-capture" value="Fish Capture" class="text-sm text-gray-700 cursor-pointer" />
                                                </div>
                                            </div>
                                            <div class="md:w-[49%] sm:w-full">
                                                <div class="inline-flex items-center space-x-2">
                                                    <TextInput type="checkbox" :checked="livelihoodForm.fisherfolks.includes('Fish Processing')" value="Fish Processing" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFisherFolks" />
                                                    <InputLabel for="fish-processing" value="Fish Processing" class="text-sm text-gray-700 cursor-pointer" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center justify-between w-full mb-2">
                                            <div class="md:w-[49%] sm:w-full">
                                                <div class="inline-flex items-center space-x-2">
                                                    <TextInput type="checkbox" :checked="livelihoodForm.fisherfolks.includes('Aquaculture')" value="Aquaculture" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFisherFolks" />
                                                    <InputLabel for="aquaculture" value="Aquaculture" class="text-sm text-gray-700 cursor-pointer" />
                                                </div>
                                            </div>
                                            <div class="md:w-[49%] sm:w-full">
                                                <div class="inline-flex items-center space-x-2">
                                                    <TextInput type="checkbox" :checked="livelihoodForm.fisherfolks.includes('Fish Vending')" value="Fish Vending" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFisherFolks" />
                                                    <InputLabel for="fish-vending" value="Fish Vending" class="text-sm text-gray-700 cursor-pointer" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center justify-between w-full mb-2">
                                            <div class="md:w-[49%] sm:w-full">
                                                <div class="inline-flex items-center space-x-2">
                                                    <TextInput type="checkbox" :checked="livelihoodForm.fisherfolks.includes('Gleaning')" value="Gleaning" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFisherFolks" />
                                                    <InputLabel for="gleaning" value="Gleaning" class="text-sm text-gray-700 cursor-pointer" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.fisherfolks.includes('Others')" value="Others" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleFisherFolks" />
                                                <InputLabel for="fisherfolk-other" value="Others" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>

                                            <div class="flex flex-wrap items-center ms-4" v-if="livelihoodForm.fisherfolks.includes('Others')">
                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                <div class="rounded-md block mt-1 w-full">
                                                    <TextInput type="text" name="suffix" v-model="livelihoodForm.fisherfolks_others" class="mt-1 block w-full uppercase" autocomplete="off" 
                                                    @blur="y$.fisherfolks_others.$touch()"
                                                    :class="!y$.fisherfolks_others.$dirty && livelihoodForm.fisherfolks_others ? 'border-gray-300' : livelihoodInputBorderClass('fisherfolks_others')"
                                                    />
                                                </div>
                                                <p v-if="hasLivelihoodError('fisherfolks_others')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="y$.fisherfolks_others.required?.$invalid">Value is required.</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <p v-if="hasLivelihoodError('fisherfolks')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="y$.fisherfolks.required?.$invalid">Fishing Activity is required. Please select atleast 1.</span>
                                    </p>
                                </div>
                            </div>
                            <div class="bg-white shadow-xl sm:w-full rounded-md md:mb-4 lg:mb-0 xl:mb-4 2xl:mb-0" v-if="livelihoodForm.main_livelihood.includes('agri_youth')"
                                :class="{
                                    'md:w-[49%] lg:w-[49%] xl:w-[49%] 2xl:w-[49%]' : livelihoodForm.main_livelihood.length >= 1 && livelihoodForm.main_livelihood.length <= 2,
                                    'md:w-[32%] lg:w-[32%] xl:w-[32%] 2xl:w-[32%]' : livelihoodForm.main_livelihood.length == 3,
                                    'md:w-[49%] lg:w-[33%] xl:w-[33%] 2xl:w-[24%]' : livelihoodForm.main_livelihood.length == 4
                                }"
                            >
                                <div class="p-4 lg:p-6 bg-white">
                                    <h4 class="text-center font-bold italic text-lg mb-3">For Agri Youth:</h4>

                                    <p class="mb-3">For the purposes of trainings, financial assistance, and either programs and catered to the youth with involvement to any agriculture activity.</p>

                                    <h5 class="font-bold text-md mb-2">Type of Involvement</h5>

                                    <div class="flex flex-wrap">
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.agri_youth.includes('Part of a farming household')" value="Part of a farming household" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleAgriYouth" />
                                                <InputLabel for="part-of-farming-household" value="Part of a farming household" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>
                                        </div>
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.agri_youth.includes('Attending / attended formal agri-fishery related course')" value="Attending / attended formal agri-fishery related course" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleAgriYouth" />
                                                <InputLabel for="attended-formal-agri-fishery" value="Attending / attended formal agri-fishery related course" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>
                                        </div>
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.agri_youth.includes('Attending / attended non-formal agri-fishery related course')" value="Attending / attended non-formal agri-fishery related course" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleAgriYouth" />
                                                <InputLabel for="attended-non-formal-agri-fishery" value="Attending / attended non-formal agri-fishery related course" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>
                                        </div>
                                        <div class="w-full mb-2">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.agri_youth.includes('Participated a any agircultural activity / program')" value="Participated a any agircultural activity / program" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleAgriYouth" />
                                                <InputLabel for="participated-any-agri-activity" value="Participated a any agircultural activity / program" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>
                                        </div>
                                        <div class="w-full">
                                            <div class="inline-flex items-center space-x-2">
                                                <TextInput type="checkbox" :checked="livelihoodForm.agri_youth.includes('Others')" value="Others" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" @click="handleAgriYouth" />
                                                <InputLabel for="youth-others" value="Others" class="text-sm text-gray-700 cursor-pointer" />
                                            </div>

                                            <div class="flex flex-wrap items-center ms-4" v-if="livelihoodForm.agri_youth.includes('Others')">
                                                <InputLabel for="crops-specify" value="Specify: " class="me-4" />
                                                <div class="rounded-md block mt-1 w-full">
                                                    <TextInput type="text" name="suffix" v-model="livelihoodForm.agri_youth_others" class="mt-1 block w-full uppercase" autocomplete="off" 
                                                    @blur="y$.agri_youth_others.$touch()"
                                                    :class="!y$.agri_youth_others.$dirty && livelihoodForm.agri_youth_others ? 'border-gray-300' : livelihoodInputBorderClass('agri_youth_others')"
                                                    />
                                                </div>
                                                <p v-if="hasLivelihoodError('agri_youth_others')" class="text-red-500 text-sm">
                                                    <span class="text-red-500 text-sm" v-if="y$.agri_youth_others.required?.$invalid">Value is required.</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="hasLivelihoodError('agri_youth')" class="text-red-500 text-sm">
                                        <span class="text-red-500 text-sm" v-if="y$.agri_youth.required?.$invalid">Type of Involvement is required. Please select atleast 1.</span>
                                    </p>
                                </div>
                            </div>
                        </div>

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
                                        <TextInput type="number" class="mt-1 block w-full uppercase" v-model="livelihoodForm.farming_gross"
                                            @blur="y$.farming_gross.$touch()"
                                            :class="!y$.farming_gross.$dirty && livelihoodForm.farming_gross ? 'border-gray-300' : livelihoodInputBorderClass('farming_gross')"
                                        />
                                        <p v-if="hasLivelihoodError('farming_gross')" class="text-red-500 text-sm">
                                            <span class="text-red-500 text-sm" v-if="y$.farming_gross.required?.$invalid">Farming Gross Income is required.</span>
                                        </p>
                                    </div>
                                    <div class="sm:w-full md:w-[49%]">
                                        <InputLabel for="non-farming" value="Non-farming" :required="true" />
                                        <TextInput type="number" class="mt-1 block w-full uppercase"  v-model="livelihoodForm.no_farming_gross"
                                            @blur="y$.no_farming_gross.$touch()"
                                            :class="!y$.no_farming_gross.$dirty && livelihoodForm.no_farming_gross ? 'border-gray-300' : livelihoodInputBorderClass('no_farming_gross')"
                                        />
                                        <p v-if="hasLivelihoodError('no_farming_gross')" class="text-red-500 text-sm">
                                            <span class="text-red-500 text-sm" v-if="y$.no_farming_gross.required?.$invalid">Non Farming Gross Income is required.</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <ActionMessage :on="recentlySuccessful" class="me-3">
                    Main Livelihood successfully updated.
                </ActionMessage>
                <PrimaryButton class="bg-blue-500 hover:bg-blue-700 text-white me-2" :class="{ 'opacity-25': processing }" 
                    :disabled="processing" @click="submitEditLivelihood">Save</PrimaryButton>
                <SecondaryButton @click="closeLivelihoodEditModal">Close</SecondaryButton>
            </template>
        </DialogModal>

        <DialogModal id="newAssistance" :show="createAssistanceDialog" :max-width="'md'" @close="closeAssistanceModal">
            <template #title>
                New Assistance
            </template>
            <template #content>
                <div>
                    <div class="py-3 lg:py-3 bg-white">
                        <div class="mb-4">
                            <InputLabel for="Livelihood" value="Livelihood" :required="true" />
                            <div class="rounded-md block w-full">
                                <Select2 class="h-10 uppercase" v-model="historyForm.livelihood" :options="history_main_livelihood" :settings="{ placeholder: 'Select An Option', width: '100%', dropdownParent: $('#newAssistance') }" @select="handleAssistanceLivelihood" />
                            </div>

                            <p v-if="historyError('livelihood')" class="text-red-500 text-sm mt-1">
                                <span class="text-red-500 text-sm" v-if="history$.livelihood.required?.$invalid">Livelihood is required.</span>
                            </p>
                        </div>

                        <div class="mb-4">
                            <InputLabel for="type" value="Assistance" :required="true" />
                            <div class="rounded-md block w-full">
                                <Select2 class="h-10 uppercase" v-model="historyForm.assistance" :options="availableAssistance" :settings="{ placeholder: 'Select An Option', width: '100%', dropdownParent: $('#newAssistance') }" @select="handleAssistance" />
                            </div>

                            <p v-if="historyError('assistance')" class="text-red-500 text-sm mt-1">
                                <span class="text-red-500 text-sm" v-if="history$.assistance.required?.$invalid">Assistance is required.</span>
                            </p>
                        </div>
                        <div class="mb-4" v-if="isCashAssist">
                            <InputLabel for="amount" value="Amount" :required="true" />
                            <TextInput v-model="historyForm.amount" type="number" class="mt-1 block w-full uppercase" placeholder="Enter Amount" autocomplete="off" 
                                @blur="history$.amount.$touch()"
                                :class="historyInputBorderClass('amount')"
                            />

                            <p v-if="historyError('amount')" class="text-red-500 text-sm">
                                <span class="text-red-500 text-sm" v-if="history$.amount.required?.$invalid">Amount is required.</span>
                            </p>
                        </div>
                        <div>
                            <InputLabel for="remarks" value="Remarks" :required="true" />
                            <textAreaInput v-model="historyForm.remarks" class="w-full uppercase" :rows="3" 
                                @blur="history$.remarks.$touch()"
                                :class="historyInputBorderClass('remarks')"
                            />

                            <p v-if="historyError('remarks')" class="text-red-500 text-sm">
                                <span class="text-red-500 text-sm" v-if="history$.remarks.required?.$invalid">Remarks is required.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <ActionMessage :on="recentlySuccessful" class="me-3">
                    Farming Type successfully added.
                </ActionMessage>
                <PrimaryButton class="bg-blue-500 hover:bg-blue-700 text-white me-2" :class="{ 'opacity-25': processing }" 
                    :disabled="processing" @click="submitNewAssistance">Save</PrimaryButton>
                <SecondaryButton @click="closeAssistanceModal">Close</SecondaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
