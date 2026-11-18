<script setup lang="ts">
import { computed } from "vue";
import { spaceFreqLabel } from "@/Composables/Space/useSpaceIndex";
import { useSpace } from "@/Composables/shared/useSpace";
import { useTheme } from "@/Composables/shared/useTheme";
import SelectInput from "@/Components/UI/Inputs/SelectInput.vue";
import Icon from "./Icon.vue";

const { theme, toggleTheme } = useTheme();
const { spaces, current, loadSpaces, setSpace } = useSpace();
loadSpaces().catch(() => undefined);
const spaceOptions = computed(() => spaces.value.map((s) => ({ label: `${s.name} · ${spaceFreqLabel(s)}`, value: s.id })));
const dark = computed(() => theme.value === "dark");
</script>

<template>
	<header class="app-topbar">
		<SelectInput
			v-if="current"
			class="app-topbar__space"
			:model-value="current.id"
			:options="spaceOptions"
			:input-attrs="{ 'aria-label': 'Espacio' }"
			@update:model-value="setSpace(Number($event))"
		/>
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
