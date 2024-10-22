import contact from "@/navigation/vertical/contact"
import messages from "@/navigation/vertical/messages"
import phoneNumbers from "@/navigation/vertical/phone-numbers"
import recentCalls from "@/navigation/vertical/recent-calls"

//import reports from "@/navigation/vertical/reports"
import teams from "@/navigation/vertical/teams"
import dashboard from './dashboard'

//import addOns from "@/navigation/vertical/add-ons"
import topUpCredit from "@/navigation/vertical/top-up-credit"

// export default [...dashboard, ...appAndPages, ...uiElements, ...forms, ...charts, ...others]
//...addOns, ...reports,  
export default [...dashboard, ...teams, ...phoneNumbers, ...contact, ...recentCalls, ...messages, ...topUpCredit]
