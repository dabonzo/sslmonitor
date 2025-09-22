<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useThemeStore } from '@/stores/theme'
import AppLogoIcon from '@/components/AppLogoIcon.vue'
import { ChevronDown } from 'lucide-vue-next'
import { mainMenuItems, bottomMenuItems, isActiveRoute } from '@/config/navigation'

const themeStore = useThemeStore()

// Active dropdown state
const activeDropdown = ref<string>('')

// Sub-menus are always visible when dropdown is active
const shouldShowSubMenus = computed(() => {
  return true
})

function toggleDropdown(key: string) {
  activeDropdown.value = activeDropdown.value === key ? '' : key
}
</script>

<template>
  <nav
    v-if="themeStore.menu !== 'horizontal'"
    class="sidebar fixed bottom-0 top-0 z-50 h-full min-h-screen w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] transition-all duration-300 bg-sidebar text-sidebar-foreground"
    :class="{
      'ltr:-left-[260px] rtl:right-[260px]': !themeStore.sidebarOpen,
      'ltr:left-0 rtl:right-0': themeStore.sidebarOpen
    }"
  >
    <div class="h-full bg-sidebar">
      <!-- Logo section -->
      <div class="flex items-center justify-between px-4 py-3">
        <Link href="/dashboard" class="main-logo flex shrink-0 items-center">
          <AppLogoIcon class="ml-[5px] h-8 w-8 flex-none fill-current text-primary" />
          <span
            class="text-2xl font-semibold align-middle text-sidebar-primary ltr:ml-1.5 rtl:mr-1.5 text-sidebar-foreground"
          >
            SSL Monitor
          </span>
        </Link>
      </div>

      <!-- Navigation menu -->
      <div class="perfect-scrollbar relative h-[calc(100vh-60px)] overflow-y-auto overflow-x-hidden p-4 py-0">
        <ul class="relative space-y-0.5 p-4 py-0 font-semibold">

          <!-- Main menu items -->
          <template v-for="item in mainMenuItems" :key="item.key">
            <li class="menu nav-item">
              <!-- Direct link items -->
              <Link
                v-if="item.href"
                :href="item.href"
                class="nav-link group w-full"
                :class="{ 'active': isActiveRoute(item.href) }"
              >
                <div class="flex items-center">
                  <component :is="item.icon" class="shrink-0 group-hover:!text-primary" />
                  <div class="ltr:pl-3 rtl:pr-3">
                    <span class="text-sidebar-foreground group-hover:text-sidebar-primary">
                      {{ item.title }}
                    </span>
                    <div v-if="item.description" class="text-xs text-sidebar-muted">
                      {{ item.description }}
                    </div>
                  </div>
                </div>
              </Link>

              <!-- Dropdown items -->
              <template v-else>
                <button
                  type="button"
                  class="nav-link group w-full"
                  :class="{ 'active': activeDropdown === item.key }"
                  @click="toggleDropdown(item.key)"
                >
                  <div class="flex items-center">
                    <component :is="item.icon" class="shrink-0 group-hover:!text-primary" />
                    <span class="text-sidebar-foreground group-hover:text-sidebar-primary ltr:pl-3 rtl:pr-3">
                      {{ item.title }}
                    </span>
                  </div>

                  <div
                    class="rtl:rotate-180 transition-transform duration-200"
                    :class="{ '!rotate-90': activeDropdown === item.key }"
                  >
                    <ChevronDown class="h-4 w-4" />
                  </div>
                </button>

                <Transition name="slide-down">
                  <ul v-show="activeDropdown === item.key && shouldShowSubMenus" class="sub-menu text-gray-500">
                    <li v-for="child in item.children" :key="child.href">
                      <Link
                        :href="child.href"
                        :class="{ 'active': isActiveRoute(child.href) }"
                      >
                        {{ child.title }}
                      </Link>
                    </li>
                  </ul>
                </Transition>
              </template>
            </li>
          </template>

          <!-- Separator -->
          <li class="h-px w-full bg-white-light dark:bg-[#1b2e4b] my-4"></li>

          <!-- Bottom menu items -->
          <template v-for="item in bottomMenuItems" :key="item.key">
            <li class="menu nav-item">
              <Link
                :href="item.href"
                class="nav-link group w-full"
                :class="{ 'active': isActiveRoute(item.href) }"
              >
                <div class="flex items-center">
                  <component :is="item.icon" class="shrink-0 group-hover:!text-primary" />
                  <div class="ltr:pl-3 rtl:pr-3">
                    <span class="text-sidebar-foreground group-hover:text-sidebar-primary">
                      {{ item.title }}
                    </span>
                    <div v-if="item.description" class="text-xs text-sidebar-muted">
                      {{ item.description }}
                    </div>
                  </div>
                </div>
              </Link>
            </li>
          </template>

        </ul>
      </div>
    </div>
  </nav>
</template>

<style scoped>
/* Navigation styles */
.nav-link {
  display: flex;
  cursor: pointer;
  align-items: center;
  justify-content: space-between;
  border-radius: 0.375rem;
  padding: 0.5rem;
  transition: all 0.2s ease;
}

.nav-link:hover {
  background-color: rgb(248 250 252);
  color: rgb(67 97 238);
}

.dark .nav-link:hover {
  background-color: rgb(24 31 50);
  color: rgb(67 97 238);
}

.nav-link.active {
  background-color: rgb(243 244 246);
  color: rgb(67 97 238);
}

.dark .nav-link.active {
  background-color: rgb(24 31 50);
  color: rgb(67 97 238);
}

.sub-menu {
  margin-left: 1.5rem;
  list-style: none;
}

.sub-menu li {
  margin-bottom: 0.25rem;
  list-style: none;
}

.sub-menu a {
  display: block;
  border-radius: 0.375rem;
  padding: 0.5rem;
  transition: all 0.2s ease;
}

.sub-menu a:hover {
  background-color: rgb(243 244 246);
  color: rgb(67 97 238);
}

.dark .sub-menu a:hover {
  background-color: rgb(24 31 50);
  color: rgb(67 97 238);
}

.sub-menu a.active {
  background-color: rgb(243 244 246);
  color: rgb(67 97 238);
}

.dark .sub-menu a.active {
  background-color: rgb(24 31 50);
  color: rgb(67 97 238);
}

/* Slide down animation */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
  opacity: 1;
  max-height: 500px;
}

/* Scrollbar styling */
.perfect-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.perfect-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.perfect-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.dark .perfect-scrollbar::-webkit-scrollbar-thumb {
  background: #475569;
}
</style>