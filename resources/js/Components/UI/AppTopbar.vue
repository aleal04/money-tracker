<script setup lang="ts">
import { computed } from "vue";
import { spaceFreqLabel } from "@/Composables/Space/useSpaceIndex";
import { useSpace } from "@/Composables/shared/useSpace";
import { useTheme } from "@/Composables/shared/useTheme";
import Icon from "./Icon.vue";

const { theme, toggleTheme } = useTheme();
const { spaces, current, loadSpaces, setSpace } = useSpace();
loadSpaces().catch(() => undefined);
const dark = computed(() => theme.value === "dark");
</script>

<template>
	<header class="app-topbar">
		<select
			v-if="current"
			class="input app-topbar__space"
			aria-label="Espacio"
			:value="current.id"
			@change="setSpace(Number(($event.target as HTMLSelectElement).value))"
		>
			<option v-for="s in spaces" :key="s.id" :value="s.id">{{ s.name }} · {{ spaceFreqLabel(s) }}</option>
		</select>
		<button
			type="button"
			class="icon-btn"
			:aria-label="dark ? 'Usar modo claro' : 'Usar modo oscuro'"
			:title="dark ? 'Modo claro' : 'Modo oscuro'"
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
