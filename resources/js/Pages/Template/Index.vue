<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import TemplateForm from "@/Components/Pages/Template/TemplateForm.vue";
import Icon from "@/Components/UI/Icon.vue";
import { freqLabel, useTemplateIndex } from "@/Composables/Template/useTemplateIndex";
import { formatCRC } from "@/Composables/shared/format";

const { error, editing, groups, load, toggle } = useTemplateIndex();
</script>

<template>
	<div class="template-index">
		<Head title="Plantillas" />
		<header class="card template-index__header">
			<div class="template-index__heading">
				<h1 class="template-index__title">Plantillas</h1>
				<p class="template-index__sub">Se aplican al crear una quincena nueva.</p>
			</div>
			<button v-if="editing !== 'new'" type="button" class="btn btn-accent" @click="editing = 'new'"><Icon name="plus" />Plantilla</button>
		</header>
		<p v-if="error" role="alert" class="alert">{{ error }}</p>
		<TemplateForm v-if="editing === 'new'" class="card" @saved="load" @cancel="editing = null" />
		<section v-for="g in groups" :key="g.key" class="card template-index__group">
			<h2 class="template-index__group-title">{{ g.label }}</h2>
			<div class="template-index__row template-index__head th">
				<span>Nombre</span><span class="num">Monto</span><span class="template-index__center">Activa</span><span class="num">Acciones</span>
			</div>
			<ul class="template-index__list">
				<li v-for="t in g.items" :key="t.id" class="template-index__item">
					<div class="template-index__row">
						<div class="template-index__main" :class="{ 'template-index__off': !t.active }">
							<span class="template-index__name">{{ t.name }}</span>
							<span class="template-index__freq">{{ freqLabel(t) }}</span>
						</div>
						<span class="num" :class="{ 'template-index__off': !t.active }">{{ formatCRC(t.estimated) }}</span>
						<label class="template-index__active" :aria-label="`Activa: ${t.name}`">
							<input type="checkbox" :checked="t.active" @change="toggle(t)" />
						</label>
						<div class="template-index__actions">
							<button type="button" class="icon-btn" :aria-label="`Editar ${t.name}`" @click="editing = t.id"><Icon name="edit" /></button>
						</div>
					</div>
					<TemplateForm v-if="editing === t.id" :template="t" @saved="load" @cancel="editing = null" />
				</li>
			</ul>
		</section>
	</div>
</template>
