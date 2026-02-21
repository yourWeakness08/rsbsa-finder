<script setup>
    import { ref, watch } from 'vue'
    const tabs = ['Farmer Information', 'Farm Profile', 'Other Information', 'Assistance']
    const activeTab = ref(tabs[0])
    
    const props = defineProps({
        modelValue: String
    })

    const emit = defineEmits(['update:modelValue'])

    const changeTab = (tab) => {
        emit('update:modelValue', tab)

        const url = new URL(window.location.href)
        const params = url.searchParams;

        for (const key of [...params.keys()]) {
            if (key.includes('page')) {
                params.delete(key);
            }

            if (key.includes('paginate')) {
                params.delete(key);
            }
        }

        window.history.replaceState({}, '', url.pathname + url.search)
    }
</script>

<template>
    <div>
    <!-- Tabs -->
        <div class="flex border-b border-gray-200">
            <!-- <button v-for="(tab, index) in tabs" :key="index" @click="changeTab(tab)" :class="[ 'px-4 py-2 font-medium text-sm focus:outline-none', activeTab === tab ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500 hover:text-indigo-600' ]" class="uppercase">
                {{ tab }}
            </button> -->
            <button v-for="(tab, index) in tabs" :key="index" @click="changeTab(tab)" :class="[ 'px-4 py-2 font-medium text-sm focus:outline-none', modelValue === tab ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500 hover:text-indigo-600' ]" class="uppercase">
                {{ tab }}
            </button>
        </div>

        <!-- Tab Content -->
        <div class="mt-4">
            <div v-if="modelValue === 'Farmer Information'">
                <slot name="farmer-profile" />
            </div>

            <div v-if="modelValue === 'Farm Profile'">
                <slot name="farm-profile" />
            </div>

            <div v-if="modelValue === 'Other Information'">
                <slot name="other-information" />
            </div>

            <div v-if="modelValue === 'Assistance'">
                <slot name="assistance" />
            </div>
        </div>
    </div>
</template>