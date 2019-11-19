import Admin from 'stylemix-vue-admin'
import './loader'

const base = 'translations'
Admin.addTranslationGroup(`${base}::admin`)

Admin.router.addRoutes([
  {
    path: `/${base}`,
    name: base,
    component: () => import('./Index.vue'),
    meta: {
      auth: true,
    },
  },
])

Admin.nav.push({
  id: `${base}`,
  order: 200,
  label: '$t.translations::admin.title',
  route: {
    name: `${base}`,
  },
  icon: 'icon-earth',
})
