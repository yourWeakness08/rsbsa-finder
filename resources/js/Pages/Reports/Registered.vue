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
    import TablePagination from '@/Components/TablePagination.vue';

    import Select2 from 'vue3-select2-component';

    import { Link, router, useForm, usePage } from '@inertiajs/vue3';
    import axios from 'axios';
    import moment from 'moment';
    import Swal from 'sweetalert2';
    import daterangepicker from 'daterangepicker';
    import { provinces, municipalities, barangays } from 'psgc'
    
    import $ from 'jquery';

    const { proxy } = getCurrentInstance()
    
    const props = defineProps({
        reports: {
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
        assistance: {
            type: Object,
            default: () => ({})
        }
    });

    const pageValue = ref(null);
    const searchValue = ref(null);
    const debouncedSearch = ref('');
    const processing = ref(false);
    const select2Assitance = ref([]);

    const pages = ref([ 10, 25, 50, 100, 200, 'All']);

    const submitFilter = () => {
        const { value } = pageValue;
        let formData = {};
        if(value){ formData.paginate = value; }
        if(searchValue.value){ formData.search = searchValue.value; }
        
        if(Object.keys(formData).length > 0){
            router.visit('/types', {
                method: 'get',
                data: formData,
                only: ['farming_type', 'filter']
            });
        }
    }

    const dateFormat = (date) => {
        return moment(date).format('MMM. DD, YYYY hh:mm A');
    }

    const handleSearch = proxy.$debounce((val) => {
        const { value } = pageValue;

        debouncedSearch.value = val;
        let formData = {};
        if (value) { form.paginate = value };
        form.search = debouncedSearch.value ? val : '';

        form.post(route('reports.registered'), {
            onProgress: () => processing.value = true,
            onSuccess: () => {
                const page = usePage();
            }
        })
    }, 1000);

    watch(searchValue, (val) => {
        handleSearch(val)
    });

    const tableShow = () => {
        const { value } = pageValue;

        let formData = {};
        if (value) { form.paginate = value };
        if (searchValue.value) { form.search = searchValue.value; }

        form.post(route('reports.registered'), {
            onProgress: () => processing.value = true,
            onSuccess: () => {
                const page = usePage();
            }
        })
    }

    const startDate = moment().subtract(6, 'days');
    const endDate = moment();

    const form = useForm({
        city: 'Hinigaran',
        brgy: '',
        from: moment(startDate).format('YYYY-MM-DD'),
        to: moment(endDate).format('YYYY-MM-DD'),
        search: '',
        paginate: ''
    })

    const main_livelihood = ref([
        { id: 'farmer', text: 'FARMER' },
        { id: 'farm_worker', text: 'FARM WORKER / LABORER' },
        { id: 'fisherfolks', text: 'FISHERFOLKS' },
        { id: 'agri_youth', text: 'AGRI YOUTH' },
    ]);

    onMounted(() => {
        datepicker();
        select2Brgy(form.city);
    });

    const datepicker = () => {
        $('#daterange').daterangepicker({
            opens: 'left',
            locale: {
                format: 'MM/DD/YYYY',
            },
            singleDatePicker: false,
            showDropdowns: true,
            autoUpdateInput: true,
            startDate: startDate,
            endDate: endDate
        }).on('apply.daterangepicker', function(ev, picker){
            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));

            form.from = picker.startDate.format('YYYY-MM-DD');
            form.to = picker.endDate.format('YYYY-MM-DD');
        });
    }

    const generateAssistance = () => {
        let formData = {};

        form.post(route('reports.registered'), {
            onProgress: () => processing.value = true,
            onSuccess: () => {
                const page = usePage();
                $("#search").val('');
            }
        })
    }

    const resetFields = () => {
        form.brgy = '';
        form.from = startDate;
        form.to = endDate;

        datepicker();
    }

    const cityOptions = ref([]);
    const barangayOptions = ref([]);

    cityOptions.value = municipalities
        .all()
        .filter(item => item.province === 'Negros Occidental')
        .map(item => {
            const name = item.name + (item.city ? ' City' : '')

            return {
                id: name,                 // Select2 value
                text: name.toUpperCase(), // display
                name: item.name.toLowerCase()
            }
    })

    const handleMuniCitySelect = (event) => {
        let selectedValue = event.id.toLowerCase();

        if (selectedValue == 'escalante city' || selectedValue == 'himamaylan city' || selectedValue == 'kabankalan city' || selectedValue == 'talisay city' || selectedValue == 'victorias city') {
            let newText = selectedValue.split(" ")[0];
            selectedValue = 'city of ' + newText;
        }

        const brgy = barangays.all()
            .filter(
                item => item.citymun.toLowerCase().includes(selectedValue)
            ).map( item => {
                return {
                    id: item.name,
                    text: item.name.toUpperCase(),
                    citymun: item.citymun
                }
            });

        barangayOptions.value = brgy;
        form.brgy = null;
    }

    const groupedReports = computed(() => {
        const rows = Array.isArray(props.reports?.data)
            ? props.reports.data
            : Array.isArray(props.reports)
                ? props.reports
                : [];

        const groups = {};

        const formCity = (props.filter.city ?? '').toString().trim();
        const formBrgy = (props.filter.brgy ?? '').toString().trim();

        rows.forEach((row) => {
            const city = (row.city ?? '').toString().trim();
            const brgy = (row.brgy ?? '').toString().trim();

            if (formBrgy !== '') {
                const key = `NO_GROUP_${row.id}`;
                groups[key] = {
                    group: null,
                    items: [row]
                };
                return;
            }

            let key = '';

            if (formCity !== '') {
                key = brgy !== '' ? brgy : 'UNSPECIFIED';
            } else {
                key = city !== '' ? city : 'UNSPECIFIED';
            }

            if (!groups[key]) {
                groups[key] = {
                    group: key,
                    items: []
                };
            }

            groups[key].items.push(row);
        });

        return Object.values(groups);
    });

    const select2Brgy = (val) => {
        const city = val.toLowerCase();
        const brgy = barangays.all()
            .filter(
                item => item.citymun.toLowerCase().includes(city)
            ).map( item => {
                return {
                    id: item.name,
                    text: item.name.toUpperCase(),
                    citymun: item.citymun
                }
            });

        barangayOptions.value = brgy;
    }
