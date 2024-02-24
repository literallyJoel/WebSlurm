import Spinner from "@/components/Spinner/Spinner";
import type { Job } from "@/helpers/jobs";

interface props {
  job: Job;
}
const JobCard = ({ job }: props): JSX.Element => {
  const ProgressDiv = () => {
    if (job.jobComplete === 0) {
      return (
        <div className="p-2 text-sm flex flex-row">
          <Spinner />
          Running since{" "}
          {new Date(
            job.jobStartTime ? job.jobStartTime * 1000 : 0
          ).toLocaleDateString("en-GB")}
        </div>
      );
    } else if (job.jobComplete === 1) {
      return (
        <div className="p-2 text-sm">
          🟢Completed{" "}
          {new Date(
            job.jobCompleteTime ? job.jobCompleteTime * 1000 : 0
          ).toLocaleString("en-GB")}
        </div>
      );
    } else {
      return <div className="p-2 text-sm">🔴Failed</div>;
    }
  };

  return (
    <div className="w-full flex flex-col justify-center p-1 border border-gray-600 rounded-md shadow-md">
      <div className="w-full flex flex-row justify-between p-2">
        <div className="text-sm">ID: {job.jobID}</div>
        <div className="text-sm">Job Type: {job.jobTypeName ?? ""}</div>
      </div>
      <div>{job.jobName}</div>
      <ProgressDiv />
    </div>
  );
};

export default JobCard;
