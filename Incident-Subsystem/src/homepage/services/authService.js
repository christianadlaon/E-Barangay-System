/**
 * authService.js
 *
 * FIX: isIndigent append in adminEntry().
 *
 * The original loop skipped falsy values with `value !== ""` but
 * isIndigent = 0 (number) is falsy in a loose sense and the `typeof boolean`
 * branch only converts actual booleans. Added an explicit isIndigent guard so:
 *   - isIndigent: 0  → appended as "0"
 *   - isIndigent: 1  → appended as "1"
 *   - isIndigent: true/false → still handled by the boolean branch
 */
import api from "../../services/sub-system-1/Api";

export const authService = {

    getLocations: async () => {
        try {
            const response = await api.get('/locations');
            return {
                success: true,
                puroks:  response.data.puroks  || [],
                streets: response.data.streets || [],
            };
        } catch (error) {
            console.error("Backend Error: Failed to fetch locations.", error.message);
            return { success: false, puroks: [], streets: [], error: "Could not connect to server" };
        }
    },

    checkHouseholdHead: async ({ houseNumber, street, purok }) => {
        try {
            if (!houseNumber || !street || !purok) return { exists: false };
            const response = await api.get('/check-household', {
                params: { house_number: houseNumber, street_id: street, purok_id: purok },
            });
            return response.data;
        } catch (error) {
            const errorData = error.response?.data || error.message;
            console.error("Check household head error:", errorData);
            console.error("Status:", error.response?.status);
            console.error("Full error:", error);
            return { exists: false, error: true };
        }
    },

    searchAddresses: async (query) => {
        try {
            if (!query || query.length < 2) return { data: [] };
            const response = await api.get('/search-addresses', { params: { q: query } });
            return response.data;
        } catch (error) {
            console.error("Search addresses error:", error.response?.data || error.message);
            return { data: [] };
        }
    },

    checkEmail: async (email) => {
        try {
            if (!email) return { available: true };
            const response = await api.get('/check-email', { params: { email } });
            return response.data;
        } catch (error) {
            console.error("Check email error:", error.response?.data || error.message);
            return { available: true };
        }
    },

    // ── Shared FormData builder ──────────────────────────────────────────────
    // Extracted so both register() and adminEntry() use identical logic.
    _buildFormData(formData) {
        const data = new FormData();

        Object.keys(formData).forEach((key) => {
            if (key === "idFront" || key === "idBack") return;

            let value = formData[key];

            // Boolean → "1" / "0"
            if (typeof value === "boolean") {
                data.append(key, value ? 1 : 0);
                return;
            }

            // FIX: isIndigent is 0 or 1 (number). 0 is falsy but must be sent.
            // Without this guard, `value !== ""` would pass but we want to be
            // explicit: any numeric flag field must always be appended.
            if (key === "isIndigent") {
                data.append(key, value ? 1 : 0);
                return;
            }

            if (value !== null && value !== undefined && value !== "") {
                data.append(key, value);
            }
        });

        return data;
    },

    // 5. PUBLIC Registration → POST /api/register (no auth, always Pending)
    register: async (formData) => {
        try {
            const data = authService._buildFormData(formData);

            // ID files are REQUIRED for public registration
            if (formData.idFront instanceof File) data.append("idFront", formData.idFront);
            if (formData.idBack  instanceof File) data.append("idBack",  formData.idBack);

            // IMPORTANT: Do NOT manually set Content-Type header. Axios automatically
            // sets it to 'multipart/form-data; boundary=...' when FormData is passed.
            // Setting it manually breaks the boundary and PHP won't parse $_POST.
            const response = await api.post('/register', data);

            return response.data;
        } catch (error) {
            const errorData = error.response?.data || { message: "Network Error" };
            console.error("Public registration error:", errorData);
            throw errorData;
        }
    },

    // 6. STAFF Direct Enrollment → POST /api/staff/enroll (auth required, always Verified)
    adminEntry: async (formData) => {
        try {
            const data = authService._buildFormData(formData);

            // ID files are optional for staff enrollment
            if (formData.idFront instanceof File) data.append("idFront", formData.idFront);
            if (formData.idBack  instanceof File) data.append("idBack",  formData.idBack);

            // IMPORTANT: Do NOT manually set Content-Type header. Axios automatically
            // sets it to 'multipart/form-data; boundary=...' when FormData is passed.
            const response = await api.post('/staff/enroll', data);

            return response.data;
        } catch (error) {
            const errorData = error.response?.data || { message: "Network Error" };
            console.error("Staff enrollment error:", errorData);
            throw errorData;
        }
    },

    // 7. Tracking Search
    track: async (trackingNumber) => {
        try {
            const cleaned  = trackingNumber.toUpperCase().trim();
            const response = await api.get(`/track/${cleaned}`);
            return response.data;
        } catch (error) {
            console.error("Tracking error:", error.response?.data || error.message);
            throw error.response?.data || new Error("Not found");
        }
    },
};