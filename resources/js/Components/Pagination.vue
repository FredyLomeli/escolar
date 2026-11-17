<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types';

defineProps<{
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}>();
</script>

<template>
    <div
        v-if="total > 0"
        class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 bg-white px-4 py-3 sm:flex-row sm:px-6"
    >
        <p class="text-sm text-gray-600">
            Mostrando <span class="font-medium text-gray-900">{{ from ?? 0 }}</span> a
            <span class="font-medium text-gray-900">{{ to ?? 0 }}</span> de
            <span class="font-medium text-gray-900">{{ total }}</span> resultados
        </p>

        <nav v-if="links.length > 3" class="inline-flex -space-x-px rounded-md shadow-sm" aria-label="Paginación">
            <template v-for="(link, idx) in links" :key="idx">
                <span
                    v-if="link.url === null"
                    class="relative inline-flex items-center border border-gray-300 bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-400 first:rounded-l-md last:rounded-r-md"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    :class="[
                        'relative inline-flex items-center border px-3 py-1.5 text-sm font-medium transition first:rounded-l-md last:rounded-r-md focus:z-10 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500',
                        link.active
                            ? 'z-10 border-indigo-600 bg-indigo-600 text-white'
                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                    ]"
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
