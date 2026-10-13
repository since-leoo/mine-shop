<script setup lang="ts">
import OptionalPluginPage from "@/components/optional-plugin-page/index.vue"
const loader = () => import('../../../../../../plugins/analytics/web/views/dashboard/workbench.vue')
</script>
<template><OptionalPluginPage plugin="since/analytics" :loader="loader" /></template>
