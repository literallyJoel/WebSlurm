import { useQuery } from "react-query";
import { getJobTypes } from "../../../../helpers/jobTypes";
import { Button } from "@/components/shadui/ui/button";
import { FaPlus } from "react-icons/fa";
import JobTypeCard from "@/components/jobTypes/JobCard";
import { Link } from "react-router-dom";
import { useAuthContext } from "@/providers/AuthProvider";

const JobTypes = (): JSX.Element => {
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const getAllJobTypes = useQuery("getAllTypes", () => {
    return getJobTypes(token);
  });
  return (
    <div className="w-full flex flex-col">
      <span className="text-2xl text-uol font-bold">Job Types</span>
      <div className="w-full flex flex-row justify-center p-4">
        <Link to="/admin/jobtypes/new">
          {" "}
          <Button className="bg-tranparent border-green-600 border hover:bg-green-600 group transition-colors">
            <FaPlus className="text-green-600 group-hover:text-white transition-colors" />
          </Button>
        </Link>
      </div>

      <div className="grid grid-cols-4">
        {getAllJobTypes.data?.map((jobType) => {
          return (
            <JobTypeCard
              key={jobType.jobTypeId}
              id={`${jobType.jobTypeId}`}
              name={jobType.jobTypeName}
              createdBy={jobType.createdByName}
            />
          );
        })}
      </div>
    </div>
  );
};

export default JobTypes;
