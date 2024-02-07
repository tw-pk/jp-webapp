export default [
    {
        title: 'Teams',
        icon: {icon: 'tabler-users'},
        children: [
            {
                title: 'Manage Members',
                to: 'pages-teams-manage-members',
            },
            {
                title: 'Manage Team',
                to: 'pages-teams-manage-teams',
                subject: 'teams',
                action: 'read'
            },
        ],
        badgeContent: '',
        badgeClass: 'bg-primary',
    },
]
