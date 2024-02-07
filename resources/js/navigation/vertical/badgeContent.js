// badgeContent.js
import { useTopUpCreditStore } from "@/views/apps/credit/useTopUpCreditStore";

const topUpCreditStore = useTopUpCreditStore();

export async function fetchBadgeContent() {
    try {
        const response = await topUpCreditStore.fetchTopUpCreditInfo();
        const credit = response.data.credit;
        return credit !== null ? credit.toString() : ''; // Convert credit to string before returning
    } catch (error) {
        console.error(error);
        throw error; // Propagate the error to the outer promise
    }
}
