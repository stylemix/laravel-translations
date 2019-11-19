import Admin from 'stylemix-vue-admin'
import Axios from 'axios'

Admin.hooks.addFilter('locale-loaders', 'translations', (loaders, groups, locale) => {
  loaders.push(
    Axios.get(
      `translations/export?groups=${groups}&locale=${locale}`,
    ).then(response => response.data)
  )

  return loaders
}, 10, 3)
