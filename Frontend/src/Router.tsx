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
import JobTypes from "./pages/admin/settings/JobTypes/JobTypes.tsx";
import { UpdateJobType } from "./pages/admin/settings/JobTypes/UpdateJobTypes/UpdateJobTypes.tsx";
import CreateJob from "./pages/jobs/CreateJob/CreateJob.tsx";
import { useState } from "react";
import Uppy from "@uppy/core";
import Tus from "@uppy/tus";
import Webcam from "@uppy/webcam";
import ViewJobs from "./pages/jobs/ViewJob/ViewJobs.tsx";
import JobInfo from "./pages/jobs/ViewJob/JobInfo.tsx";


const queryClient = new QueryClient();


const Router = () => {
  const getNewUppy = () => {
    return new Uppy({
      autoProceed: false,
      allowMultipleUploads: false,
      restrictions: { maxNumberOfFiles: 1, allowedFileTypes: allowedTypes },
      onBeforeFileAdded(currentFile) {
        const modifiedFile = {
          ...currentFile,
          name: fileID ?? "noid",
          meta: { ...currentFile.meta, name: fileID ?? "noid" },
        };
        return modifiedFile;
      },
    })
      .use(Tus, {
        endpoint:
          process.env.NODE_ENV === "development"
            ? "http://localhost:8080/api/jobs/upload"
            : "/api/jobs/upload",
        retryDelays: [0, 1000, 3000, 5000],
        limit: 1,
        removeFingerprintOnSuccess: true,
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
        chunkSize: 2142880,
      })
      .use(Webcam)
      .on("complete", (res) => {
        if (res.successful) {
          setIsUploadComplete(true);
        }
      });
  };
  const [fileID, setFileID] = useState<string | undefined>();
  const [isUploadComplete, setIsUploadComplete] = useState(false);
  const [allowedTypes, setAllowedTypes] = useState<string[] | undefined>();
  const [uppy, setUppy] = useState(() => getNewUppy());

  const resetUppy = () => {
    console.log("resetting uppy");
    setUppy(getNewUppy());
  };

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
      children: [
        { path: "/admin/jobtypes/new", element: <NewJobType /> },
        { path: "/admin/jobtypes", element: <JobTypes /> },
        {
          path: "/admin/jobtypes/:id",
          element: <UpdateJobType />,
        },
      ],
    },
    {
      path: "/jobs/create",
      element: (
        <CreateJob
          uppy={uppy}
          isUploadComplete={isUploadComplete}
          setFileID={setFileID}
          fileID={fileID}
          resetUppy={resetUppy}
          setAllowedTypes={setAllowedTypes}
          allowedTypes={allowedTypes}
        />
      ),
    },
    {
      path: "/jobs/",
      element: <ViewJobs />,
      children: [
        {
          path: "/jobs/:jobID",
          element: <JobInfo />,
        },
      ],
    },
  ]);

  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <RouterProvider router={router} />
      </AuthProvider>
    </QueryClientProvider>
  );
};

export default Router;
