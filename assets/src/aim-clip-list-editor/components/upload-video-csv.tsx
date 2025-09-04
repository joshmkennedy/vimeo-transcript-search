import { X } from 'lucide-react';
import { useDropzone } from 'react-dropzone';
import { useCallback } from "react";
import { AppState, AppStore } from '../store';
import { useAtom } from 'jotai';
import { useAPI } from '../hooks/useAPI';
import toast from 'react-hot-toast';
import { Button } from '@/components/ui/button';

export function UploadVideoCsv({ onUpload, isShowing, setShowing }: { onUpload: Function, isShowing: boolean, setShowing: (s: boolean) => void }) {
  const api = useAPI()
  const [, setAppState] = useAtom(AppState)
  const [appStore, setAppStore] = useAtom(AppStore);
  const onDrop = useCallback(async (acceptedFiles: File[]) => {
    const file = acceptedFiles[0];
    if (file) {
      const formData = new FormData();
      formData.append('file', file);
      const res = await api.file('/upload-csv', formData).catch((e) => {
        setAppState({
          status: 'error',
          error: e.message,
        });
      });
      if (res.code) {
        setAppState({
          status: 'error',
          error: res.message,
        });
        return;
      }
      setAppState({
        status: 'success',
      });
      appStore.items = res.items;
      if (res.postId) {
        appStore.postId = res.postId;
      }
      if (res.post) {
        appStore.post = res.post;
      }
      onUpload(appStore.postId);
    }
  }, []);

  const {
    getRootProps,
    getInputProps,
    isDragAccept,
    isDragActive,
    isDragReject,
  } = useDropzone({
    accept: {
      'text/csv': [],
    },
    onDrop,
  });
  if (!isShowing) return null;
  return <div {...getRootProps({
    className: `
          w-full p-6 border-2 rounded-md transition-colors text-center hover:bg-neutral-100 hover:border-neutral-300 active:bg-blue-900 active:text-white relative
          ${isDragActive ? "border-blue-500 bg-blue-50 text-blue-800 border-solid" : "border-neutral-200 text-neutral-500 border-dashed"}
          ${isDragAccept ? "border-blue-500 bg-blue-300 text-white" : ""}
          ${isDragReject ? "border-red-500 bg-red-50 text-red-800" : ""}
        `
  })} >
    <div className="flex flex-row items-center justify-end w-full absolute right-4 top-4">
      <Button 
        onClick={(e) => {
          e.stopPropagation()
          setShowing(false)
        }}
        className="h-6 w-6 rounded-full aspect-square leading-0 flex justify-center items-center bg-neutral-500" aria-label="Close">
        <X className="h-5 w-5" />
      </Button>
    </div>
    <input {...getInputProps()} />
    <p className="md:text-lg font-bold">Drag and Drop the csv from the Ai Process</p>
  </div>
}
