<script setup lang="ts">
import { faCalendar, faCreditCard, faMoon, faUser } from "@fortawesome/free-regular-svg-icons";
import { faCircleHalfStroke, faHouse, faLayerGroup } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import type { IconName } from "@/Constants/shared/navigation";

// Sun icons read as a gear at small size; the half circle is the dark-mode symbol.
const ICONS = {
	home: faHouse,
	periods: faCalendar,
	installments: faCreditCard,
	templates: faLayerGroup,
	user: faUser,
	moon: faMoon,
	theme: faCircleHalfStroke,
} as const;

defineProps<{ name: IconName }>();
</script>

<template>
	<FontAwesomeIcon :icon="ICONS[name]" fixed-width class="icon" aria-hidden="true" />
</template>
