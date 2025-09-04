import { createRoot } from 'react-dom/client';
import "./app.css";
import { useEffect, useRef } from 'react';
import toast, { Toaster } from 'react-hot-toast';

type AimClipPlayerProps = {
  player: Vimeo;
  vimeoId: string;
  times: {
    start: number;
    end: number;
  };
  toastMessage: string | { heading: string, message: string };
};

function App({ player, vimeoId, times, toastMessage }: AimClipPlayerProps) {
  const alreadyToasted = useRef(false);
  console.log("re-rendered");
  useEffect(() => {
    player.on("timeupdate", (timeEvent) => {
      if (Math.round(timeEvent.seconds) >= times.end && alreadyToasted?.current == false) {
        alreadyToasted.current = true;
        watchedVideoNotification(toastMessage);
      }
    })
  }, [player, times])

  useEffect(() => {
  }, [])
  return (
    <div>
      {/* <AimClipToolbar /> */}
      <Toaster
        containerStyle={{ top: '100px' }}
        toastOptions={{
          className: 'toast-toast',
        }}
      />
    </div>
  );
}

export function mountReactApp(el: HTMLElement, props: AimClipPlayerProps) {
  const root = createRoot(el);
  root.render(<App {...props} />);
}

function watchedVideoNotification(toastMessage:AimClipPlayerProps['toastMessage']){
      toast(() => {
      if (typeof toastMessage !== "string") {
        return (<div className="toast-content">
          <div className="toast-heading">{toastMessage.heading}</div>
          <div className="toast-text">{toastMessage.message}</div>
        </div>)
      }
      return (<div className="toast-content">
        {toastMessage}
      </div>)
    }, {
      icon: "🎉",
      duration: 5000,
    });
}
