<script setup lang="ts">
import AppConfirmDialog from "@/components/AppConfirmDialog.vue";
import AppToastRegion from "@/components/AppToastRegion.vue";
</script>

<template>
  <router-view />
  <AppConfirmDialog />
  <AppToastRegion />
</template>
