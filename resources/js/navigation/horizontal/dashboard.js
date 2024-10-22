export default [
  {
    title: 'Dashboard',
    icon: { icon: 'tabler-layout-dashboard' },
    children: [
      {
        title: 'Analytics',
        to: 'dashboard',
        icon: { icon: 'tabler-chart-pie-2' },
      },
      {
        title: 'eCommerce',
        to: 'dashboard-ecommerce',
        icon: { icon: 'tabler-atom-2' },
      },
      {
        title: 'CRM',
        to: 'dashboard-crm',
        icon: { icon: 'tabler-3d-cube-sphere' },
      },
    ],
  },
]
