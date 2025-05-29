<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
    cols: Number,
    rows: {
        type: Number,
        default: 5,
    }
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <textarea 
        ref="input"
        :value="modelValue"
        class="border-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 rounded-md shadow-sm" :rows="rows" :cols="cols"
        @change="$emit('update:modelValue', $event.target.value)">{{ modelValue }}</textarea>

</template>
