<template>
  <b-dropdown ref="dropdown" :text="selectedText" variant="light">
    <b-dropdown-form>
      <b-form-checkbox
        v-for="locale in locales"
        v-model="selected"
        :key="locale.locale"
        :value="locale.locale"
        size="sm"
        @change="onInput"
      >
        {{ locale.native }}
      </b-form-checkbox>
    </b-dropdown-form>
  </b-dropdown>
</template>

<script>
import filter from 'lodash-es/filter'
import map from 'lodash-es/map'
import truncate from 'lodash-es/truncate'

export default {
  name: 'LocaleSelect',

  props: {
    locales: {
      type: Array,
      required: true,
    },
    value: {
      type: Array,
      default: () => [],
    },
  },

  data() {
    return {
      selected: [].concat(this.value),
    }
  },

  computed: {
    selectedText() {
      if (!this.selected.length) {
        return '-'
      }
      const locales = map(
        filter(
          this.locales,
          locale => this.selected.indexOf(locale.locale) !== -1,
        ),
        'native',
      )
      return (
        truncate(locales.join(', '), {
          length: 60,
        }) + ` (${this.selected.length})`
      )
    },
  },

  watch: {
    value() {
      this.selected = [].concat(this.value)
    },
  },

  methods: {
    onInput() {
      setTimeout(() => {
        this.$emit('input', this.selected)
      }, 0)
    },
  },
}
</script>
