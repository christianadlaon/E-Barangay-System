import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from "react";
import {
  clearBarangayLogoDataUrl,
  clearBarangayLogoDataUrlRemote,
  fetchBarangayLogoDataUrlRemote,
  getBarangayLogoDataUrl,
  saveBarangayLogoDataUrlRemote,
  setBarangayLogoDataUrl,
} from "../utils/branding";

const BrandingContext = createContext(null);
const DEFAULT_FAVICON = "/gulodlogo-circle.png";

// Default context value to prevent undefined errors
const DEFAULT_BRANDING_VALUE = {
  logoDataUrl: null,
  updateLogo: async () => {},
  resetLogo: async () => {},
  hasCustomLogo: false,
};

const ensureLinkTag = (rel, type) => {
  if (typeof document === "undefined") return null;
  let link = document.querySelector(`link[rel="${rel}"]`);
  if (!link) {
    link = document.createElement("link");
    link.rel = rel;
    if (type) link.type = type;
    document.head.appendChild(link);
  }
  return link;
};

export const BrandingProvider = ({ children }) => {
  const [logoDataUrl, setLogoDataUrl] = useState(() => getBarangayLogoDataUrl());
  const logoRef = useRef(logoDataUrl);
  const pollingRef = useRef(false);

  useEffect(() => {
    logoRef.current = logoDataUrl;
  }, [logoDataUrl]);

  useEffect(() => {
    const iconSrc = logoDataUrl || DEFAULT_FAVICON;
    const favicon = ensureLinkTag("icon", "image/png");
    if (favicon) favicon.href = iconSrc;
    const appleIcon = ensureLinkTag("apple-touch-icon");
    if (appleIcon) appleIcon.href = iconSrc;
  }, [logoDataUrl]);

  useEffect(() => {
    let isMounted = true;

    const pollRemoteLogo = async () => {
      if (!isMounted || pollingRef.current) return;
      if (typeof document !== "undefined" && document.visibilityState !== "visible") {
        return;
      }
      pollingRef.current = true;
      try {
        const remote = await fetchBarangayLogoDataUrlRemote();
        if (!isMounted || remote === null) return;
        if (remote !== logoRef.current) {
          setBarangayLogoDataUrl(remote);
          setLogoDataUrl(remote || "");
        }
      } finally {
        pollingRef.current = false;
      }
    };

    pollRemoteLogo();
    const intervalId = setInterval(pollRemoteLogo, 10000);

    return () => {
      isMounted = false;
      clearInterval(intervalId);
    };
  }, []);

  const updateLogo = useCallback(async (dataUrl) => {
    const saved = await saveBarangayLogoDataUrlRemote(dataUrl);
    setBarangayLogoDataUrl(saved);
    setLogoDataUrl(saved || "");
  }, []);

  const resetLogo = useCallback(async () => {
    await clearBarangayLogoDataUrlRemote();
    clearBarangayLogoDataUrl();
    setLogoDataUrl("");
  }, []);

  const value = useMemo(
    () => ({
      logoDataUrl,
      updateLogo,
      resetLogo,
      hasCustomLogo: Boolean(logoDataUrl),
    }),
    [logoDataUrl, updateLogo, resetLogo],
  );

  return (
    <BrandingContext.Provider value={value}>
      {children}
    </BrandingContext.Provider>
  );
};

export const useBranding = () => {
  const context = useContext(BrandingContext);
  
  // If context is undefined, we're outside the provider
  // Return default values and warn in development
  if (context === undefined) {
    if (process.env.NODE_ENV === 'development') {
      console.warn('useBranding must be used within a BrandingProvider');
    }
    return DEFAULT_BRANDING_VALUE;
  }
  
  return context || DEFAULT_BRANDING_VALUE;
};
