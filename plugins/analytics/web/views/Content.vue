<script setup lang="ts">
import Analysis from "./dashboard/analysis.vue"
import Report from "./dashboard/report.vue"
</script>
<template><el-tabs><el-tab-pane label="分析"><Analysis /></el-tab-pane><el-tab-pane label="报表" lazy><Report /></el-tab-pane></el-tabs></template>
