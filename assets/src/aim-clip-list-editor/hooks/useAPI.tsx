import { useAtom } from "jotai";
import { API, AppState } from "../store";

export function useAPI() {
  const [api] = useAtom(API);
  const [, setAppState] = useAtom(AppState);
  return {
    get: async (route: string) => {
      const response = await fetch(`${api.url}${route}`, {
        credentials: 'include',
      });
      return response.json();
    },
    post: async (route: string, data: Record<string, any> | any[]) => {
      setAppState({
        status: 'loading',
      });
      const response = await fetch(`${api.url}${route}`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': api.nonce,
        },
        body: JSON.stringify(data),
      }).finally(() => {
        setAppState({
          status: 'idle',
        });
      });
      return response.json();
    },

    file: async (route: string, data: FormData) => {
      console.log(api.url, route);
      const response = await fetch(`${api.url}${route}`, {
        method: 'POST',
        headers: {
          // 'Content-Type': 'multipart/form-data',
          'X-WP-Nonce': api.nonce,
        },
        credentials: 'include',
        mode: 'cors',
        body: data,
      });
      return response.json();
    }
  }
}

