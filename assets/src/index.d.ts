
declare global {
  interface Window {
    vtsAdmin: {
      nonce: string;
      apiUrl: string;
    };
  }
}

// This empty export makes the file a module, which is required for 'declare global'.
export {};

