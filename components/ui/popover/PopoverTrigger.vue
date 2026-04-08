<script setup lang="ts">
import { PopoverTrigger, type PopoverTriggerProps } from 'radix-vue'
defineProps<PopoverTriggerProps>()
</script>
<template>
  <PopoverTrigger v-bind="$props"><slot /></PopoverTrigger>
</template>
