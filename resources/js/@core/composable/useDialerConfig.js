import { themeConfig } from "@themeConfig"
import { dialerConfig } from "../../../../dialerConfig"


export const useDialerConfig = () => {
  const dialer = computed({
    get(){
      return dialerConfig.dialer.status.value
    },
    set(value) {
      dialerConfig.dialer.status.value = value
      localStorage.setItem(`${themeConfig.app.title}-dialer`, value.toString())
    },
  })

  return {
    dialer,
  }
}
