import React from "react";
import { AppNavigationContext } from "../hooks/useAppNavigation";
import { Button } from "@/components/ui/button";

export const AppHeader = () => {
  return <header className="p-4 pb-0">
    <div className="flex items-center justify-between border-b border-b-neutral-400 pb-4">
      <h1 className="text-gray-900 text-lg">Vimeo Transcript Search</h1>
      <Menu />
    </div>
  </header>;
};

function Menu() {
  const r = React.useContext(AppNavigationContext);
  const current = r?.currentPage;
  return <div className="flex items-center gap-2">
		<Button variant={current == "viewVideos" ? "default" : "outline"} onClick={() => r?.navigate("viewVideos")}>
			View Videos
		</Button>
    <Button variant={current == "upload" ? "default" : "outline"} onClick={() => r?.navigate("upload")}>
      Upload
    </Button>
    <Button variant={current == "search" ? "default" : "outline"} onClick={() => r?.navigate("search")}>
      Search
    </Button>
  </div>
}
