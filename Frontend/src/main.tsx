import React from "react";
import ReactDOM from "react-dom/client";
import "./index.css";
import Router from "./Router";
import { ChonkyIconFA } from "chonky-icon-fontawesome";
import { setChonkyDefaults } from "chonky";
import { QueryClientProvider, QueryClient } from "react-query";

//This comes from a library, and is setup as per their own setup guide
//But typescript gets a bit upset at it.
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
