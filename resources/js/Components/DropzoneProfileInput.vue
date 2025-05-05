<script setup>
    import { ref, onMounted, nextTick } from 'vue'
    import Dropzone from 'dropzone'
    import 'dropzone/dist/dropzone.css'

    const props = defineProps({
        uploadUrl: String,
        currentImageUrl: String,
    })

    const emit = defineEmits(['fileSelected'])

    const dropzoneRef = ref(null)
    const uploadedImage = ref(props.currentImageUrl || null)
    const selectedFile = ref(null)

    onMounted(() => {
        nextTick(() => {
            if (dropzoneRef.value) {
                const dz = new Dropzone(dropzoneRef.value, {
                    url: props.uploadUrl,
                    autoProcessQueue: false,
                    maxFilesize: 3,
                    maxFiles: 1,
                    acceptedFiles: 'image/*',
                    clickable: '.upload-trigger', // trigger hidden input via overlay icon
                    previewsContainer: false, // disables default preview
                    autoQueue: false,
                })

                dz.on("addedfile", file => {
                    
                    selectedFile.value = file
                    emit('fileSelected', file)
                    
                    const reader = new FileReader()
                    reader.onload = e => {
                        uploadedImage.value = e.target.result
                    }
                    reader.readAsDataURL(file)
                })
            }
        })
    })
</script>


<template>
    <div class="relative md:w-35 md:h-35 2xl:w-40 2xl:h-40 rounded-md border border-gray-300">
        <!-- Circular Image -->
        <img :src="uploadedImage" alt="Profile" class="w-full h-full object-contain " style="scale: 0.9;" />

        <!-- Upload Overlay -->
        <div class="absolute rounded-md inset-0 bg-black/40 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity cursor-pointer upload-trigger">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15.232 5.232l3.536 3.536M9 13l6-6m2 10H7a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2z"/>
            </svg>
        </div>

        <!-- Hidden Dropzone Element -->
        <div ref="dropzoneRef" class="hidden"></div>
    </div>
</template>
  
<style scoped>
    .upload-trigger {
        position: absolute;
        inset: 0;
    }
</style>
  