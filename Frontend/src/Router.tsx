import { RouterProvider, createBrowserRouter } from "react-router-dom";

import Home from "./pages/home/Home";
import AccountSettings from "./pages/users/AccountSettings";
import CreateAccount from "./pages/users/create/CreateAccount";
import { CreationSuccess } from "./pages/users/create/CreationSuccess";
import { CreationFailure } from "./pages/users/create/CreationFailure";
import ResetPassword from "./pages/auth/ResetPassword";
import Login from "./pages/auth/Login";
import AdminSettings from "./pages/admin/settings/Settings";
import CreateJobType from "./pages/admin/settings/JobTypes/CreateJobType";
import JobTypes from "./pages/admin/settings/JobTypes/JobTypes";
import { UpdateJobType } from "./pages/admin/settings/JobTypes/UpdateJobType";
import CreateJob from "./pages/jobs/createJob/CreateJob";
import Tus from "@uppy/tus";
import Webcam from "@uppy/webcam";
import { ComponentType, useState } from "react";
import { generateFileId } from "./helpers/files";
import Uppy from "@uppy/core";
import { apiEndpoint } from "./config/config";
import AuthProvider from "./providers/AuthProvider";
import ViewJobs from "./pages/jobs/viewJob/ViewJobs";
import JobInfo from "./pages/jobs/viewJob/JobInfo";
import Users from "./pages/admin/settings/Users/Users";

const Router = () => {
  const [fileId, setFileId] = useState<string | undefined>();
  const [isUploadComplete, setIsUploadComplete] = useState(false);
  const [allowedTypes, setAllowedTypes] = useState<string[] | undefined>();
  const [arrayJobCount, setArrayJobCount] = useState(1);

  const getNewUppy = () => {
    if (!fileId) {
      generateFileId(localStorage.getItem("token") ?? "").then((data) => {
        setFileId(data.fileId);
      });
    }

    return new Uppy({
      autoProceed: false,
      restrictions: {
        maxNumberOfFiles: arrayJobCount,
        minNumberOfFiles: arrayJobCount,
        allowedFileTypes: allowedTypes,
      },
      onBeforeFileAdded(currentFile, files) {
        if (Object.keys(files).length === 0) {
          const modified = {
            ...currentFile,
            name: fileId ?? "noid",
            meta: { ...currentFile.meta, name: fileId ?? "noid" },
          };
          return modified;
        } else {
          const modified = {
            ...currentFile,
            name: fileId ? `${fileId}-${Object.keys(files).length}` : "noid",
            meta: {
              ...currentFile.meta,
              name: fileId ? `${fileId}-${Object.keys(files).length}` : "noid",
            },
          };

          return modified;
        }
      },
    })
      .use(Tus, {
        endpoint: `${apiEndpoint}/files/upload`,
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
  const [uppy, setUppy] = useState(() => getNewUppy());

  const resetUppy = () => {
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
      {
        path: "/accounts/settings",
        element: withAuth(AccountSettings)({}),
      },
      {
        path: "/auth/login",
        element: withAuth(Login)({}),
      },
      {
        path: "/admin",
        element: withAuth(AdminSettings)({}),
        children: [
          { path: "/admin/jobtypes/new", element: <CreateJobType /> },
          { path: "/admin/jobtypes", element: <JobTypes /> },
          { path: "/admin/jobtypes/:id", element: <UpdateJobType /> },
          { path: "/admin/users", element: <Users /> },
        ],
      },
      {
        path: "/jobs/create",
        element: withAuth(CreateJob)({
          uppy: uppy,
          isUploadComplete: isUploadComplete,
          setFileId: setFileId,
          fileId: fileId,
          resetUppy: resetUppy,
          setAllowedTypes: setAllowedTypes,
          allowedTypes: allowedTypes,
          setArrayJobCount: setArrayJobCount,
        }),
      },
      {
        path: "/jobs",
        element: withAuth(ViewJobs)({}),
        children: [
          {
            path: "/jobs/:jobId",
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
