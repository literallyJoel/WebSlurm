import Spinner from "@/components/Spinner/Spinner";
import type { Job } from "@/helpers/jobs";
import { Badge } from "../shadui/ui/badge";
import Tooltip from "../Tooltip";

interface props {
  job: Job;
}
const JobCard = ({ job }: props): JSX.Element => {
  const ProgressDiv = () => {
    if (Number(job.jobComplete) === 0) {
      return (
        <div className="p-2 text-sm flex flex-row">
          <Spinner />
          Running since{" "}
          {new Date(
            job.jobStartTime ? job.jobStartTime * 1000 : 0
          ).toLocaleDateString("en-GB")}
        </div>
      );
    } else if (Number(job.jobComplete) === 1) {
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
    <div className="w-full flex flex-col  p-1 border border-gray-600 rounded-md shadow-md">
      <div className="flex flex-row justify-between pl-1 pr-1">
        <div>
          <Tooltip text="ID">
            <Badge className="justify-center" variant="default">
              {job.jobId}
            </Badge>
          </Tooltip>
          <Tooltip text="Job Type">
            {" "}
            <Badge className="justify-center ml-2" variant="destructive">
              {job.jobTypeName ?? ""}
            </Badge>
          </Tooltip>
        </div>
        <div>
          <Tooltip text="Created By">
            <Badge
              className="justify-center border-slate-400"
              variant="outline"
            >
              {job.createdByName}
            </Badge>
          </Tooltip>
        </div>
      </div>
      <div>{job.jobName}</div>
      <ProgressDiv />
    </div>
  );
};

export default JobCard;
