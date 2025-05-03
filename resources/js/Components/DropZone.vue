<script setup>
import { onMounted, ref } from 'vue';
import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css';

const emit = defineEmits(['success']);

const dropzoneRef = ref(null);

onMounted(() => {
  const dz = new Dropzone(dropzoneRef.value, {
    url: '/upload', // change to your upload endpoint
    paramName: 'file',
    maxFilesize: 2, // MB
    acceptedFiles: 'image/*',
    clickable: true,
    autoProcessQueue: true,
    success(file, response) {
      emit('success', response);
    },
  });
});
</script>

<template>
  <div ref="dropzoneRef" class="dropzone border border-dashed rounded-lg p-6 text-center text-gray-500">
    <div class="dz-message">Drop files here or click to upload</div>
  </div>
</template>
