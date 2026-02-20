<script setup>
    import { ref, watch } from 'vue'
    const tabs = ['Farmer Information', 'Farm Profile', 'Other Information', 'Assistance']
    const activeTab = ref(tabs[0])
    
    const props = defineProps({
        switchToTab: String,
    })

    const emit = defineEmits(['tab-changed'])

    const changeTab = (tab) => {
        activeTab.value = tab

        const url = new URL(window.location.href)
        const params = url.searchParams;

        for (const key of [...params.keys()]) {
            if (key.includes('page')) {
                params.delete(key); // Remove the parameter.
            }

            if (key.includes('paginate')) {
                params.delete(key); // Remove the parameter.
            }
        }

        window.history.replaceState({}, '', url.pathname + url.search)

        emit('tab-changed', tab) 
    }

    watch(() => props.switchToTab, (newTab) => {
        console.log('Switching to tab:', newTab)
        if (newTab) changeTab(newTab)
    })
</script>

<template>
    <div>
    <!-- Tabs -->
        <div class="flex border-b border-gray-200">
            <!-- <button v-for="(tab, index) in tabs" :key="index" @click="changeTab(tab)" :class="[ 'px-4 py-2 font-medium text-sm focus:outline-none', activeTab === tab ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500 hover:text-indigo-600' ]" class="uppercase">
                {{ tab }}
            </button> -->
            <button v-for="(tab, index) in tabs" :key="index" @click="changeTab(tab)" :class="[ 'px-4 py-2 font-medium text-sm focus:outline-none', activeTab === tab ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500 hover:text-indigo-600' ]" class="uppercase">
                {{ tab }}
            </button>
        </div>

        <!-- Tab Content -->
        <div class="mt-4">
            <div v-if="activeTab === 'Farmer Information'">
                <slot name="farmer-profile" />
            </div>

            <div v-if="activeTab === 'Farm Profile'">
                <slot name="farm-profile" />
            </div>

            <div v-if="activeTab === 'Other Information'">
                <slot name="other-information" />
            </div>

            <div v-if="activeTab === 'Assistance'">
                <slot name="assistance" />
            </div>
        </div>
    </div>
</template>