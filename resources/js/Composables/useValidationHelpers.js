import { watch } from 'vue'

// Helper to resolve field from path like 'farm_parcel[0].brgy'
const getFieldState = (v$, path) => {
  return path
    .replace(/\[(\d+)\]/g, '.$1') // convert [0] to .0
    .split('.')
    .reduce((acc, part) => acc?.[part], v$.value)
}

export default function useValidationHelpers(v$, form, options = { autoTouch: false }) {
  const hasError = (path) => {
    const state = getFieldState(v$, path)
    return state?.$dirty && state?.$invalid
  }

  const inputBorderClass = (path) => {
    const state = getFieldState(v$, path)
    if (!state?.$dirty) return 'border-gray-300'
    if (state?.$invalid) return 'border-red-500'
    return 'border-green-500'
  }

  if (options.autoTouch) {
    if (form?.value && typeof form.value === 'object') {
      Object.keys(form.value).forEach((field) => {
        watch(
          () => form.value[field],
          () => {
            const state = getFieldState(v$, field)
            if (state && !state.$dirty) state.$touch()
          }
        )
      })
    } else {
      console.warn('form is not a ref or is empty')
    }
  }

//   example for dynamic fields
//   <input
//   v-model="form.farm_parcel[0].brgy"
//   @blur="getFieldState('farm_parcel[0].brgy')?.$touch()"
//   :class="inputBorderClass('farm_parcel[0].brgy')"
// />

// <p v-if="hasError('farm_parcel[0].brgy')" class="text-red-500 text-sm">
//   Barangay is required
// </p>

  return {
    hasError,
    inputBorderClass,
    getFieldState // useful for @blur events
  }
}
