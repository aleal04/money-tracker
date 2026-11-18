<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import CardSection from "@/Components/Pages/Installment/CardSection.vue";
import Icon from "@/Components/UI/Icon.vue";
import { useInstallmentIndex } from "@/Composables/Installment/useInstallmentIndex";

const { cards, error, editing, newCard, cardName, cardError, saved, cancelSoon, saveCard } = useInstallmentIndex();
</script>

<template>
	<div class="installment-index">
		<Head title="Cuotas" />
		<header class="card installment-index__header">
			<div class="installment-index__heading">
				<h1 class="installment-index__title">Cuotas</h1>
				<p class="installment-index__sub">{{ cards.length }} tarjetas</p>
			</div>
			<button v-if="!newCard" type="button" class="btn btn-accent" @click="newCard = true"><Icon name="plus" />Tarjeta</button>
		</header>
		<p v-if="error" role="alert" class="alert">{{ error }}</p>
		<form v-if="newCard" class="form-panel card" @submit.prevent="saveCard">
			<label class="field">Nombre de la tarjeta
				<input v-model="cardName" class="input" />
				<span v-if="cardError" role="alert" class="err">{{ cardError }}</span>
			</label>
			<div class="form-actions">
				<button class="btn btn-primary">Guardar</button>
				<button type="button" class="btn btn-ghost" @click="newCard = false">Cancelar</button>
			</div>
		</form>
		<CardSection v-for="c in cards" :key="c.id" :card="c" :editing="editing" @edit="editing = $event" @saved="saved" @cancel-soon="cancelSoon" />
	</div>
</template>
