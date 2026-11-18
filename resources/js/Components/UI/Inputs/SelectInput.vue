<script setup lang="ts">
import { useAttrs, useSlots } from "vue";
import VueSelect from "vue3-select-component";

/**
 * Wraps the select of vue3-select-component to show its texts in Spanish and to not be clearable by default.
 * The props, events and slots pass through to the original select.
 */
defineOptions({ name: "SelectInput", inheritAttrs: false });

type SelectProps = Parameters<typeof VueSelect>[0];

const selectProps = useAttrs() as unknown as SelectProps;
const slots = useSlots();
</script>

<template>
	<VueSelect v-bind="{ isClearable: false, ...selectProps }">
		<template v-for="(_, slotName) in slots" #[slotName]="slotProps">
			<slot :name="slotName" v-bind="slotProps ?? {}" />
		</template>
		<template v-if="!slots['no-options']" #no-options>No se encontraron resultados</template>
	</VueSelect>
</template>
