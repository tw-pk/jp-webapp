import { Device } from '@twilio/voice-sdk'

let device = null

export function createDevice(token) {
  device = new Device(token)

  // device.ready(() => {
  //     console.log('ready')
  // })
  return device
}

export function getDevice() {
  return device
}
