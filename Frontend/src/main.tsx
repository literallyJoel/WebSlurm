import React from "react";
import ReactDOM from "react-dom/client";
import "./index.css";
import { RouterProvider, createBrowserRouter } from "react-router-dom";

import AccountSettings from "./pages/accounts/settings/AccountSettings.tsx";
import Home from "./pages/home/Home.tsx";
import CreateAccount from "./pages/accounts/create/CreateAccount.tsx";
import { CreationSuccess } from "./pages/accounts/create/CreationSuccess.tsx";
import { CreationFailure } from "./pages/accounts/create/CreationFailure.tsx";
import ResetPassword from "./pages/accounts/resetPassword/ResetPassword.tsx";
import Login from "./pages/auth/login/Login.tsx";
import { QueryClient, QueryClientProvider } from "react-query";
import AuthProvider from "./providers/AuthProvider/AuthProvider.tsx";
import AdminSettings from "./pages/admin/settings/Settings.tsx";
import NewJobType from "./pages/admin/settings/JobTypes/NewJobType/NewJobType.tsx";

const router = createBrowserRouter([
  { path: "/", element: <Home /> },
  { path: "/accounts/create", element: <CreateAccount /> },
  { path: "/accounts/create/success", element: <CreationSuccess /> },
  { path: "/accounts/create/failure", element: <CreationFailure /> },
  {
    path: "/accounts/settings/resetpassword",
    element: <ResetPassword />,
  },
  { path: "/accounts/settings", element: <AccountSettings /> },
  { path: "/auth/login", element: <Login /> },
  {
    path: "/admin",
    element: <AdminSettings />,
    children: [{ path: "/admin/jobtypes/new", element: <NewJobType /> }],
  },
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
