
declare global {
  interface Window {
    vtsAdmin: {
      nonce: string;
      apiUrl: string;
    };
    vtsACLEditor:{
      nonce: string;
      apiUrl: string;
      postId:  number | string;
      post: any;
      items: any;
      previewList: any;
      resources: any;
    };
    vtsPublic: {
      nonce: string;
      aimClip?:string;
    }
  }
}

export interface VimeoPluginInterface {
  shouldUse: () => boolean;
  init: () => void;
}
declare global {
  interface Window {
    aimVimeoPlugins: {
      [key: string]: VimeoPluginInterface;
    }[];
  }
}
// This empty export makes the file a module, which is required for 'declare global'.
export {};

