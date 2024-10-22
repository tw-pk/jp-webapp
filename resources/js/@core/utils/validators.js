import { isEmpty, isEmptyArray, isNullOrUndefined } from './index'

// 👉 Required Validator
export const requiredValidator = value => {
  if (isNullOrUndefined(value) || isEmptyArray(value) || value === false)
    return 'This field is required'
  
  return !!String(value).trim().length || 'This field is required'
}

// 👉 Email Validator
export const emailValidator = value => {
  if (isEmpty(value))
    return true
  const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
  if (Array.isArray(value))
    return value.every(val => re.test(String(val))) || 'The Email field must be a valid verify'
  
  return re.test(String(value)) || 'The Email field must be a valid verify'
}

// 👉 Phone Number Validator
export const phoneValidator = value => {
  if (isEmpty(value)) return true
  
  // Regular expression for phone number validation
  const re = /^\+?(\d{1,4})?[-.\s]?(\(?\d{1,3}?\)?[-.\s]?)?(\d{1,4})[-.\s]?(\d{1,4})[-.\s]?(\d{1,9})$/

  if (Array.isArray(value)) {
    return value.every(val => re.test(String(val))) || 'The Phone Number field must be a valid phone number'
  }

  return re.test(String(value)) || 'The Phone Number field must be a valid phone number'
}

// 👉 Password Validator
export const passwordValidator = password => {
  const regExp = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%&*()]).{8,}/
  const validPassword = regExp.test(password)
  
  return (
    // eslint-disable-next-line operator-linebreak
    validPassword ||
        'Field must contain at least one uppercase, lowercase, special character and digit with min 8 chars')
}

// 👉 Confirm Password Validator
export const confirmedValidator = (value, target) => value === target || 'The Confirm Password field confirmation does not match'

// 👉 Between Validator
export const betweenValidator = (value, min, max) => {
  const valueAsNumber = Number(value)
  
  return (Number(min) <= valueAsNumber && Number(max) >= valueAsNumber) || `Enter number between ${min} and ${max}`
}

// 👉 Integer Validator
export const integerValidator = value => {
  if (isEmpty(value))
    return true
  if (Array.isArray(value))
    return value.every(val => /^-?[0-9]+$/.test(String(val))) || 'This field must be an integer'
  
  return /^-?[0-9]+$/.test(String(value)) || 'This field must be an integer'
}

// 👉 Regex Validator
export const regexValidator = (value, regex) => {
  if (isEmpty(value))
    return true
  let regeX = regex
  if (typeof regeX === 'string')
    regeX = new RegExp(regeX)
  if (Array.isArray(value))
    return value.every(val => regexValidator(val, regeX))
  
  return regeX.test(String(value)) || 'The Regex field format is invalid'
}

// 👉 Alpha Validator
export const alphaValidator = value => {
  if (isEmpty(value))
    return true
  
  return /^[A-Z]*$/i.test(String(value)) || 'The Alpha field may only contain alphabetic characters'
}

// 👉 URL Validator
export const urlValidator = value => {
  if (isEmpty(value))
    return true
  const re = /^(http[s]?:\/\/){0,1}(www\.){0,1}[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,5}[\.]{0,1}/
  
  return re.test(String(value)) || 'URL is invalid'
}

// 👉 Length Validator
export const lengthValidator = (value, length) => {
  if (isEmpty(value))
    return true
  
  return String(value).length === length || `The Min Character field must be at least ${length} characters`
}

// 👉 Alpha-dash Validator
export const alphaDashValidator = value => {
  if (isEmpty(value))
    return true
  const valueAsString = String(value)
  
  // Check if value contains at least one alphabetic character
  const containsAlpha = /[a-zA-Z]/.test(valueAsString)

  // Check if value matches the alpha-dash pattern
  const isAlphaDash = /^[0-9A-Z_-]*$/i.test(valueAsString)

  return (containsAlpha && isAlphaDash) || 'All Character are not valid'

}

// 👉 Card Validator start here
function isEmpty_Card(value) {
  return value.trim() === ''
}
function isValidLuhnAlgorithm(value) {
  let sum = 0
  let isDouble = false

  for (let i = value.length - 1; i >= 0; i--) {
    let digit = parseInt(value[i])

    if (isDouble) {
      digit *= 2
      if (digit > 9) {
        digit -= 9
      }
    }
    sum += digit
    isDouble = !isDouble
  }
  
  return sum % 10 === 0
}
function isVisaCard(value) {
  return /^4\d{12,21}$/.test(value)
}
function isMasterCard(value) {
  return /^5[1-5]\d{13,22}$/.test(value)
}
function isAmexCard(value) {
  return /^3[47]\d{11,20}$/.test(value)
}
export function cardValidator(value) {
  if (isEmpty(value))
    return true

  if (!/^\d{14,24}$/.test(value))
    return 'Card number must be between 14 and 24 digits'

  // Check if the card number is valid using the Luhn algorithm
  if (!isValidLuhnAlgorithm(value))
    return 'Invalid card number'

  // Check for specific card brand patterns
  if (!isVisaCard(value) && !isMasterCard(value) && !isAmexCard(value))
    return 'Invalid card brand'

  return true
}

// 👉 Card Validator end's

// 👉 expiry Validator start here
export function expiryValidator(value) {
  if (isEmpty_Card(value))
    return true

  const [month, year] = value.split('/')
  const currentDate = new Date()
  const currentYear = currentDate.getFullYear() % 100
  const currentMonth = currentDate.getMonth() + 1

  const isValidFormat = /^\d{2}\/\d{2}$/.test(value)
  const isNumericMonth = !isNaN(month)
  const isNumericYear = !isNaN(year)

  if (!isValidFormat || !isNumericMonth || !isNumericYear)
    return `The expiry date must be in the format MM/YY`

  const expiryYear = parseInt(year)
  const expiryMonth = parseInt(month)

  if (expiryYear < currentYear || (expiryYear === currentYear && expiryMonth < currentMonth))
    return `The expiry date must be in the future`

  return true
}

// 👉 expiry Validator end's


// 👉 cvv Validator start here
export function cvvValidator(value) {
  if (isEmpty_Card(value))
    return true

  if (!/^\d{3,4}$/.test(value))
    return 'CVV must be a 3 or 4 digit number'

  return true
}

// 👉 cvv Validator end's
