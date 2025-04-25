<script setup>
    const emit = defineEmits(['step-selected'])
    
    defineProps({
        currentStep: {
            type: Number,
            required: true,
        },
        steps: {
            type: Array,
            required: true,
        },
    });

    const goToStep = (index) => {
        emit('step-selected', index)
    }
</script>

<template>
    <div class="flex justify-between">
        <div v-for="(label, index) in steps" :key="index" class="flex items-center space-x-2 cursor-pointer group" @click="goToStep(index)">
            <div 
                class="rounded-full w-6 text-center" 
                :class="{ 
                    'bg-green-600 text-white': currentStep > index, 
                    'bg-green-500 text-white': currentStep === index, 
                    'bg-gray-300': currentStep < index,
                    'group-hover:scale-110': true }" 
            >
                {{ index+1 }}
            </div>
            <div 
                class="text-sm font-medium" 
                :class="{ 
                    'text-green-600': currentStep === index, 
                    'text-gray-600': currentStep !== index }" 
            >
                {{ label }}
            </div>
        </div>
    </div>
</template>