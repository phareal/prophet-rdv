<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue'
import { AvatarFallback as AvatarFallbackPrimitive, type AvatarFallbackProps } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<AvatarFallbackProps & { class?: HTMLAttributes['class'] }>()
const delegatedProps = computed(() => { const { class: _, ...d } = props; return d })
</script>
<template>
  <AvatarFallbackPrimitive v-bind="delegatedProps" :class="cn('flex h-full w-full items-center justify-center rounded-full bg-muted font-semibold', props.class)">
    <slot />
  </AvatarFallbackPrimitive>
</template>
