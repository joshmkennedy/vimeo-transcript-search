import React from "react";

import { createRoot } from 'react-dom/client';

export const App = () => {
  return (
    <main className="p-4 bg-gray-100 text-gray-900">
      <h1 className="text-2xl font-bold text-red-100">Vimeo Transcript Upload</h1>
    </main>
  );
};
document.addEventListener('DOMContentLoaded', () => {
  const root = createRoot(document.getElementById('vimeo-transcript-upload-app')!);
  root.render(<App />);
});


