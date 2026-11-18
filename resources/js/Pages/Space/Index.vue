<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import SpaceForm from "@/Components/Pages/Space/SpaceForm.vue";
import Icon from "@/Components/UI/Icon.vue";
import { spaceFreqLabel, useSpaceIndex } from "@/Composables/Space/useSpaceIndex";
import { formatCRC } from "@/Composables/shared/format";

const { spaces, error, editing, busy, saved, makePrimary } = useSpaceIndex();
</script>

<template>
	<div class="space-index">
		<Head title="Espacios" />
		<header class="card space-index__header">
			<div class="space-index__heading">
				<h1 class="space-index__title">Espacios</h1>
				<p class="space-index__sub">Cada espacio tiene su propio saldo, periodos, plantillas y tarjetas.</p>
			</div>
			<button v-if="editing !== 'new'" type="button" class="btn btn-accent" @click="editing = 'new'"><Icon name="plus" />Espacio</button>
		</header>
		<p v-if="error" role="alert" class="alert">{{ error }}</p>
		<SpaceForm v-if="editing === 'new'" class="card" @saved="saved" @cancel="editing = null" />
		<section class="card space-index__group">
			<ul class="space-index__list">
				<li v-for="s in spaces" :key="s.id" class="space-index__item">
					<div class="space-index__row">
						<div class="space-index__main">
							<span class="space-index__name">{{ s.name }}</span>
							<span v-if="s.isPrimary" class="pill space-index__badge">Principal</span>
							<span class="space-index__freq">{{ spaceFreqLabel(s) }} · Saldo inicial {{ formatCRC(s.initialBalance) }}</span>
						</div>
						<button v-if="!s.isPrimary" type="button" :disabled="busy" class="btn btn-ghost" @click="makePrimary(s)">Hacer principal</button>
						<button type="button" class="icon-btn" :aria-label="`Editar ${s.name}`" @click="editing = s.id"><Icon name="edit" /></button>
					</div>
					<SpaceForm v-if="editing === s.id" :space="s" @saved="saved" @cancel="editing = null" />
				</li>
			</ul>
		</section>
	</div>
</template>
