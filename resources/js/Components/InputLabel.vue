<script setup>
defineProps({
    value: String,
    required: false
});
</script>

<template>
    <label class="block font-medium text-sm text-gray-700">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
        <span v-if="required" class="required ms-1">*</span>
    </label>
</template>
