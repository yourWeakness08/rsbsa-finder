<script setup>
    import { Head, Link, useForm, router } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import Welcome from '@/Components/Welcome.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import SearchDropdown from '@/Components/SearchDropdown.vue';
    import { ref, onMounted, watch, reactive, nextTick } from 'vue';
    import { Pie, Line, Bar  } from 'vue-chartjs'
    import {Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, PointElement, LineElement, BarElement} from 'chart.js'
    import axios from 'axios';

    ChartJS.register(Tooltip, Legend, ArcElement, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title)

    const chartAssistanceKey = ref(0)

    const AssistanceBarChartData = ref({
        labels: ['PENDING', 'APPROVED', 'DISAPPROVED', 'CANCELLED'],
        datasets: [
            {
                label: 'Assistance Status (%)',
                data: [0,0,0,0],
                backgroundColor: ['#facc15', '#22c55e', '#ef4444', '#6b7280'],
                borderRadius: 6,
                barThickness: 40
            }
        ]
    })

    const chartAssistanceOptions = {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y', // makes the bar chart horizontal
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.raw + '%'
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%'
                    }
                }
            }
        },
        onClick: (event, elements, chart) => {
            if (!elements.length) return

            const index = elements[0].index
            const status = chart.data.labels[index].toLowerCase()

            // redirect (adjust depending on your routing)
            router.visit(route('assistances.index', { status }))
        },
        onHover: (event, elements) => {
            event.native.target.style.cursor = elements.length ? 'pointer' : 'default'
        }
    }

    const statusOptions = [
        { value: 'all', label: 'All' },
        { value: 'pending', label: 'Pending' },
        { value: 'approved', label: 'Approved' },
        { value: 'disapproved', label: 'Disapproved' },
        { value: 'cancelled', label: 'Cancelled' },
    ]
    const currentYear = new Date().getFullYear()
    const yearOptions = Array.from({ length: 6 }, (_, i) => currentYear - i) // last 6 years

    const status = ref('all')
    const year = ref(currentYear)
    const charByMonthKey = ref(0)
    const chartRef = ref(null)

    const chartByMonthData = ref({
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1000,
            easing: 'easeOutQuart'
        },
        plugins: {
            legend: { position: 'bottom' },
        },
        scales: {
            y: {
            beginAtZero: true,
            ticks: { precision: 0 },
            suggestedMax: 5,
            },
        },
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [
            {
                label: 'Assistances',
                data: [],
                backgroundColor: '#3b82f6',
                borderColor: '#1d4ed8',
                borderWidth: 1,
                barThickness: 28,
                minBarLength: 4,
            }
        ]
    })

    const chartByMonthOptions = ref({
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' },
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 },
                suggestedMax: 5,
            },
        },
    })

    async function loadChart() {
        const res = await axios.get('/chart/assistances-by-month', {
            params: { status: status.value, year: year.value },
        })

        const labels = res.data.labels ?? []
        const data = (res.data.data ?? []).map(v => Number(v || 0))

        chartByMonthData.value = {
            labels,
            datasets: [
                {
                    ...chartByMonthData.value.datasets[0],
                    label: `Assistances (${res.data.year})`,
                    data,
                },
            ],
        }

        const max = Math.max(1, ...data)
        chartByMonthOptions.value = {
            ...chartByMonthOptions.value,
            scales: {
            ...chartByMonthOptions.value.scales,
            y: {
                ...chartByMonthOptions.value.scales.y,
                suggestedMax: max + 1,
            },
            },
        }

        // loaded.value = true
        charByMonthKey.value++
    }

    function wait(ms) {
        return new Promise(resolve => setTimeout(resolve, ms))
    }

    async function loadPie() {
        const res = await axios.get('/chart/assistance-status')

        AssistancePiechartData.value.datasets[0].data = [
            Number(res.data.pending ?? 0),
            Number(res.data.approved ?? 0),
            Number(res.data.disapproved ?? 0),
            Number(res.data.cancelled ?? 0),
        ]

        chartAssistanceKey.value++
    }

    onMounted(async () => {
        // total()
        // await wait(700)
        // await loadPie();
        // await wait(700)

        // await loadChart()
        // await wait(700)
        // await loadFarmersByBrgy()

        const res = await axios.get(route('dashboard.data'), {
            params: {
                status: status.value,
                year: year.value,
            }
        })

        const data = res.data

        // Pie
        AssistanceBarChartData.value.datasets[0].data = [
            data.assistance_percentage.pending,
            data.assistance_percentage.approved,
            data.assistance_percentage.disapproved,
            data.assistance_percentage.cancelled
        ]
        chartAssistanceKey.value++

        // Monthly
        chartByMonthData.value.labels = data.assistances_by_month.labels
        chartByMonthData.value.datasets[0].data = data.assistances_by_month.data
        charByMonthKey.value++

        // Livelihood Cards
        totals.value = data.livelihood_totals
        animateCount('farmer', totals.value.farmer)
        animateCount('farm_worker', totals.value.farm_worker)
        animateCount('fisherfolks', totals.value.fisherfolks)
        animateCount('agri_youth', totals.value.agri_youth)

        // Brgy Chart
        farmersByBrgyData.value.labels = data.farmers_by_brgy.labels
        farmersByBrgyData.value.datasets[0].data = data.farmers_by_brgy.data
        chartByBrgyKey.value++
})

    watch([status, year], loadChart)

    const totals = ref({
        farmer: 0,
        farm_worker: 0,
        fisherfolks: 0,
        agri_youth: 0,
    })

    const animatedTotals = ref({
        farmer: 0,
        farm_worker: 0,
        fisherfolks: 0,
        agri_youth: 0,
    })

    function animateCount(key, toValue, duration = 800) {
        const startValue = Number(animatedTotals.value[key] || 0)
        const endValue = Number(toValue || 0)

        const start = performance.now()

        const step = (now) => {
            const t = Math.min((now - start) / duration, 1)
            const val = Math.round(startValue + (endValue - startValue) * t)
            animatedTotals.value[key] = val

            if (t < 1) requestAnimationFrame(step)
        }

        requestAnimationFrame(step)
    }

    const total = async () => {
        const res = await axios.get(route('dashboard.livelihood-totals')) // or '/dashboard/livelihood-totals'
        totals.value = res.data

        // animate each card
        animateCount('farmer', totals.value.farmer)
        animateCount('farm_worker', totals.value.farm_worker)
        animateCount('fisherfolks', totals.value.fisherfolks)
        animateCount('agri_youth', totals.value.agri_youth)
    }

    const chartByBrgyKey = ref(0)

    // Bar chart data (Brgy)
    const farmersByBrgyData = ref({
        labels: [],
        datasets: [
            {
            label: 'Registered',
            data: [],
            backgroundColor: '#3b82f6',
            borderColor: '#1d4ed8',
            borderWidth: 1,
            barThickness: 18,
            minBarLength: 4,
            },
        ],
    })

    const farmersByBrgyOptions = ref({
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' },
            title: { display: true, text: 'Registered by Barangay' },
        },
        scales: {
            y: {
            beginAtZero: true,
            ticks: { precision: 0 },
            },
            x: {
            ticks: {
                autoSkip: false,
                maxRotation: 60,
                minRotation: 60,
            },
            },
        },
    })

    const loadFarmersByBrgy = async () => {
        const res = await axios.get('/dashboard/chart/farmers-by-brgy') 
        const labels = res.data.labels ?? []
        const data = (res.data.data ?? []).map(v => Number(v || 0))

        farmersByBrgyData.value = {
            labels,
            datasets: [
            {
                ...farmersByBrgyData.value.datasets[0],
                data,
                label: 'Registered',
            },
            ],
        }

        const max = Math.max(1, ...data)

        farmersByBrgyOptions.value = {
            ...farmersByBrgyOptions.value,
            scales: {
            ...farmersByBrgyOptions.value.scales,
            y: {
                ...farmersByBrgyOptions.value.scales.y,
                suggestedMax: max + 1,
            },
            },
        }

        chartByBrgyKey.value++
    }
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="w-full mx-auto mb-4">
                <div class="flex flex-wrap justify-end">
                    <div class="w-3/12">
                        <SearchDropdown />
                    </div>
                </div>
            </div>

            <div class="flex gap-x-4 mb-4">
                <div class="w-1/4 rounded-lg bg-white shadow px-4 py-2 text-center">
                    <div class="p-4">
                        <h3 class="mb-3 font-semibold">Total Farmer</h3>
                        <div class="text-3xl font-bold">{{ animatedTotals.farmer }}</div>
                    </div>
                </div>
                <div class="w-1/4 rounded-lg bg-white shadow px-4 py-2 text-center">
                    <div class="p-4">
                        <h3 class="mb-3 font-semibold">Total Farm Worker / Laborer</h3>
                        <div class="text-3xl font-bold">{{ animatedTotals.farm_worker }}</div>
                    </div>
                </div>
                <div class="w-1/4 rounded-lg bg-white shadow px-4 py-2 text-center">
                    <div class="p-4">
                        <h3 class="mb-3 font-semibold">Total Fisherfolks</h3>
                        <div class="text-3xl font-bold">{{ animatedTotals.fisherfolks }}</div>
                    </div>
                </div>
                <div class="w-1/4 rounded-lg bg-white shadow px-4 py-2 text-center" hidden>
                    <div class="p-4">
                        <h3 class="mb-3 font-semibold">Total Agri Youth</h3>
                        <div class="text-3xl font-bold">{{ animatedTotals.agri_youth }}</div>
                    </div>
                </div>
            </div>

            <div class="flex gap-x-4 mb-4">
                <div class="w-1/3 rounded-lg bg-white shadow px-4 py-2 text-center">
                    <div class="p-4" style="height: 400px">
                        <h3 class="mb-3 font-semibold">Assistances by Status</h3>
                        <div style="height: 320px;">
                            <Bar :key="chartAssistanceKey" :data="AssistanceBarChartData" :options="chartAssistanceOptions" />
                        </div>
                    </div>
                </div>
                <div class="w-2/3 rounded-lg bg-white shadow px-4 py-2 text-center">
                    <div class="p-4" style="height: 400px">
                        <div class="flex items-center justify-between mb-3">
                            <div class="font-semibold">Assistances by Month</div>

                            <div class="w-2/12 flex justify-end gap-2">
                                <select v-model="year" class="border rounded px-2 py-1 w-[50%]">
                                    <option v-for="y in yearOptions" :key="y" :value="y">
                                        {{ y }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div style="height: 320px;">
                            <Bar ref="chartRef" :key="charByMonthKey" :data="chartByMonthData" :options="chartByMonthOptions" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full">
                <div class="bg-white rounded-lg shadow px-4 py-2 text-center">
                    <div style="height: 400px;">
                        <Bar :key="chartByBrgyKey" :data="farmersByBrgyData" :options="farmersByBrgyOptions" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
