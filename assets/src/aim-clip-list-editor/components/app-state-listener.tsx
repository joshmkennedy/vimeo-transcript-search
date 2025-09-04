import { useAtom } from "jotai";
import { useEffect } from "react";
import toast from "react-hot-toast";
import { AppState } from "../store";

export function AppStateListener() {
  const [state] = useAtom(AppState);
	useEffect(() => {
    switch (state.status) {
      case 'loading':
        toast.loading('Loading...');
        break;
      case 'success':
        toast.success('Success!');
        break;
      case 'error':
        toast.dismiss();
        toast.error(`Error: ${state.error}`);
        break;
      case 'idle':
        toast.dismiss();
        break;
    }
  }, [state]);

  return null; // This component doesn't render anything
}
