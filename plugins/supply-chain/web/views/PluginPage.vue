<script setup lang="ts">
import PluginAvailability from './PluginAvailability.vue'
const loader = () => import('./Content.vue')
</script>

<template>
  <PluginAvailability :loader="loader" />
</template>
