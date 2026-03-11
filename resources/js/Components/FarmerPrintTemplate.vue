<script setup>
const props = defineProps({
    farmer: {
        type: Object,
        required: true,
    },
    photoUrl: {
        type: String,
        default: '',
    },
    dateFormat: {
        type: Function,
        default: (value) => value ?? '',
    },
    numberFormat: {
        type: Function,
        default: (value) => value ?? 0,
    },
    mainLivelihoodOptions: {
        type: Array,
        default: () => [],
    },
    checkMeta: {
        type: Function,
        default: () => false,
    },
    famerSpecify: {
        type: Function,
        default: () => '',
    },
    otherActivity: {
        type: Function,
        default: () => '',
    },
    formatFarmType: {
        type: Function,
        default: (value) => value ?? '',
    },
})

const displayText = (value, fallback = '') => {
    return value !== null && value !== undefined && value !== '' ? value : fallback
}

const yesNo = (value) => {
    if (value === 1 || value === '1' || value === true) return 'YES'
    if (value === 0 || value === '0' || value === false) return 'NO'
    return ''
}

const showSpouse = () => {
    return ['Married', 'Widowed', 'Windowed'].includes(props.farmer?.civil_status)
}
</script>

<template>
    <div id="printable-area" class="mx-auto w-full box-border bg-white p-4 text-[12px] text-black">
        <div class="mb-4 flex items-start justify-between gap-4 avoid-break">
            <div class="w-[110px] shrink-0">
                <div class="flex h-[110px] w-[110px] items-center justify-center border border-black bg-white overflow-hidden">
                    <img
                        v-if="photoUrl"
                        :src="photoUrl"
                        alt="Farmer Photo"
                        class="h-full w-full object-cover"
                    >
                    <span v-else class="px-2 text-center text-[10px] uppercase leading-tight">
                        2x2 Photo
                    </span>
                </div>
            </div>

            <div class="flex-1 text-center px-2">
                <h1 class="text-lg font-bold uppercase">
                    Hinigaran RRS Farmers and Fisherfolks Information System
                </h1>
            </div>

            <div class="w-[160px] shrink-0">
                <div class="p-2 text-xs">
                    <p class="m-0 font-semibold uppercase">Reference No.</p>
                    <p class="m-0 mt-1 border border-black px-2 py-1 text-center font-semibold uppercase">
                        {{ farmer.ref_no ?? 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="mb-3 print-section avoid-break border border-black">
            <div class="print-section-title border-b border-black bg-gray-200 px-2 py-1 font-bold uppercase">
                I. Personal Information
            </div>

            <table class="w-full border-collapse">
                <tbody>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold w-[16%]">Last Name</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.lastname) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold w-[16%]">First Name</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.firstname) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold w-[16%]">Middle Name</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.middlename) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold">Suffix</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.suffix) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">Gender</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.gender) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">Civil Status</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.civil_status) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold">Date of Birth</td>
                        <td class="border border-black px-2 py-1 uppercase">
                            {{ farmer.date_of_birth ? dateFormat(farmer.date_of_birth) : '' }}
                        </td>
                        <td class="border border-black px-2 py-1 font-semibold">Place of Birth</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.place_of_birth) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">Contact No.</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.mobile_no) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold">Religion</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.religion) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">Mother's Maiden Name</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.mothers_maiden_name) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">Household Head</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ yesNo(farmer.is_household_head) }}</td>
                    </tr>
                    <tr v-if="showSpouse()">
                        <td class="border border-black px-2 py-1 font-semibold">Name of Spouse</td>
                        <td colspan="5" class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.spouse) }}</td>
                    </tr>
                    <tr v-if="farmer.is_household_head == 0 && farmer.is_household_head != ''">
                        <td class="border border-black px-2 py-1 font-semibold">Name of Household Head</td>
                        <td colspan="2" class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.name_if_not_head) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">Relationship</td>
                        <td colspan="2" class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.is_not_head_relationship) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold">Living Household Members</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ farmer.no_of_living_members || 0 }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">Male</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ farmer.no_of_male || 0 }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">Female</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ farmer.no_of_female || 0 }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold">Highest Formal Education</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.highest_formal_education) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">PWD</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ yesNo(farmer.is_pwd) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">4P's Beneficiary</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ yesNo(farmer.is_4ps) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold">With Government ID</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ yesNo(farmer.has_gov_id) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">ID Number</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ farmer.has_gov_id == 1 ? displayText(farmer.id_no) : '' }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">Farmer Coop Member</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ yesNo(farmer.is_farmer_coop_mem) }}</td>
                    </tr>
                    <tr v-if="farmer.is_farmer_coop_mem == 1">
                        <td class="border border-black px-2 py-1 font-semibold">Association / Cooperative</td>
                        <td colspan="5" class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.is_farmer_mem) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold">Emergency Contact Person</td>
                        <td colspan="2" class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.contact_emergency) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">Emergency Contact No.</td>
                        <td colspan="2" class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.contact_no) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mb-3 print-section avoid-break border border-black">
            <div class="print-section-title border-b border-black bg-gray-200 px-2 py-1 font-bold uppercase">
                II. Address Information
            </div>

            <table class="w-full border-collapse">
                <tbody>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold w-[20%]">Region</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.region) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold w-[20%]">Province</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.province) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold">City / Municipality</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.city) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">Barangay</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.brgy) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold">House / Lot / Bldg. No.</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.lot_block_no) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">Street / Sitio / Subdv.</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.street) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mb-3 print-section avoid-break border border-black">
            <div class="print-section-title border-b border-black bg-gray-200 px-2 py-1 font-bold uppercase">
                III. Main Livelihood
            </div>

            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="border border-black px-2 py-1 text-left uppercase">Livelihood</th>
                        <th class="border border-black px-2 py-1 text-left uppercase">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="farmer.main_livelihood?.length > 0">
                        <tr v-for="(item, index) in farmer.main_livelihood" :key="index">
                            <td class="border border-black px-2 py-1 uppercase">
                                {{ item.replaceAll('_', ' ') }}
                            </td>
                            <td class="border border-black px-2 py-1 uppercase">
                                <template v-if="item === 'farmer' && farmer.main_livelihood_info?.farmer?.length">
                                    {{
                                        [
                                            checkMeta(farmer.main_livelihood_info.farmer, 'rice') ? 'Rice' : null,
                                            checkMeta(farmer.main_livelihood_info.farmer, 'corn') ? 'Corn' : null,
                                            checkMeta(farmer.main_livelihood_info.farmer, 'crops') ? `Other Crops: ${famerSpecify('crops', farmer.main_livelihood_info.farmer)}` : null,
                                            checkMeta(farmer.main_livelihood_info.farmer, 'livestock') ? `Livestock: ${famerSpecify('livestock', farmer.main_livelihood_info.farmer)}` : null,
                                            checkMeta(farmer.main_livelihood_info.farmer, 'poultry') ? `Poultry: ${famerSpecify('poultry', farmer.main_livelihood_info.farmer)}` : null,
                                        ].filter(Boolean).join(', ')
                                    }}
                                </template>

                                <template v-else-if="item === 'farm_worker' && farmer.main_livelihood_info?.farm_worker?.length">
                                    {{
                                        [
                                            checkMeta(farmer.main_livelihood_info.farm_worker, 'land preparation') ? 'Land Preparation' : null,
                                            checkMeta(farmer.main_livelihood_info.farm_worker, 'planting / transplanting') ? 'Planting / Transplanting' : null,
                                            checkMeta(farmer.main_livelihood_info.farm_worker, 'cultivation') ? 'Cultivation' : null,
                                            checkMeta(farmer.main_livelihood_info.farm_worker, 'harvesting') ? 'Harvesting' : null,
                                            checkMeta(farmer.main_livelihood_info.farm_worker, 'others') ? `Others: ${otherActivity('Others', farmer.main_livelihood_info.farm_worker)}` : null,
                                        ].filter(Boolean).join(', ')
                                    }}
                                </template>

                                <template v-else-if="item === 'fisherfolks' && farmer.main_livelihood_info?.fisherfolks?.length">
                                    {{
                                        [
                                            checkMeta(farmer.main_livelihood_info.fisherfolks, 'fish capture') ? 'Fish Capture' : null,
                                            checkMeta(farmer.main_livelihood_info.fisherfolks, 'Fish Processing') ? 'Fish Processing' : null,
                                            checkMeta(farmer.main_livelihood_info.fisherfolks, 'aquaculture') ? 'Aquaculture' : null,
                                            checkMeta(farmer.main_livelihood_info.fisherfolks, 'fish vending') ? 'Fish Vending' : null,
                                            checkMeta(farmer.main_livelihood_info.fisherfolks, 'gleaning') ? 'Gleaning' : null,
                                            checkMeta(farmer.main_livelihood_info.fisherfolks, 'others') ? `Others: ${otherActivity('Others', farmer.main_livelihood_info.fisherfolks)}` : null,
                                        ].filter(Boolean).join(', ')
                                    }}
                                </template>

                                <template v-else-if="item === 'agri_youth' && farmer.main_livelihood_info?.agri_youth?.length">
                                    {{
                                        [
                                            checkMeta(farmer.main_livelihood_info.agri_youth, 'part of a farming household') ? 'Part of a Farming Household' : null,
                                            checkMeta(farmer.main_livelihood_info.agri_youth, 'attending / attended formal agri-fishery related course') ? 'Formal Agri-Fishery Related Course' : null,
                                            checkMeta(farmer.main_livelihood_info.agri_youth, 'attending / attended non-formal agri-fishery related course') ? 'Non-Formal Agri-Fishery Related Course' : null,
                                            checkMeta(farmer.main_livelihood_info.agri_youth, 'participated a any agircultural activity / program') ? 'Participated in Agricultural Activity / Program' : null,
                                            checkMeta(farmer.main_livelihood_info.agri_youth, 'others') ? `Others: ${otherActivity('Others', farmer.main_livelihood_info.agri_youth)}` : null,
                                        ].filter(Boolean).join(', ')
                                    }}
                                </template>

                                <template v-else>
                                    -
                                </template>
                            </td>
                        </tr>
                    </template>
                    <tr v-else>
                        <td colspan="2" class="border border-black px-2 py-2 text-center uppercase">No record found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mb-3 print-section avoid-break border border-black">
            <div class="print-section-title border-b border-black bg-gray-200 px-2 py-1 font-bold uppercase">
                IV. Gross Annual Income Last Year
            </div>

            <table class="w-full border-collapse">
                <tbody>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold w-[20%]">Farming</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ farmer.farming_gross > 0 ? numberFormat(farmer.farming_gross) : numberFormat(0) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold w-[20%]">Non-farming</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ farmer.no_farming_gross > 0 ? numberFormat(farmer.no_farming_gross) : numberFormat(0) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mb-3 print-section page-break">
            <div class="print-section-title border border-black bg-gray-200 px-2 py-1 font-bold uppercase">
                V. Farm Parcel Information
            </div>

            <table class="mb-3 w-full border-collapse avoid-break">
                <tbody>
                    <tr>
                        <td class="border border-black px-2 py-1 font-semibold w-[25%]">No. of Farm Parcels</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ displayText(farmer.farm_parcel_no, 0) }}</td>
                        <td class="border border-black px-2 py-1 font-semibold w-[25%]">Agrarian Reform Beneficiary (ARB)</td>
                        <td class="border border-black px-2 py-1 uppercase">{{ yesNo(farmer.is_arb) }}</td>
                    </tr>
                </tbody>
            </table>

            <template v-if="farmer.farm_parcel?.length > 0">
                <div
                    v-for="(item, index) in farmer.farm_parcel"
                    :key="index"
                    class="mb-4 avoid-break border border-black p-2"
                >
                    <div class="mb-2 border-b border-black pb-1 font-bold uppercase">
                        Farm Parcel No. {{ index + 1 }}
                    </div>

                    <table class="mb-2 w-full border-collapse">
                        <tbody>
                            <tr>
                                <td class="border border-black px-2 py-1 font-semibold w-[20%]">Name of Farmer(s) in Rotation</td>
                                <td colspan="5" class="border border-black px-2 py-1 uppercase">{{ displayText(item.farmer_in_rotation_name) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-black px-2 py-1 font-semibold">Municipality</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ displayText(item.city) }}</td>
                                <td class="border border-black px-2 py-1 font-semibold">Barangay</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ displayText(item.brgy) }}</td>
                                <td class="border border-black px-2 py-1 font-semibold">Total Farm Area</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ displayText(item.total_farm_area) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-black px-2 py-1 font-semibold">Within Ancestral Domain</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ yesNo(item.is_whithin_ancentral_domain) }}</td>
                                <td class="border border-black px-2 py-1 font-semibold">Agrarian Reform Beneficiary</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ yesNo(item.is_agrarian_reform_beneficiary) }}</td>
                                <td class="border border-black px-2 py-1 font-semibold">Ownership Document</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ displayText(item.document) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-black px-2 py-1 font-semibold">Ownership Document No.</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ displayText(item.ownership_document_no) }}</td>
                                <td class="border border-black px-2 py-1 font-semibold">Type of Ownership</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ displayText(item.ownership_type) }}</td>
                                <td class="border border-black px-2 py-1 font-semibold">
                                    {{ item.ownership_type == 'Others' ? 'Specify' : 'Name of Land Owner' }}
                                </td>
                                <td class="border border-black px-2 py-1 uppercase">
                                    {{
                                        item.ownership_type == 'Others'
                                            ? displayText(item.is_other)
                                            : displayText(item.landowner_name)
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="border border-black px-2 py-1 text-left uppercase">Crop / Commodity</th>
                                <th class="border border-black px-2 py-1 text-left uppercase">Size (ha)</th>
                                <th class="border border-black px-2 py-1 text-left uppercase">No. of Head</th>
                                <th class="border border-black px-2 py-1 text-left uppercase">Farm Type</th>
                                <th class="border border-black px-2 py-1 text-left uppercase">Organic Practitioner</th>
                                <th class="border border-black px-2 py-1 text-left uppercase">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(v, i) in item.farm_parcel_informations" :key="i">
                                <td class="border border-black px-2 py-1 uppercase">{{ v.farming_type_name ?? '' }}</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ v.size ?? '' }}</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ v.no_of_head > 0 ? v.no_of_head : 0 }}</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ v.farm_type ? formatFarmType(v.farm_type) : '' }}</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ v.is_organic_practitioner == 1 ? 'YES' : 'NO' }}</td>
                                <td class="border border-black px-2 py-1 uppercase">{{ displayText(v.remarks) }}</td>
                            </tr>
                            <tr v-if="!item.farm_parcel_informations || item.farm_parcel_informations.length === 0">
                                <td colspan="6" class="border border-black px-2 py-2 text-center uppercase">No record found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <div v-else class="border border-black px-2 py-4 text-center uppercase">
                No farm parcel found.
            </div>
        </div>

        <div class="print-section avoid-break border border-black">
            <div class="print-section-title border-b border-black bg-gray-200 px-2 py-1 font-bold uppercase">
                VI. Certification
            </div>

            <div class="p-3">
                <p class="mb-10">
                    I hereby certify that the information provided above is true and correct to the best of my knowledge.
                </p>

                <table class="w-full border-collapse">
                    <tbody>
                        <tr>
                            <td class="w-1/3 border-0 px-4 pt-8 text-center">
                                ______________________________
                                <br>
                                FARMER SIGNATURE
                            </td>
                            <td class="w-1/3 border-0 px-4 pt-8 text-center">
                                ______________________________
                                <br>
                                ENCODER / AGRICULTURE STAFF
                            </td>
                            <td class="w-1/3 border-0 px-4 pt-8 text-center">
                                ______________________________
                                <br>
                                DATE
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style scoped>
    @media screen {
        #printable-area {
            display: block;
        }
    }
    @media print {
        @page {
            size: A4;
            margin: 10mm;
        }

        :global(html),
        :global(body) {
            background: #fff !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        :global(body *) {
            visibility: hidden;
        }

        #printable-area,
        #printable-area * {
            visibility: visible;
        }

        #printable-area {
            display: block;
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
            padding: 0;
            box-sizing: border-box;
            overflow: visible !important;
        }

        table {
            width: 100% !important;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            table-layout: fixed;
        }

        th,
        td {
            /* border: 1.2px solid #000 !important; */
            box-sizing: border-box;
            background: #fff !important;
            vertical-align: top;
        }

        .print-section {
            /* border: 1.2px solid #000 !important; */
            box-sizing: border-box;
            overflow: visible !important;
            /* page-break-inside: auto;
            break-inside: auto; */
        }

        .print-section-title {
            /* border-bottom: 1.2px solid #000 !important; */
            background: #e5e7eb !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
            /* page-break-after: avoid;
            break-after: avoid; */
        }

        .page-break {
            page-break-before: always;
            break-before: page;
        }

        .avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr,
        img {
            page-break-inside: avoid;
            break-inside: avoid;
        }
    }
</style>