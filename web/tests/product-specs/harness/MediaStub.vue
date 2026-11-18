<script setup lang="ts">
defineProps<{ modelValue?: any; initialValue?: any; multiple?: boolean; limit?: number; height?: number }>()
defineEmits<{ (e: 'update:modelValue', value: any): void }>()
</script>
<template><span /></template>
