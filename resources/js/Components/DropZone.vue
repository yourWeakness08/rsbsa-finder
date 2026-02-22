<script setup>
import { onMounted, ref, nextTick, watch } from 'vue';
import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css';

const props = defineProps({
  uploadedSingleFile: {
    type: Object,
    default: () => ({})
  },
  uploadedFiles: {
    type: Object,
    default: () => ({}),
  },
  maxFile: {
    type: Number,
    default: 10
  }, 
  currentStep: {
    type: Number,
    default: 0
  },
  isMultiple: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['fileSelected']);

const _dropzoneRef = ref(null);
  let selectedFile = ref([])

onMounted(() => {
  nextTick(() => {
    const x = new Dropzone(_dropzoneRef.value, {
      url: '/upload', // change to your upload endpoint
      paramName: 'file',
      maxFilesize: 2, // MB
      acceptedFiles: '.pdf',
      clickable: true,
      autoProcessQueue: false,
      maxFiles: props.maxFile
    });

    x.on("addedfile", file => {
      selectedFile.value = file;

      if (props.currentStep == 2) {
        emit('fileSelected', [...x.files]);  
      }

      if (props.currentStep == 1) {
        emit('fileSelected', file);
      }

      if (props.currentStep == 0) {
        if (props.isMultiple) {
          emit('fileSelected', [...x.files]);
        } else {
          emit('fileSelected', file);
        }
      }

      file.previewElement.classList.add('dz-success');
      x.emit('success', file, {});
      x.emit('complete', file);

      const removeButton = document.createElement('button');
      removeButton.textContent = 'Remove';

      removeButton.classList.add('dz-remove');
      file.previewElement.appendChild(removeButton);

      removeButton.addEventListener('click', () => {
        x.removeFile(file);

        emit('fileSelected', [...x.files])
      });
    });

    if (x.getAcceptedFiles().length == 0) {
      if (props.currentStep == 1) {
        if (props.uploadedSingleFile !== null) {
          const _file = props.uploadedSingleFile;
          const mock = {
            'name' : _file.name,
            'size' : _file.size
          }

          x.emit('addedfile', mock);
          x.emit('complete', mock);

          emit('fileSelected', props.uploadedSingleFile);
        }
      }

      if (props.currentStep == 2){
        if (props.uploadedFiles.length > 0) {
          $.each(props.uploadedFiles, function(i, v){
            const mockFile = {
              'name': v.name,
              'size': v.size
            }

            x.emit('addedfile', mockFile);
            x.emit('complete', mockFile);
          });

          emit('fileSelected', [...props.uploadedFiles]);  
        }
      }
    }
  })
});
</script>

<template>
  <div ref="_dropzoneRef" class="dropzone border border-dashed rounded-lg p-6 text-center text-gray-500">
    <div class="dz-message">Drop PDF files here or click to upload</div>
  </div>
</template>

<style type="text/css">
  .dz-preview {
    position: relative;
  }
  .dz-remove{
    position: absolute;
    margin: 0 auto !important;
    color: red !important;
    display: none !important;
    top: 0%;
    z-index: 999 !important;
    transform: translate(12px, 30px) !important; 
    padding: 20px;
    background-color: #ffffff45 !important;
    font-weight: bolder;
    text-transform: uppercase;
  }
  .dz-preview:hover > .dz-remove {
    display: block !important;
  }
</style>