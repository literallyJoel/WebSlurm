import Nav from "@/components/Nav";
import Spinner from "@/components/Spinner/Spinner";
import React, { useMemo } from "react";
import { Link, Outlet, useLocation } from "react-router-dom";

function useQueryParams() {
  const { search } = useLocation();
  return useMemo(() => new URLSearchParams(search), [search]);
}

const ViewJobs = (): JSX.Element => {
  const queryParams = useQueryParams();
  const filter = queryParams.get("filter") || "";
  return (
    <>
      <Nav />
      <div className="flex h-screen w-full text-center">
        <nav className="w-80 bg-gray-100 dark:bg-gray-800 border-r dark:border-gray-700 h-full px-6 py-4">
          <h1 className="text-xl font-bold mb-4">Your Jobs</h1>
          <ul className="space-y-2">
            <li>
              <div className="w-full flex flex-col justify-center p-1 border border-gray-600 rounded-md shadow-md">
                <div className="w-full flex flex-row justify-between p-2">
                  <div className="text-sm">ID: 9</div>
                  <div className="text-sm">Job Type: WC</div>
                </div>
                <div>Analysis of word count from document</div>
                <div className="p-2 text-sm">🔴Failed 2021/10/12 12:00:00</div>
              </div>
            </li>
            <li>
              <div className="w-full flex flex-col justify-center p-1 border border-gray-600 rounded-md shadow-md">
                <div className="w-full flex flex-row justify-between p-2">
                  <div className="text-sm">ID: 10</div>
                  <div className="text-sm">Job Type: WC</div>
                </div>
                <div>
                  Analysis of word count from another more different document
                </div>
                <div className="p-2 text-sm">
                  🟢Completed 2021/10/12 12:00:00
                </div>
              </div>
            </li>
            <li>
              <div className="w-full flex flex-col justify-center p-1 border border-gray-600 rounded-md shadow-md">
                <div className="w-full flex flex-row justify-between p-2">
                  <div className="text-sm">ID: 11</div>
                  <div className="text-sm">Job Type: WC</div>
                </div>
                <div>
                  Analysis of word count from another even more different
                  document
                </div>
                <div className="p-2 text-sm flex flex-row">
                  <Spinner />
                  Running since 2021/10/12 12:00:00
                </div>
              </div>
            </li>
          </ul>
        </nav>
        <main className="flex-grow p-8">
          <Outlet />
        </main>
      </div>
    </>
  );
};

export default ViewJobs;
