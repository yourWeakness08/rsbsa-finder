<script setup>
import { onMounted } from 'vue'
import FarmerPrintTemplate from '@/Components/FarmerPrintTemplate.vue'

const props = defineProps({
    farmer: {
        type: Object,
        default: () => ({}),
    },
})

const dateFormat = (value) => {
    if (!value) return ''
    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: '2-digit',
    })
}

const numberFormat = (value) => {
    return Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
}

const checkMeta = (items, value) => {
    if (!Array.isArray(items)) return false

    return items.some(
        item => String(item?.meta || '').toLowerCase() === String(value).toLowerCase()
    )
}

const famerSpecify = (meta, items) => {
    if (!Array.isArray(items)) return ''

    return items
        .filter(item => String(item?.meta || '').toLowerCase() === String(meta).toLowerCase())
        .map(item => item.value)
        .filter(Boolean)
        .join(', ')
}

const otherActivity = (meta, items) => {
    if (!Array.isArray(items)) return ''

    return items
        .filter(item => String(item?.meta || '').toLowerCase() === String(meta).toLowerCase())
        .map(item => item.value)
        .filter(Boolean)
        .join(', ')
}

const formatFarmType = (value) => {
    if (!value) return ''
    return String(value)
        .replaceAll('_', ' ')
        .replace(/\b\w/g, c => c.toUpperCase())
}

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search)
    const debug = urlParams.get('debug')

    if (!debug) {
        setTimeout(() => {
            window.print()
        }, 300)

        window.onafterprint = () => {
            window.close()
        }
    }
})
</script>

<template>
    <div class="min-h-screen bg-white p-4">
        <FarmerPrintTemplate
            :farmer="farmer"
            :photo-url="farmer.farmer_image"
            :date-format="dateFormat"
            :number-format="numberFormat"
            :check-meta="checkMeta"
            :famer-specify="famerSpecify"
            :other-activity="otherActivity"
            :format-farm-type="formatFarmType"
        />
    </div>
</template>