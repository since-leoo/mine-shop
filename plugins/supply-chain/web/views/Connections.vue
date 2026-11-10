<script setup lang="ts">
import PluginAvailability from './PluginAvailability.vue'
const loader = () => import('./ConnectionSettings.vue')
</script>

<template>
  <PluginAvailability :loader="loader" />
</template>