</script>

<template>
    <AppLayout title="Assistance Report">
        <template #header>
            <h2 class="font-semibold text-md text-gray-800 leading-tight">
                ASSISTANCE REPORTS
            </h2>
        </template>

        <div class="py-8">
            <div class="flex flex-wrap justify-between">
                <div class="w-[28%]">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
                            <div class="w-full mb-4">
                                <h4 class="text-lg uppercase font-semibold">Filter</h4>
                            </div>

                            <hr class="mb-4">

                            <div class="mb-4" hidden>
                                <InputLabel for="City" value="City" />
                                <div class="rounded-md block w-full mt-1">
                                    <Select2 class="h-10 uppercase" v-model="form.city" :options="cityOptions" :settings="{ placeholder: 'Select An Option', width: '100%' }" @select="handleMuniCitySelect" />
                                </div>
                            </div>

                            <div class="mb-4">
                                <InputLabel for="Barangay" value="Barangay" />
                                <div class="rounded-md block w-full mt-1">
                                    <Select2 class="h-10 uppercase" v-model="form.brgy" :options="barangayOptions" :settings="{ placeholder: 'Select An Option', width: '100%' }" />
                                </div>
                            </div>

                            <div class="mb-4">
                                <InputLabel for="daterange" value="Date" />
                                <TextInput type="text" class="mt-1 block w-full uppercase" id="daterange" placeholder="MM/DD/YYYY - MM/DD/YYYY" />
                            </div>

                            <hr class="mb-4">

                            <div class="w-full text-right">
                                <PrimaryButton class="bg-red-500 hover:bg-red-700 text-white px-2 py-3 me-2" @click="resetFields"> 
                                    Reset Filter 
                                </PrimaryButton>
                                <PrimaryButton class="bg-green-500 hover:bg-green-700 text-white px-2 py-3" :class="{ 'opacity-25': processing }" @click="generateAssistance" :disabled="processing"> 
                                    Generate 
                                </PrimaryButton>
                            </div>
                            
                        </div>
                    </div>
                </div>
                <div class="w-[70%]">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
                            <div class="flex flex-wrap justify-end">
                                <div class="w-3/12">
                                    <TextInput v-model="searchValue" id="search" type="text" class="block w-full h-10" placeholder="Search" autocomplete="off" />
                                </div>
                            </div>

                            <div class="mt-6">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead
                                        class="text-xs text-gray-700 uppercase">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 w-2/12">Reference No.</th>
                                            <th scope="col" class="px-6 py-3 w-3/12">Name</th>

                                            <!-- BARANGAY COLUMN -->
                                            <template v-if="!filter.city || filter.brgy">
                                                <th scope="col" class="px-6 py-3 w-2/12">Barangay</th>
                                            </template>
                                            <th scope="col" class="px-6 py-3 w-3/12">Created By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-if="groupedReports.length > 0">

                                            <template v-for="group in groupedReports" :key="group.group ?? group.items[0].id">

                                                <!-- GROUP HEADER -->
                                                <tr v-if="group.group" class="bg-gray-100 border-b">
                                                    <th colspan="4" class="px-6 py-3 text-left font-bold text-gray-800 uppercase">
                                                        {{ group.group }}
                                                    </th>
                                                </tr>

                                                <!-- ROWS -->
                                                <tr v-for="report in group.items" :key="report.id" class="bg-white border-b">
                                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                        {{ report.ref_no }}
                                                    </td>
                                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                        {{ report.name }}
                                                    </td>

                                                    <template v-if="!filter.city || filter.brgy">
                                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                            {{ report.brgy || '-' }}
                                                        </td>
                                                    </template>

                                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                        <p class="font-semibold">{{ report.created_name }}</p>
                                                        <small>{{ dateFormat(report.created_at) }}</small>
                                                    </td>
                                                </tr>

                                            </template>

                                        </template>

                                        <template v-else>
                                            <tr class="bg-white border-b">
                                                <th colspan="4" class="px-6 py-4 text-center uppercase">
                                                    No Data Found!
                                                </th>
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
                                            <TablePagination :arr="reports" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>