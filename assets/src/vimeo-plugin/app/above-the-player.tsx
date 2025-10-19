import { createPortal } from 'react-dom';

export function getAbovePlayerDomElement(){
  const appRoot = document.querySelector("#aim-clip-player-app");
  const appRootRoot = appRoot?.closest(".wp-block-embed");
  if(!appRootRoot){
    throw new Error("Bad Dom Tree: Could not find app root's root, looking for closest .wp-block-embed");
  }
  let portalRoot = appRootRoot.parentElement?.querySelector(".aim-clip-player--above-the-player")
  if(!portalRoot){
    portalRoot = document.createElement("div");
    portalRoot.classList.add("aim-clip-player--above-the-player");
    appRootRoot.insertAdjacentElement("beforebegin", portalRoot);
  }
  return portalRoot;
}

export function AboveVideoPlayer({ children }: { children: React.ReactNode }) {
  const portalRoot = getAbovePlayerDomElement();

  return <>{createPortal(children, portalRoot, "aboveVideoPlayer__portal")}</>
}
