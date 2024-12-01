<template>
    <div class="w-[600px] h-[80px] flex items-center">
        <TextInput
            type="text"
            class="block w-full mr-2"
            v-model="search"
            autocomplete
            @keyup.enter.prevent="onSearch"
            placeholder="Search for files and folders"
        />
    </div>
</template>


<script setup>
// Imports
import { router } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';

import { ref } from "vue";


// Uses


// Refs
const search = ref('')


// Props & Emit


// Computed


// Methods
function onSearch() {
    const params = new URLSearchParams(window.location.search)
    params.set('search', search.value)
    router.get(window.location.pathname + '?' + params.toString())
}


// Hooks


</script>


<style lang="scss" scoped>

</style>
