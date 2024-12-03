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

import { onMounted, ref } from "vue";


// Uses
let params = ''


// Refs
const search = ref('')


// Props & Emit


// Computed


// Methods
function onSearch() {
    params.set('search', search.value)
    router.get(window.location.pathname + '?' + params.toString())
}


// Hooks
onMounted(() => {
    params = new URLSearchParams(window.location.search)
    search.value = params.get('search')
})


</script>


<style lang="scss" scoped>

</style>
