<template>
  <div class="h-screen w-screen flex overflow-hidden bg-slate-100 font-sans">
    <!-- Left Green Sidebar (matching screenshot) -->
    <Sidebar />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
      <!-- Top Navigation & Search Bar -->
      <TopHeader />

      <!-- Dynamic Main View -->
      <main class="flex-1 relative overflow-hidden">
        <transition name="fade-fast" mode="out-in">
          <DashboardView v-if="activeMenu === 'dashboard'" key="dashboard" />
          <MembersView v-else-if="activeMenu === 'members'" key="members" />
          <FamilyTreeView v-else-if="activeMenu === 'tree'" key="tree" />
          <SemanticSearchView v-else-if="activeMenu === 'search'" key="search" />
          <ReasoningView v-else-if="activeMenu === 'reasoning'" key="reasoning" />
        </transition>
      </main>
    </div>

    <!-- Add Member Modal -->
    <AddMemberModal />

    <!-- Member Detail Drawer -->
    <MemberDetailDrawer />
  </div>
</template>

<script setup>
import { useFamilyStore } from './composables/useFamilyStore.js'
import Sidebar from './components/Sidebar.vue'
import TopHeader from './components/TopHeader.vue'
import DashboardView from './components/DashboardView.vue'
import MembersView from './components/MembersView.vue'
import FamilyTreeView from './components/FamilyTreeView.vue'
import SemanticSearchView from './components/SemanticSearchView.vue'
import ReasoningView from './components/ReasoningView.vue'
import AddMemberModal from './components/AddMemberModal.vue'
import MemberDetailDrawer from './components/MemberDetailDrawer.vue'

const { activeMenu } = useFamilyStore()
</script>

<style>
.fade-fast-enter-active,
.fade-fast-leave-active {
  transition: opacity 0.15s ease;
}

.fade-fast-enter-from,
.fade-fast-leave-to {
  opacity: 0;
}
</style>

