<script setup>
    import { ref, getCurrentInstance, watch } from 'vue'
    import { Link, router, usePage } from '@inertiajs/vue3';
    import axios from 'axios'
    import TextInput from '@/Components/TextInput.vue';
    const { proxy } = getCurrentInstance()

    const results = ref([])
    const debouncedSearch = ref('');
    const searchValue = ref('');
    let hasResult = ref(null);

    const handleSearch = proxy.$debounce(async (val) => {
        debouncedSearch.value = val;
        if (searchValue.length < 3) {
            results.value = []
            return
        }

        try {
            if (val.trim().length >= 3) {
                const response = await axios.get('/search', {
                    params: { query: val },
                })
    
                if (response.data.length > 0) {
                    results.value = response.data;
                    hasResult.value = true;
                } else {
                    hasResult.value = false;
                }
            }
        } catch (error) {
            console.error('Searcg failed:', error)
        }
    }, 1000);

    watch(searchValue, (val) => {

        if (val.length < 3) {
            hasResult.value = null;
            results.value = [];
        }

        handleSearch(val)
    });

    const select = (id) => {
        const url = route('farmers.view', { id: id });
        router.visit(url)
    }
</script>

<template>
    <div class="relative w-full max-w-md">
        <TextInput type="text" class="block w-full h-10 uppercase" placeholder="Search" autocomplete="off" v-model="searchValue" />
        <template v-if="results.length && searchValue.length">
            <ul class="absolute z-10 bg-white border mt-1 rounded shadow w-full max-h-60 overflow-y-auto">
                <li v-for="item in results" :key="item.id" @click="select(item)" class="flex items-center p-3 hover:bg-gray-100 cursor-pointer">
                    <img :src="item.farmer_image" alt="Profile" class="w-14 h-14 rounded-full object-cover mr-3" />
                    <span>{{ item.name }}</span>
                </li>
            </ul>
        </template>
        <template v-if="hasResult != null">
            <ul v-if="searchValue.length >= 3 && hasResult == false" class="absolute z-10 bg-white border mt-1 rounded shadow w-full max-h-60 overflow-y-auto">
                <li class="flex items-center p-3 hover:bg-gray-100">
                    <span>No Matching record found for <strong>"{{ searchValue }}"</strong>.</span>
                </li>
            </ul>
        </template>
    </div>
</template>