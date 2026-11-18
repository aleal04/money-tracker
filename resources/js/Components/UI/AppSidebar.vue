<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { isActive, NAV_LINKS } from "@/Constants/shared/navigation";
import Icon from "./Icon.vue";
import { useSpace } from "@/Composables/shared/useSpace";
import Logo from "./Logo.vue";

const page = usePage();
const { selectedId } = useSpace();
const hrefOf = (href: string) => (href === "/periods" && selectedId.value !== null ? `${href}?space=${selectedId.value}` : href);
</script>

<template>
	<aside class="app-sidebar">
		<Logo class="app-sidebar__logo" />
		<nav class="app-sidebar__nav" aria-label="Principal">
			<Link
				v-for="link in NAV_LINKS"
				:key="link.href"
				:href="hrefOf(link.href)"
				class="app-sidebar__link"
				:class="{ 'app-sidebar__link--active': isActive(link, page.url) }"
				:aria-current="isActive(link, page.url) ? 'page' : undefined"
				:title="link.label"
			>
				<Icon :name="link.icon" />
				<span class="app-sidebar__label">{{ link.label }}</span>
			</Link>
		</nav>
	</aside>
</template>
