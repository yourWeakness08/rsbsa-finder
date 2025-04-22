import debounce from '../Utils/debounce' // or import { debounce } from 'lodash'

export default {
  install(app) {
    app.config.globalProperties.$debounce = debounce
  }
}