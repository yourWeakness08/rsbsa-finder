<script setup>
    import { Head, Link, router } from '@inertiajs/vue3';
    import ApplicationMark from '@/Components/ApplicationMark.vue';
    import NavLink from '@/Components/NavLink.vue';
</script>

<template>
    <div class="flex">
      <!-- Backdrop -->
      <div
        :class="isOpen ? 'block' : 'hidden'"
        @click="isOpen = false"
        class="fixed inset-0 z-20 transition-opacity bg-black opacity-50 lg:hidden"
      ></div>
      <!-- End Backdrop -->
  
      <div class="-translate-x-full ease-in fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto transition duration-300 transform bg-white border-b border-gray-900 lg:translate-x-0 lg:static lg:inset-0">
        <Link class="flex items-center justify-center mt-8" :href="route('dashboard')">
          <ApplicationMark class="block h-9 w-auto" />
          <span class="mx-2 text-2xl font-semibold">RSBA</span>
        </Link>
  
        <nav class="mt-10">
            <NavLink class="flex items-center gap-3 w-full px-6 pt-3 py-3 mt-4 duration-200 border-r-4" :href="route('dashboard')" :active="route().current('dashboard')" style="border-bottom: 0 !important">
              <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 10C2 5.58172 5.58172 2 10 2V10H18C18 14.4183 14.4183 18 10 18C5.58172 18 2 14.4183 2 10Z" fill="currentColor" />
                <path d="M12 2.25195C14.8113 2.97552 17.0245 5.18877 17.748 8.00004H12V2.25195Z" fill="currentColor" />
              </svg>

              DASHBOARD
            </NavLink>
            <NavLink class="flex items-center gap-3 w-full px-6 pt-3 py-3 duration-200 border-r-4" :href="route('farmers')" :active="route().current('farmers')" style="border-bottom: 0 !important">
              <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 10C2 5.58172 5.58172 2 10 2V10H18C18 14.4183 14.4183 18 10 18C5.58172 18 2 14.4183 2 10Z" fill="currentColor" />
                <path d="M12 2.25195C14.8113 2.97552 17.0245 5.18877 17.748 8.00004H12V2.25195Z" fill="currentColor" />
              </svg>

              FARMERS
            </NavLink>
            
            <NavLink class="flex items-center gap-3 w-full px-6 pt-3 py-3 duration-200 border-r-4" :href="route('users')" :active="route().current('users')" style="border-bottom: 0 !important">
              <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 10C2 5.58172 5.58172 2 10 2V10H18C18 14.4183 14.4183 18 10 18C5.58172 18 2 14.4183 2 10Z" fill="currentColor" />
                <path d="M12 2.25195C14.8113 2.97552 17.0245 5.18877 17.748 8.00004H12V2.25195Z" fill="currentColor" />
              </svg>

              USERS
            </NavLink>
        </nav>
      </div>
    </div>
  </template>
  