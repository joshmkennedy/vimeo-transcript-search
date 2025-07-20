import { toast } from "react-hot-toast";
import { Button } from "./button";

export function TestToast() {
  return <>
    <Button onClick={() => toast.success("Hello")} className="bg-green-400">Test Success Toast</Button>
    <Button onClick={() => toast.loading("Hello")} className="bg-blue-400">Test Loading Toast</Button>
    <Button onClick={() => toast.error("Hello")} className="bg-red-400">Test Error Toast</Button>
    <Button onClick={() => uploadStatusToast(() => new Promise((r) => setTimeout(() => r(undefined), 10000)), "Your Video Transcription it being uploaded", "There was an error uploading your video", "Uploading your video")} className="bg-yellow-400">Test Upload Status Toast</Button>
  </>
}


function uploadStatusToast(
  promise: () => Promise<void>,
  successMessage: string,
  errorMessage: string,
  loadingMessage: string,
) {

  return toast.promise(
    promise(),
    {
      loading: loadingMessage,
      success: successMessage,
      error: errorMessage,
    }
  );
}

