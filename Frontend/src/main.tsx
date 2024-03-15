import React from "react";
import ReactDOM from "react-dom/client";
import "./index.css";
import Router from "./Router";
import { ChonkyIconFA } from "chonky-icon-fontawesome";
import { setChonkyDefaults } from "chonky";
//This comes from a library, and is setup as per their own setup guide
//But typescript gets a bit upset at it.
//@ts-ignore
setChonkyDefaults({ iconComponent: ChonkyIconFA, disableDragAndDrop: true });
ReactDOM.createRoot(document.getElementById("root")!).render(
  <React.StrictMode>
    <Router />
  </React.StrictMode>
);
