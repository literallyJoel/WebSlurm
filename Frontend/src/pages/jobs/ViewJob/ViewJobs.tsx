import Nav from "@/components/Nav";
import { getJobs, type Job } from "@/helpers/jobs";
import { AuthContext } from "@/providers/AuthProvider/AuthProvider";
import React, { ChangeEvent, useEffect, useMemo, useState } from "react";
import { useQuery } from "react-query";
import { Link, Outlet, useLocation, useParams } from "react-router-dom";
import JobCard from "./components/JobCard";

import { Input } from "@/shadui/ui/input";
import { FaFilter } from "react-icons/fa";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/shadui/ui/dropdown-menu";
import Spinner from "@/components/Spinner/Spinner";
import { Button } from "@/shadui/ui/button";
function useQueryParams() {
  const { search } = useLocation();
  return useMemo(() => new URLSearchParams(search), [search]);
}

const ViewJobs = (): JSX.Element => {
  const { jobID } = useParams();
  const queryParams = useQueryParams();
  const token = React.useContext(AuthContext).getToken();
  const filter = queryParams.get("filter") || "";
  const [filteredJobs, setFilteredJobs] = useState<Job[]>([]);
  const [searchTerm, setSearchTerm] = useState("");

  const allJobs = useQuery(
    "allJobs",
    () => {
      return getJobs(token);
    },
    { refetchInterval: 120000 }
  );

  useEffect(() => {
    //Refilter the jobs when the data changes
    handleFilter();
    setFilteredJobs((prev) =>
      prev.filter((job) =>
        job.jobName.toLowerCase().includes(searchTerm.toLowerCase())
      )
    );
  }, [allJobs.data]);

  const handleFilter = () => {
    if (allJobs.data) {
      if (filter === "running") {
        setFilteredJobs(
          allJobs.data.filter((job) => Number(job.jobComplete) === 0)
        );
      } else if (filter === "completed") {
        setFilteredJobs(
          allJobs.data.filter((job) => Number(job.jobComplete) === 1)
        );
      } else if (filter === "failed") {
        setFilteredJobs(
          allJobs.data.filter((job) => Number(job.jobComplete) === 2)
        );
      } else {
        setFilteredJobs(allJobs.data);
      }

      //Ensures that the selected job is always in and at the top of the list
      if (jobID) {
        setFilteredJobs((prev) => {
          const _prev = prev.filter((job) => `${job.jobID}` !== jobID);
          _prev.unshift(
            allJobs.data.filter((job) => `${job.jobID}` === jobID)[0]
          );
          return _prev;
        });
      }
    }
  };

  useEffect(() => {
    handleFilter();
  }, [filter]);
  const handleSearch = (e: ChangeEvent<HTMLInputElement>) => {
    if (e.target.value !== "") {
      setFilteredJobs((prev) => {
        let _prev = prev.filter((job) =>
          job.jobName.toLowerCase().includes(e.target.value.toLowerCase())
        );

        _prev = _prev.filter((job) => `${job.jobID}` !== jobID);
        _prev.unshift(
          allJobs.data!.filter((job) => `${job.jobID}` === jobID)[0]
        );
        return _prev;
      });

      //Ensures that the selected job is always in and at the top of the list
      if (jobID) {
        setFilteredJobs((prev) => {
          const _prev = prev.filter((job) => `${job.jobID}` !== jobID);
          _prev.unshift(
            allJobs.data!.filter((job) => `${job.jobID}` === jobID)[0]
          );
          return _prev;
        });
      }
    } else {
      handleFilter();
    }
    setSearchTerm(e.target.value);
  };

  return (
    <>
      <Nav />
      <div className="flex h-screen w-full text-center">
        <nav className="w-80 bg-gray-100 overflow-y-auto dark:bg-gray-800 border-r dark:border-gray-700 h-full px-6 py-4">
          <div className="flex justify-between">
            <h1 className="text-xl font-bold mb-4 w-full">Your Jobs</h1>
            <Link to="/jobs/create">
              <Button className="self-end">+</Button>
            </Link>
          </div>
          <div className="flex flex-row p-2 gap-2">
            <Input
              placeholder="Search"
              value={searchTerm}
              onChange={(e) => handleSearch(e)}
            />
            <DropdownMenu>
              <DropdownMenuTrigger>
                <FaFilter
                  className={`${
                    queryParams.get("filter") === "completed"
                      ? "text-green-500"
                      : queryParams.get("filter") === "failed"
                      ? "text-red-500"
                      : queryParams.get("filter") === "running"
                      ? "text-orange-400"
                      : ""
                  }`}
                />
              </DropdownMenuTrigger>
              <DropdownMenuContent>
                <Link
                  to={
                    queryParams.get("filter") === "completed"
                      ? "/jobs?filter="
                      : "?filter=completed"
                  }
                >
                  <DropdownMenuItem
                    className={`h-11 cursor-pointer ${
                      queryParams.get("filter") === "completed"
                        ? "bg-[#F1F5F9]"
                        : ""
                    }`}
                  >
                    🟢Completed
                  </DropdownMenuItem>
                </Link>
                <Link
                  to={
                    queryParams.get("filter") === "failed"
                      ? "/jobs?filter="
                      : "?filter=failed"
                  }
                >
                  <DropdownMenuItem
                    className={`h-11 cursor-pointer ${
                      queryParams.get("filter") === "failed"
                        ? "bg-[#F1F4F9]"
                        : ""
                    }`}
                  >
                    🔴Failed
                  </DropdownMenuItem>
                </Link>
                <Link
                  to={
                    queryParams.get("filter") === "running"
                      ? "/jobs?filter="
                      : "?filter=running"
                  }
                >
                  <DropdownMenuItem
                    className={`h-11 cursor-pointer ${
                      queryParams.get("filter") === "running"
                        ? "bg-[#F1F5F9]"
                        : ""
                    }`}
                  >
                    <Spinner />
                    Running
                  </DropdownMenuItem>
                </Link>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
          <ul className="space-y-2">
            {filteredJobs.map((job) => (
              <Link
                to={`/jobs/${job.jobID}${queryParams.get("filter")? `?filter=${filter}` : ""}`}
                key={job.jobID}
                className={`flex hover:bg-slate-200 ${
                  `${jobID}` === `${job.jobID}` ? "bg-slate-200" : ""
                }`}
              >
                <JobCard key={job.jobID} job={job} />
              </Link>
            ))}
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
