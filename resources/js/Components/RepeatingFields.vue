<template>
    <div>
        <div v-for="(field, index) in fields" :key="index">
            <div class="flex">
                <input v-model="field.value" type="text" :placeholder="`Field ${index + 1}`" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <button @click="removeField" type="button">Remove Field</button>
            </div>

        </div>
        <button @click="addField" type="button">Add Field</button>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                fields: [{ value: '' }], // Initialize with an empty field
            };
        },
        methods: {
            addField() {
                this.fields.push({ value: '' });
            },
            removeField() {
                if (this.fields.length > 1) {
                    this.fields.pop();
                }
            },
        },
    };
</script>