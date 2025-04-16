<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
    modelOptions: Array,
    placeholder: { type: String, default: () => ('Select An Option')},
});

defineEmits(['update:modelValue']);

const select = ref(null);

onMounted(() => {
    if (select.value.hasAttribute('autofocus')) {
        select.value.focus();
    }
});

defineExpose({ focus: () => select.value.focus() });
</script>

<template>
    <select ref="select" 
        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
        :value="modelValue" 
        :placeholder="placeholder"
        @change="$emit('update:modelValue', $event.target.value)">
            <option selected disabled value=""> {{ placeholder }} </option>
            <option v-if="modelOptions.length > 0"
                v-for="(option, index) in modelOptions" 
                :value="option.id" 
                :key="option.id ? option.id: index">{{ option.text ? option.text: option  }}</option>
        </select>
</template>
