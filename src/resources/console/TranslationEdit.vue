<template>
  <div>
    <b-row>
      <b-col>
        <h6>
          Translations for key:
          <code class="mr-3">
            {{ `${row.group}.${row.key}` }}
          </code>
        </h6>
      </b-col>
    </b-row>

    <b-form @submit.prevent="$emit('submit', row)">
      <b-row v-if="row._showDetails">
        <b-col v-for="locale in locales" :key="locale.locale" md="12">
          <b-form-group :label="locale.native">
            <b-form-input
              v-model="row[locale.locale]"
              :value="row[locale.locale]"
            />
          </b-form-group>
        </b-col>
      </b-row>

      <b-row>
        <b-col>
          <b-button size="sm" variant="info" type="submit">
            {{ $t('admin.actions.save') }}
          </b-button>
        </b-col>
      </b-row>
    </b-form>
  </div>
</template>

<script>
export default {
  name: 'TranslationEdit',

  props: {
    item: {
      type: Object,
      default: () => null,
    },
    locales: {
      type: Array,
      default: () => [],
    },
  },

  computed: {
    row() {
      return { ...this.item }
    },
  },
}
</script>
