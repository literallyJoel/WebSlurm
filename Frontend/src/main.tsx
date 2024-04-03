import React from "react";
import ReactDOM from "react-dom/client";
import "./index.css";
import { setChonkyDefaults } from "chonky";
import { ChonkyIconFA } from "chonky-icon-fontawesome";
import { QueryClient, QueryClientProvider } from "react-query";
import Router from "./Router.tsx";
import "@/../node_modules/noty/lib/noty.css";
import "@/../node_modules/noty/lib/themes/mint.css";
//@ts-ignore
setChonkyDefaults({ iconComponent: ChonkyIconFA, disableDragAndDrop: true });
const queryClient = new QueryClient();

ReactDOM.createRoot(document.getElementById("root")!).render(
  <React.StrictMode>
    <QueryClientProvider client={queryClient}>
      <Router />
    </QueryClientProvider>
  </React.StrictMode>
);
