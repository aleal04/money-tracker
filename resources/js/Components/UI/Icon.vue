<script setup lang="ts">
import { faCalendar, faCreditCard, faMoon, faUser } from "@fortawesome/free-regular-svg-icons";
import {
	faCheck,
	faChevronLeft,
	faChevronRight,
	faCircleHalfStroke,
	faHouse,
	faLayerGroup,
	faLock,
	faLockOpen,
	faPen,
	faPlus,
} from "@fortawesome/free-solid-svg-icons";
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
	left: faChevronLeft,
	right: faChevronRight,
	lock: faLock,
	unlock: faLockOpen,
	plus: faPlus,
	check: faCheck,
	edit: faPen,
} as const;

defineProps<{ name: IconName }>();
</script>

<template>
	<FontAwesomeIcon :icon="ICONS[name]" fixed-width class="icon" aria-hidden="true" />
</template>
