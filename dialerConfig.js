import {defineDialerConfig} from "@core";
import {AppContentLayoutNav, ContentWidth} from "@layouts/enums";
import {RouteTransitions, Skins} from "@core/enums";
import { breakpointsVuetify } from '@vueuse/core'
import { VIcon } from 'vuetify/components/VIcon'


export const { dialerConfig, dialerLayoutConfig } = defineDialerConfig({
    dialer: {
        enableI18n: true,
        status: 'dialer available',
        iconRenderer: VIcon,
    },
})
