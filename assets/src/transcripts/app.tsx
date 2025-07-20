import { Toaster } from 'react-hot-toast';
import { AppHeader } from "./components/app-header";
import { createRoot } from 'react-dom/client';
import { AppNavigationProvider, Router } from "./hooks/useAppNavigation";
import { Upload } from "./components/upload";
import { Search } from "./components/search";
import { TestToast } from '@/components/ui/toasts';


export const App = () => {
  return (
    <>
      <AppNavigationProvider>
        <main className="p-0 bg-gray-100 text-gray-900">
          <AppHeader />
          <div className="p-4">
            <Router config={{
              upload: <Upload />,
              search: <Search />
            }} />
          </div>
        </main>
      </AppNavigationProvider>
      <Toaster
        position="top-right"
        toastOptions={{
          success: {
            className: 'bg-green-100 text-green-900 text-base font-bold',
            iconTheme: {
              primary: '#008000',
              secondary: '#D2D2D5',
            },
          },
          loading: {
            className: 'bg-neutral-100 text-neutral-900 text-base font-bold',
            iconTheme: {
              primary: '#51A2FF',
              secondary: '#D2D2D5',
            },
          },
          error: {
            className: 'bg-red-100 text-red-900 text-base font-bold',
            iconTheme: {
              primary: '#FF0000',
              secondary: '#D2D2D5',
            },
          },
        }}
        containerStyle={{
          top: '50px',
        }}
      />
    </>
  );
};
document.addEventListener('DOMContentLoaded', () => {
  const root = createRoot(document.getElementById('vimeo-transcript-upload-app')!);
  root.render(<App />);
});


