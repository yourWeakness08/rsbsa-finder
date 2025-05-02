<script setup>
    import { Link } from '@inertiajs/vue3';
    import ApplicationMark from '@/Components/ApplicationMark.vue';
    import NavLink from '@/Components/NavLink.vue';
</script>

<template>
    <div class="flex">
      <!-- Backdrop -->
      <!-- <div
        :class="isOpen ? 'block' : 'hidden'"
        @click="isOpen = false"
        class="fixed inset-0 z-20 transition-opacity bg-black opacity-50 lg:hidden"
      ></div> -->
      <!-- End Backdrop -->
  
      <div class="-translate-x-full ease-in fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto transition duration-300 transform bg-white lg:translate-x-0 lg:static lg:inset-0">
        <Link class="flex flex-wrap items-center justify-center mt-8" :href="route('dashboard')">
          <div class="w-full">
            <ApplicationMark class="block h-24 w-24 mx-auto" />
          </div>
          <span class="mx-2 text-2xl w-12/12 font-semibold">RSBA</span>
        </Link>
  
        <nav class="mt-5">
            <NavLink class="flex items-center gap-3 w-full px-6 pt-3 py-3 mt-4 duration-200 border-r-4" :href="route('dashboard')" :active="route().current('dashboard')" style="border-bottom: 0 !important">
              <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 10C2 5.58172 5.58172 2 10 2V10H18C18 14.4183 14.4183 18 10 18C5.58172 18 2 14.4183 2 10Z" fill="currentColor" />
                <path d="M12 2.25195C14.8113 2.97552 17.0245 5.18877 17.748 8.00004H12V2.25195Z" fill="currentColor" />
              </svg>

              DASHBOARD
            </NavLink>
            <NavLink class="flex items-center gap-3 w-full px-6 pt-3 py-3 duration-200 border-r-4" :href="route('farmers.index')" :active="route().current('farmers.index') || route().current('farmers.create')" style="border-bottom: 0 !important">
              <svg fill="#000000" class="w-5 h-5" viewBox="0 0 14 14" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                <g id="SVGRepo_iconCarrier">
                  <path d="M 5.5075,1 C 4.7215,1 4.31614,2.06224 3.8875,3.19 3.74326,3.56896 3.5983,3.9556 3.4375,4.3 4.44814,3.95128 5.662,3.7525 7,3.7525 c 1.35072,0 2.57658,0.20004 3.5925,0.555 C 10.44946,3.9931 10.3132,3.64738 10.18,3.3025 9.72328,2.11906 9.29242,1 8.4925,1 8.1145,1 7.849,1.21588 7.615,1.405 7.4086,1.5718 7.23208,1.7125 7,1.7125 6.76792,1.7125 6.5914,1.5718 6.385,1.405 6.15076,1.21588 5.88574,1 5.5075,1 Z M 7,4.2325 C 3.63544,4.2325 1,5.5648 1,7.27 1,8.33344 2.03992,9.29632 3.79,9.85 4.43608,11.36224 5.4304,13 7,13 8.56984,13 9.56392,11.35522 10.21,9.8425 11.95864,9.28858 13,8.33248 13,7.27 13,5.56504 10.36456,4.2325 7,4.2325 Z M 7,6.28 c 2.7432,0 3.09192,0.71376 3.12,0.78 l 0,0.57 0.2775,-0.0375 c 0.0017,0 0.1737,-0.01998 0.2925,0.0825 0.12504,0.10824 0.15,0.32064 0.15,0.48 0,0.34824 -0.25848,0.63174 -0.6,0.6675 L 10.0975,8.8375 10.045,8.98 C 9.13972,11.36296 8.14096,12.52 7,12.52 5.85928,12.52 4.8673,11.36272 3.9625,8.98 L 3.91,8.8375 3.76,8.8225 C 3.41728,8.7877 3.16,8.5042 3.16,8.155 3.16,7.93636 3.2116,7.77904 3.31,7.69 3.43336,7.5784 3.60952,7.59298 3.61,7.5925 l 0.27,0.03 0,-0.555 C 3.9148,6.97942 4.28152,6.28 7,6.28 Z"></path>
                </g>
              </svg>

              FARMERS
            </NavLink>

            <NavLink class="flex items-center gap-3 w-full px-6 pt-3 py-3 duration-200 border-r-4" :href="route('types.index')" :active="route().current('types.index')" style="border-bottom: 0 !important">
              <svg fill="#000000" class="w-5 h-5" viewBox="0 0 96 96" id="Layer_1_1_" version="1.1" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                <g id="SVGRepo_iconCarrier">
                  <polygon points="20,94 26,94 26,84 44,84 44,82 36,82 36,79 35,79 34,79 34,82 26,82 26,70 44,70 44,68 36,68 36,65 35,65 34,65 34,68 26,68 26,56 44,56 44,54 36,54 36,51 35,51 34,51 34,54 26,54 26,42 44,42 44,40 36,40 36,37 35,37 34,37 34,40 26,40 26,26 24,26 24,24 25,24 25,20 21,20 21,24 22,24 22,26 20,26 20,36 12,36 12,33 11,33 10,33 10,36 2,36 2,38 20,38 20,50 12,50 12,47 11,47 10,47 10,50 2,50 2,52 20,52 20,64 12,64 12,61 11,61 10,61 10,64 2,64 2,66 20,66 20,79 12,79 12,75 11,75 10,75 10,79 2,79 2,81 20,81 "></polygon>
                  <path d="M12,9.439c2.017,0,3.669-1.498,3.944-3.439h16.113C32.331,7.941,33.983,9.439,36,9.439S39.669,7.941,39.944,6h16.113 C56.331,7.941,57.983,9.439,60,9.439S63.669,7.941,63.944,6h17.113C81.331,7.941,82.983,9.439,85,9.439S88.669,7.941,88.944,6H93V2 H3v4h5.056C8.331,7.941,9.983,9.439,12,9.439z"></path>
                  <rect height="3" width="2" x="11" y="12"></rect>
                  <rect height="3" transform="matrix(0.7071 -0.7071 0.7071 0.7071 -4.1348 15.0177)" width="2" x="15.061" y="11"></rect>
                  <rect height="2" transform="matrix(0.7071 -0.7071 0.7071 0.7071 -6.5134 9.2752)" width="3" x="6.44" y="11.5"></rect>
                  <rect height="3" width="2" x="34.879" y="12"></rect>
                  <rect height="3" transform="matrix(0.7071 -0.7071 0.7071 0.7071 2.8592 31.9026)" width="2" x="38.94" y="11"></rect><rect height="2" transform="matrix(0.7071 -0.7071 0.7071 0.7071 0.4805 26.1601)" width="3" x="30.319" y="11.5"></rect>
                  <rect height="3" width="2" x="59" y="12"></rect>
                  <rect height="3" transform="matrix(0.7071 -0.7071 0.7071 0.7071 9.9241 48.9588)" width="2" x="63.061" y="11"></rect>
                  <rect height="2" transform="matrix(0.7071 -0.7071 0.7071 0.7071 7.5455 43.2163)" width="3" x="54.44" y="11.5"></rect>
                  <rect height="3" width="2" x="84.121" y="12"></rect>
                  <rect height="3" transform="matrix(0.7071 -0.7071 0.7071 0.7071 17.2819 66.7221)" width="2" x="88.182" y="11"></rect>
                  <rect height="2" transform="matrix(0.7071 -0.7071 0.7071 0.7071 14.9032 60.9796)" width="3" x="79.561" y="11.5"></rect>
                  <path d="M5,27c0,3.314,2.686,6,6,6C11,29.686,8.314,27,5,27z"></path>
                  <path d="M17,27c-3.314,0-6,2.686-6,6C14.314,33,17,30.314,17,27z"></path>
                  <path d="M5,41c0,3.314,2.686,6,6,6C11,43.686,8.314,41,5,41z"></path>
                  <path d="M17,41c-3.314,0-6,2.686-6,6C14.314,47,17,44.314,17,41z"></path>
                  <path d="M5,55c0,3.314,2.686,6,6,6C11,57.686,8.314,55,5,55z"></path>
                  <path d="M17,55c-3.314,0-6,2.686-6,6C14.314,61,17,58.314,17,55z"></path>
                  <path d="M5,69c0,3.314,2.686,6,6,6C11,71.686,8.314,69,5,69z"></path>
                  <path d="M17,69c-3.314,0-6,2.686-6,6C14.314,75,17,72.314,17,69z"></path>
                  <path d="M80,27c0,3.314,2.686,6,6,6C86,29.686,83.314,27,80,27z"></path>
                  <path d="M92,27c-3.314,0-6,2.686-6,6C89.314,33,92,30.314,92,27z"></path>
                  <polygon points="87,33 86,33 85,33 85,36 78,36 78,26 76,26 76,24 77,24 77,20 73,20 73,24 74,24 74,26 72,26 72,40 64,40 64,37 63,37 62,37 62,40 53,40 53,42 72,42 72,54 64,54 64,51 63,51 62,51 62,54 53,54 53,56 72,56 72,68 64,68 64,65 63,65 62,65 62,68 53,68 53,70 72,70 72,82 64,82 64,79 63,79 62,79 62,82 53,82 53,84 72,84 72,94 78,94 78,81 94,81 94,79 87,79 87,75 86,75 85,75 85,79 78,79 78,66 94,66 94,64 87,64 87,61 86,61 85,61 85,64 78,64 78,52 94,52 94,50 87,50 87,47 86,47 85,47 85,50 78,50 78,38 94,38 94,36 87,36 "></polygon>
                  <path d="M80,41c0,3.314,2.686,6,6,6C86,43.686,83.314,41,80,41z"></path>
                  <path d="M92,41c-3.314,0-6,2.686-6,6C89.314,47,92,44.314,92,41z"></path>
                  <path d="M80,55c0,3.314,2.686,6,6,6C86,57.686,83.314,55,80,55z"></path>
                  <path d="M92,55c-3.314,0-6,2.686-6,6C89.314,61,92,58.314,92,55z"></path>
                  <path d="M80,69c0,3.314,2.686,6,6,6C86,71.686,83.314,69,80,69z"></path>
                  <path d="M92,69c-3.314,0-6,2.686-6,6C89.314,75,92,72.314,92,69z"></path>
                  <path d="M29,31c0,3.314,2.686,6,6,6C35,33.686,32.314,31,29,31z"></path>
                  <path d="M41,31c-3.314,0-6,2.686-6,6C38.314,37,41,34.314,41,31z"></path>
                  <path d="M29,45c0,3.314,2.686,6,6,6C35,47.686,32.314,45,29,45z"></path>
                  <path d="M41,45c-3.314,0-6,2.686-6,6C38.314,51,41,48.314,41,45z"></path>
                  <path d="M29,59c0,3.314,2.686,6,6,6C35,61.686,32.314,59,29,59z"></path>
                  <path d="M41,59c-3.314,0-6,2.686-6,6C38.314,65,41,62.314,41,59z"></path>
                  <path d="M29,73c0,3.314,2.686,6,6,6C35,75.686,32.314,73,29,73z"></path>
                  <path d="M41,73c-3.314,0-6,2.686-6,6C38.314,79,41,76.314,41,73z"></path>
                  <path d="M57,31c0,3.314,2.686,6,6,6C63,33.686,60.314,31,57,31z"></path>
                  <path d="M69,31c-3.314,0-6,2.686-6,6C66.314,37,69,34.314,69,31z"></path>
                  <path d="M57,45c0,3.314,2.686,6,6,6C63,47.686,60.314,45,57,45z"></path>
                  <path d="M69,45c-3.314,0-6,2.686-6,6C66.314,51,69,48.314,69,45z"></path>
                  <path d="M57,59c0,3.314,2.686,6,6,6C63,61.686,60.314,59,57,59z"></path>
                  <path d="M69,59c-3.314,0-6,2.686-6,6C66.314,65,69,62.314,69,59z"></path>
                  <path d="M57,73c0,3.314,2.686,6,6,6C63,75.686,60.314,73,57,73z"></path>
                  <path d="M69,73c-3.314,0-6,2.686-6,6C66.314,79,69,76.314,69,73z"></path>
                </g>
              </svg>

              FARMING TYPES
            </NavLink>
            
            <NavLink class="flex items-center gap-3 w-full px-6 pt-3 py-3 duration-200 border-r-4" :href="route('users.index')" :active="route().current('users.index')" style="border-bottom: 0 !important">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                <g id="SVGRepo_iconCarrier"> 
                  <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> 
                  <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> 
                </g>
              </svg>

              USERS
            </NavLink>
        </nav>
      </div>
    </div>
  </template>
  