const resolveHost = () => {
  if (import.meta.env.VITE_API_HOST) return import.meta.env.VITE_API_HOST;
  if (typeof window !== "undefined" && window.location?.hostname) {
    return window.location.hostname;
  }
  return "127.0.0.1";
};

export const API_HOST = resolveHost();

// Unified API Port - All subsystems on port 8000
export const UNIFIED_API_PORT = import.meta.env.VITE_API_PORT || "8000";
export const UNIFIED_API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL ||
  `http://${API_HOST}:${UNIFIED_API_PORT}/api`;

// Legacy port configuration (for backward compatibility - all point to 8000)
export const INCIDENT_API_PORT = UNIFIED_API_PORT;
export const INCIDENT_API_BASE_URL = `${UNIFIED_API_BASE_URL}/incidents`;

export const DOCUMENTS_API_PORT = UNIFIED_API_PORT;
export const DOCUMENTS_API_BASE_URL = `${UNIFIED_API_BASE_URL}/documents`;

export const RESIDENT_API_PORT = UNIFIED_API_PORT;
export const RESIDENT_API_BASE_URL = `${UNIFIED_API_BASE_URL}/residents`;

// Unified PHP API
export const PHP_API_BASE_URL = UNIFIED_API_BASE_URL;

export const BRANDING_API_URL =
  import.meta.env.VITE_BRANDING_API_URL ||
  `${UNIFIED_API_BASE_URL}/branding/logo`;

export const PROFILE_PHOTO_API_URL =
  import.meta.env.VITE_PROFILE_PHOTO_API_URL ||
  `${UNIFIED_API_BASE_URL}/profile/photo`;
