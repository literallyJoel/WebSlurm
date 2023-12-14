import { Button } from "@/shadui/ui/button";
import { JobCard } from "./components/JobCard";
import { Link } from "react-router-dom";

const JobSettings = (): JSX.Element => {
  const testData = [
    { jobName: "Job 1", jobID: "1234", createdBy: "Joel Vivian" },
    { jobName: "Job 2", jobID: "1235", createdBy: "Cem Hoke" },
    { jobName: "Job 3", jobID: "123123", createdBy: "Paul Olar" },
    { jobName: "Job 1", jobID: "1234", createdBy: "Joel Vivian" },
    { jobName: "Job 2", jobID: "1235", createdBy: "Cem Hoke" },
    { jobName: "Job 3", jobID: "123123", createdBy: "Paul Olar" },
    { jobName: "Job 1", jobID: "1234", createdBy: "Joel Vivian" },
    { jobName: "Job 2", jobID: "1235", createdBy: "Cem Hoke" },
    { jobName: "Job 3", jobID: "123123", createdBy: "Paul Olar" },
    { jobName: "Job 1", jobID: "1234", createdBy: "Joel Vivian" },
    { jobName: "Job 2", jobID: "1235", createdBy: "Cem Hoke" },
    { jobName: "Job 3", jobID: "123123", createdBy: "Paul Olar" },
  ];
  return (
    <div className="flex flex-col justify-center items-center w-full">  
      <span className="text-3xl font-bold text-uol mb-4">Registered Jobs</span>
      <Link to="/admin/jobs/new">
        <Button className="bg-transparent border border-uol text-uol hover:bg-uol hover:text-white">
          Create new Job Type
        </Button>
      </Link>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 p-4 w-full">
        {testData.map((job) => (
          <JobCard
            jobName={job.jobName}
            jobID={job.jobID}
            createdBy={job.createdBy}
          />
        ))}
      </div>
    </div>
  );
};

export default JobSettings;
