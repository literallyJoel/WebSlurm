import React from "react";
import ReactDOM from "react-dom/client";
import "./index.css";
import { Query, QueryClient, QueryClientProvider } from "react-query";
import { createBrowserRouter, RouterProvider } from "react-router-dom";
import CreateAccountScreen from "./pages/accounts/createAccount/CreateAccount.tsx";
import Login from "./pages/accounts/login/Login.tsx";
import ResetPassword from "./pages/accounts/resetPassword/ResetPassword.tsx";
import AuthProvider from "./providers/auth/AuthProvider.tsx";
import Home from "./pages/home/Home.tsx";
import AccountPanel from "./pages/accounts/accountPanel/AccountPanel.tsx";
//=======================================//
//=============Router Code==============//
//=====================================//
/*
This defines all the client-side routes for front-end navigation
*/

const router = createBrowserRouter([
  { path: "/", element: <Home /> },
  { path: "/accounts/settings", element: <AccountPanel /> },
  {
    path: "/accounts/create",
    element: <CreateAccountScreen />,
  },
  { path: "/accounts/reset", element: <ResetPassword isRequired={false} /> },
  { path: "/auth/login", element: <Login isExpired={false} /> },
]);

const queryClient = new QueryClient();

ReactDOM.createRoot(document.getElementById("root")!).render(
  <React.StrictMode>
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <RouterProvider router={router} />
      </AuthProvider>
    </QueryClientProvider>
  </React.StrictMode>
);
