<template>
  <b-card>
    <b-row>
      <b-col md="3">
        <b-form-input
          v-model="filter"
          :placeholder="$t('translations::admin.search_by_key')"
        />
      </b-col>
      <b-col md="9">
        <locale-select v-model="selected" :locales="locales" class="mr-2" />
      </b-col>
    </b-row>
    <br />
    <b-table
      ref="table"
      :items="dictionary"
      :fields="fields"
      :tbody-tr-class="['cursor-pointer']"
      hover
      striped
      @row-clicked="onTableRowClick"
    >
      <template v-slot:cell(key)="{ item }">
        <b>{{ item.group }}</b>
        <span>.{{ item.key }}</span>
      </template>
      <template v-slot:cell(actions)="row">
        <b-button size="sm" @click.self="row.toggleDetails">
          {{ $t('admin.actions.edit') }}
        </b-button>
      </template>
      <template v-slot:row-details="{ item }">
        <translation-edit
          :item="item"
          :locales="selectedLocales"
          @submit="onSubmit"
        />
      </template>
    </b-table>
  </b-card>
</template>

<script>
import { HasPageHeader } from 'stylemix-vue-admin'
import filter from 'lodash-es/filter'
import TranslationEdit from './TranslationEdit'
import LocaleSelect from './LocaleSelect'

export default {
  name: 'TranslationsIndex',

  components: {
    TranslationEdit,
    LocaleSelect,
  },

  mixins: [HasPageHeader],

  pageTitle() {
    return this.$t('translations::admin.title')
  },

  data() {
    return {
      dictionaries: [],
      locales: [],
      selected: [],
      filter: '',
      fields: [],
    }
  },

  computed: {
    selectedLocales() {
      return filter(
        this.locales,
        locale => this.selected.indexOf(locale.locale) !== -1,
      )
    },
    dictionary() {
      // eslint-disable-next-line vue/no-side-effects-in-computed-properties
      const selected = this.selected.sort((first, second) =>
        first.localeCompare(second),
      )
      return this.dictionaries
        .filter(word => {
          const filterLowercase = this.filter.trim().toLowerCase()
          for (const key in word.values) {
            const value = word.values[key]
            if (value && value.toLowerCase().indexOf(filterLowercase) > -1) {
              return true
            }
          }
          return (
            `${word.group}.${word.key}`.toLowerCase().indexOf(filterLowercase) >
            -1
          )
        })
        .map(word => {
          const newValue = {}
          for (const locale of selected) {
            newValue[locale] = word.values[locale]
          }
          return { ...word, ...newValue }
        })
    },
  },

  mounted() {
    this.fetchLocales()
      .then(response => {
        this.locales = response.data
        this.selected = [this.locales[0].locale]
        this.initColumns()
        return this.fetchDictionary(this.selected)
      })
      .then(response => {
        this.dictionaries = response.data
        this.startWatchers()
      })
      .catch(err => console.error(err))
  },

  methods: {
    fetchLocales() {
      return this.$http.get('/translations/locales?all_locales=y')
    },

    fetchDictionary(langs) {
      return this.$uiBlocker(
        this.$http.get('/translations/strings', {
          params: { locales: langs.join(',') },
        }),
        this.$refs.table.$el,
      )
    },

    startWatchers() {
      this.$watch('selected', (newLocales, oldLocales) => {
        if (newLocales === oldLocales) {
          return
        }

        this.reload()
        this.initColumns()
      })
    },

    initColumns() {
      const newFields = [
        {
          key: 'namespace',
          label: this.$t('translations::admin.namespace'),
        },
        {
          key: 'key',
          label: this.$t('translations::admin.key'),
        },
      ]

      for (const locale of this.selected) {
        const lang = this.locales.find(loc => loc.locale === locale)
        newFields.push({
          key: lang.locale,
          label: lang.native,
        })
      }

      newFields.push({
        key: 'actions',
        label: this.$t('translations::admin.actions'),
      })
      this.fields = newFields
    },

    onTableRowClick(item) {
      this.$set(item, '_showDetails', !item._showDetails)
    },

    onSubmit(item) {
      const values = {}
      for (const locale of this.locales) {
        if (item[locale.locale]) values[locale.locale] = item[locale.locale]
      }

      const promise = this.$http
        .put(`translations/strings/${item.id}`, { values })
        .then(({ data }) => {
          this.dictionaries = this.dictionaries.map(word =>
            word.id === data.id ? data : word,
          )
        })
        .catch(err => this.$toast.error(err.response.message))

      this.$uiBlocker(promise, this.$refs.table.$el)
    },

    async reload() {
      try {
        const response = await this.$uiBlocker(
          this.fetchDictionary(this.selected),
          this.$refs.table.$el,
        )
        this.dictionaries = response.data
      } catch (err) {
        console.error(err)
      }
    },
  },
}
</script>
