<script setup lang="ts">
import { CreditCard } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
</script>

<template>
  <AdminLayout>
    <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
      <div class="rounded-full bg-gray-100 p-6 mb-6">
        <CreditCard class="w-12 h-12 text-gray-400" />
      </div>
      <h1 class="text-2xl font-semibold text-gray-700 mb-2">Coming Soon</h1>
      <p class="text-gray-500 max-w-md">
        Payment transaction history will be available once Billplz integration is configured.
      </p>
    </div>
  </AdminLayout>
</template>
