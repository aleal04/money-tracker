<script setup lang="ts">
import AppSidebar from "@/Components/UI/AppSidebar.vue";
import AppTopbar from "@/Components/UI/AppTopbar.vue";
</script>

<template>
	<div class="app-layout">
		<AppSidebar />
		<AppTopbar />
		<main class="app-layout__content">
			<slot />
		</main>
	</div>
</template>
