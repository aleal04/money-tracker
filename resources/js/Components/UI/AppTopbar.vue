<script setup lang="ts">
import { computed } from "vue";
import { useTheme } from "@/Composables/shared/useTheme";
import Icon from "./Icon.vue";

const { theme, toggleTheme } = useTheme();
const dark = computed(() => theme.value === "dark");
</script>

<template>
	<header class="app-topbar">
		<button
			type="button"
			class="icon-btn"
			:aria-label="dark ? 'Usar modo claro' : 'Usar modo oscuro'"
			:title="dark ? 'Modo claro' : 'Modo oscuro'"
			:aria-pressed="dark"
			@click="toggleTheme"
		>
			<Icon :name="dark ? 'theme' : 'moon'" />
		</button>
		<div class="app-topbar__account">
			<span class="app-topbar__avatar"><Icon name="user" /></span>
			<span class="app-topbar__name">Mi cuenta</span>
		</div>
	</header>
</template>
