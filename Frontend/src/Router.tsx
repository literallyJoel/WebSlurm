import { RouterProvider, createBrowserRouter } from "react-router-dom";

import AccountSettings from "./pages/accounts/settings/AccountSettings.tsx";
import Home from "./pages/home/Home.tsx";
import CreateAccount from "./pages/accounts/create/CreateAccount.tsx";
import { CreationSuccess } from "./pages/accounts/create/CreationSuccess.tsx";
import { CreationFailure } from "./pages/accounts/create/CreationFailure.tsx";
import ResetPassword from "./pages/accounts/resetPassword/ResetPassword.tsx";
import Login from "./pages/auth/login/Login.tsx";

import AdminSettings from "./pages/admin/settings/Settings.tsx";
import CreateJobType from "./pages/admin/settings/JobTypes/Create/CreateJobType.tsx";
import JobTypes from "./pages/admin/settings/JobTypes/JobTypes.tsx";
import { UpdateJobType } from "./pages/admin/settings/JobTypes/UpdateJobTypes/UpdateJobTypes.tsx";
import CreateJob from "./pages/jobs/CreateJob/CreateJob.tsx";
import { ComponentType, useState } from "react";
import Uppy from "@uppy/core";
import Tus from "@uppy/tus";
import Webcam from "@uppy/webcam";
import ViewJobs from "./pages/jobs/ViewJob/ViewJobs.tsx";
import JobInfo from "./pages/jobs/ViewJob/JobInfo.tsx";
import { getFileID } from "./helpers/jobs.ts";
import Users from "./pages/admin/settings/Users/Users.tsx";
import Organisations from "./pages/admin/settings/Organisations/Organisations.tsx";
import CreateOrganisation from "./pages/admin/settings/Organisations/CreateOrganisation.tsx";
import { apiEndpoint } from "./config/config.ts";
import AuthProvider from "./providers/AuthProvider/AuthProvider.tsx";

const Router = () => {
  const getNewUppy = () => {
    if (!fileID) {
      getFileID(localStorage.getItem("token") ?? "").then((data) => {
        setFileID(data.fileID);
      });
    }
    return new Uppy({
      autoProceed: false,
      allowMultipleUploads: false,
      restrictions: {
        maxNumberOfFiles: arrayJobCount,
        minNumberOfFiles: arrayJobCount,
        allowedFileTypes: allowedTypes,
      },
      onBeforeFileAdded(currentFile, files) {
        console.log(Object.keys(files));
        console.log(Object.keys(files).length);
        if (Object.keys(files).length === 0) {
          const modifiedFile = {
            ...currentFile,
            name: fileID ?? "noid",
            meta: { ...currentFile.meta, name: fileID ?? "noid" },
          };
          return modifiedFile;
        } else {
          const modifiedFile = {
            ...currentFile,
            name: fileID ? fileID + "-" + Object.keys(files).length : "noid",
            meta: {
              ...currentFile.meta,
              name: fileID ? fileID + "-" + Object.keys(files).length : "noid",
            },
          };
          return modifiedFile;
        }
      },
    })
      .use(Tus, {
        endpoint:
          process.env.NODE_ENV === "development"
            ? "http://localhost:8080/api/jobs/upload"
            : `${apiEndpoint}/jobs/upload`,
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
  const [arrayJobCount, setArrayJobCount] = useState(1);
  const [uppy, setUppy] = useState(() => getNewUppy());

  const resetUppy = () => {
    console.log("resetting uppy");
    setUppy(getNewUppy());
  };

  const withAuth = <P extends object>(Component: ComponentType<P>) => {
    return (props: P) => (
      <AuthProvider>
        <Component {...props} />
      </AuthProvider>
    );
  };

  const router = createBrowserRouter(
    [
      { path: "/", element: withAuth(Home)({}) },
      { path: "/accounts/create", element: withAuth(CreateAccount)({}) },
      {
        path: "/accounts/create/success",
        element: withAuth(CreationSuccess)({}),
      },
      {
        path: "/accounts/create/failure",
        element: withAuth(CreationFailure)({}),
      },
      {
        path: "/accounts/settings/resetpassword",
        element: withAuth(ResetPassword)({}),
      },
      { path: "/accounts/settings", element: withAuth(AccountSettings)({}) },
      { path: "/auth/login", element: withAuth(Login)({}) },
      {
        path: "/admin",
        element: withAuth(AdminSettings)({}),
        children: [
          { path: "/admin/jobtypes/new", element: <CreateJobType /> },
          { path: "/admin/jobtypes", element: <JobTypes /> },
          {
            path: "/admin/jobtypes/:id",
            element: <UpdateJobType />,
          },
          { path: "/admin/users", element: <Users /> },
          { path: "/admin/organisations/", element: <Organisations /> },
          {
            path: "/admin/organisations/create",
            element: <CreateOrganisation />,
          },
        ],
      },
      {
        path: "/jobs/create",
        element: withAuth(CreateJob)({
          uppy: uppy,
          isUploadComplete: isUploadComplete,
          setFileID: setFileID,
          fileID: fileID,
          resetUppy: resetUppy,
          setAllowedTypes: setAllowedTypes,
          allowedTypes: allowedTypes,
          setArrayJobCount: setArrayJobCount,
        }),
      },
      {
        path: "/jobs/",
        element: withAuth(ViewJobs)({}),
        children: [
          {
            path: "/jobs/:jobID",
            element: withAuth(JobInfo)({}),
          },
        ],
      },
    ],
    {
      basename: process.env.NODE_ENV === "development" ? "" : "/~sgjvivia",
    }
  );

  return <RouterProvider router={router} />;
};

export default Router;
