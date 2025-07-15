import React, { useContext } from "react";
import { AppHeader } from "./components/app-header";
import { createRoot } from 'react-dom/client';
import { AppNavigationContext, AppNavigationProvider, Router } from "./hooks/useAppNavigation";
import { Button } from "@/components/ui/button";
import { Upload } from "./components/upload";
import { Search } from "./components/search";


export const App = () => {
  return (
    <AppNavigationProvider>
      <main className="p-0 bg-gray-100 text-gray-900">
        <AppHeader />
        <div className="p-4">
          <Router config={{
            upload: <Upload />,
            search: <Search/>
          }} />
        </div>
      </main>
    </AppNavigationProvider>
  );
};
document.addEventListener('DOMContentLoaded', () => {
  const root = createRoot(document.getElementById('vimeo-transcript-upload-app')!);
  root.render(<App />);
});


