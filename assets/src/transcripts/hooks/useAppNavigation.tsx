import React, { useState, useContext } from "react";

interface AppNavigationContextType {
  currentPage: string;
  navigate: (path: string) => void;
}

export const AppNavigationContext = React.createContext<AppNavigationContextType | undefined>(undefined);

export function useAppNavigation() {
  const context = useContext(AppNavigationContext);
  if (!context) {
    throw new Error("useAppNavigation must be used within an AppNavigationProvider");
  }
  return context;
}

interface AppNavigationProviderProps {
  children: React.ReactNode;
}

export function AppNavigationProvider({ children }: AppNavigationProviderProps) {
  const [currentPage, setCurrentPage] = useState<string>("viewVideos");
  const navigate = (path: string) =>{
    setCurrentPage(path)
    window.location.hash = path;
  };

  React.useEffect(() => {
    const hash = window.location.hash.replace("#", "");
    if (hash) {
      navigate(hash);
    }
  }, []);

  const value = { currentPage, navigate };

  return (
    <AppNavigationContext.Provider value={value}>
      {children}
    </AppNavigationContext.Provider>
  );
}

export function Router({ config }: { config: Record<string, React.ReactElement> }) {
  const { currentPage } = useAppNavigation();

  if (!(currentPage in config)) {
    return <div>404</div>;
  }

  return config[currentPage];
}
