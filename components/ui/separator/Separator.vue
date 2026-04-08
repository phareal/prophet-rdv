<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { Separator } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
  orientation?: 'horizontal' | 'vertical'
  decorative?: boolean
}>()
</script>

<template>
  <Separator
    :orientation="props.orientation ?? 'horizontal'"
    :decorative="props.decorative ?? true"
    :class="cn(
      'shrink-0 bg-border',
      props.orientation === 'vertical' ? 'h-full w-[1px]' : 'h-[1px] w-full',
      props.class,
    )"
  />
</template>
