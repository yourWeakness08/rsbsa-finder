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

    const handleSearch = proxy.$debounce((val) => {
        const { value } = pageValue;

        debouncedSearch.value = val;
        let formData = {};
        if (value) { form.paginate = value };
        form.search = debouncedSearch.value ? val : '';

        form.post(route('reports.livelihood'), {
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

        form.post(route('reports.livelihood'), {
            onProgress: () => processing.value = true,
            onSuccess: () => {
                const page = usePage();
            }
        })

    }

    const form = useForm({
        livelihood: '',
        search: '',
        paginate: ''
    })

    const main_livelihood = ref([
        { id: 'farmer', text: 'FARMER' },
        { id: 'farm_worker', text: 'FARM WORKER / LABORER' },
        { id: 'fisherfolks', text: 'FISHERFOLKS' },
        { id: 'agri_youth', text: 'AGRI YOUTH' },
    ]);

    const generateLivelihood = () => {
        let formData = {};

        form.post(route('reports.livelihood'), {
            onProgress: () => processing.value = true,
            onSuccess: () => {
                const page = usePage();
                $("#search").val('');
            }
        })
    }

    const resetFields = () => {
        form.livelihood = '';
    }
    

    const handleLivelihood = (e) => {
        const selectedValue = e.id;

        select2Assitance.value = [];
        $.each(props.assistance, function(index, item) {
            $.each(item.livelihoods, function(i, v) {
                if (v === e.id) {
                    const _temp = Object.assign({}, {id: item.id, text: item.name.toUpperCase() });
                    select2Assitance.value.push(_temp);
                }
            })
        })
    }
</script>

<template>
    <AppLayout title="Livelihood Report">
        <template #header>
            <h2 class="font-semibold text-md text-gray-800 leading-tight">
                LIVELIHOOD REPORTS
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

                            <div class="mb-4">
                                <InputLabel for="Livelihood" value="Livelihood" />
                                <div class="rounded-md block w-full mt-1">
                                    <Select2 class="h-10 uppercase" v-model="form.livelihood" :options="main_livelihood" :settings="{ placeholder: 'Select An Option', width: '100%' }" @select="handleLivelihood" />
                                </div>
                            </div>

                            <hr class="mb-4">

                            <div class="w-full text-right">
                                <PrimaryButton class="bg-red-500 hover:bg-red-700 text-white px-2 py-3 me-2" @click="resetFields"> 
                                    Reset Filter 
                                </PrimaryButton>
                                <PrimaryButton class="bg-green-500 hover:bg-green-700 text-white px-2 py-3" :class="{ 'opacity-25': processing }" @click="generateLivelihood" :disabled="processing"> 
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
                                            <th scope="col" class="px-6 py-3 w-3/12">Name</th>
                                            <th scope="col" class="px-6 py-3 w-9/12">Type / Kind of Activity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-if="reports.total > 0">
                                            <tr class="bg-white border-b" v-for="reports in reports.data" :key="reports.id">
                                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                    {{ reports.name }}
                                                </td>
                                                <td class="px-6 py-4 font-medium text-gray-900 uppercase">
                                                    <template v-if="reports.main_livelihood">
                                                        <template v-for="(item, index) in reports.main_livelihood">
                                                            <ul class="mb-3">
                                                                <li class="font-extrabold">{{ item.livelihood }}</li>
                                                                <li class="ps-3" v-if="item.content.length > 0">
                                                                    - <span class="text-gray-700">{{ item.content.join(', ') }}</span>
                                                                </li>
                                                            </ul>
                                                        </template>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                        <template v-else-if="filter.paginate == 'All' && reports.length > 0">
                                            <tr class="bg-white border-b" v-for="reports in reports.data" :key="reports.id">
                                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                    {{ reports.name }}
                                                </td>
                                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap uppercase">
                                                </td>
                                            </tr>
                                        </template>
                                        <template v-else>
                                            <tr class="bg-white border-b">
                                                <th colspan="4" id="no-data-found" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap text-center uppercase">No Data Found!</th>
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