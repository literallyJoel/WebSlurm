import { getJob, getParameters } from "@/helpers/jobs";
import { AuthContext } from "@/providers/AuthProvider/AuthProvider";
import { useContext } from "react";
import { useQuery } from "react-query";
import { useParams } from "react-router-dom";
import { GrStatusUnknown } from "react-icons/gr";
import Spinner from "@/components/Spinner/Spinner";
import { getJobType } from "@/helpers/jobTypes";

const JobInfo = (): JSX.Element => {
  const { jobID } = useParams();
  const token = useContext(AuthContext).getToken();
  const { data: jobInfo } = useQuery(`job${jobID}Info`, () => {
    return jobID ? getJob(jobID, token) : false;
  });

  const {data: jobType} = useQuery("jobType", () =>{
    return getJobType(token, `${jobInfo ? jobInfo.jobTypeID : ""}`)
  },{
    enabled: jobInfo !== false && jobInfo !== undefined
  })
  const { data: parameters } = useQuery(`job${jobID}Parameters`, () => {
    return jobID ? getParameters(jobID, token) : [];
  });

  return jobInfo === false ? (
    <div className="flex h-full flex-col gap-2 items-center justify-center">
      <GrStatusUnknown className="text-8xl text-uol animate-blueBounce" />
      <div className="text-2xl">
        Specified job does not exist or you do not have permission to access it.
      </div>
    </div>
  ) : jobInfo ? (
    <div className="h-full flex flex-col gap-4">
      <div className="text-4xl font-bold">{jobInfo.jobName}</div>
      <div className="flex flex-col w-full justify-center items-center">
        <div className="flex flex-row w-8/12 justify-center border border-black rounded-md">
          <div className="w-1/2 flex flex-row justify-between p-4">
            <div>ID: {jobInfo.jobID}</div>
            <div>Job Type: {jobInfo.jobTypeName}</div>
          </div>
        </div>
      </div>
      <div className="flex flex-col w-full justify-center items-center">
        <div className="flex flex-row w-8/12 justify-center border border-black rounded-md">
          <div className="w-1/2 flex flex-row justify-center p-4">
            {jobInfo.jobComplete === 0 ? (
              <div className="p-2 text-sm flex flex-row items-center">
                <Spinner />
                Running since{" "}
                {new Date(
                  jobInfo.jobCompleteTime ? jobInfo.jobCompleteTime * 1000 : 0
                ).toLocaleDateString("en-GB")}
              </div>
            ) : jobInfo.jobComplete === 1 ? (
              <div className="p-2 text-sm">
                🟢 Completed{" "}
                {new Date(
                  jobInfo.jobCompleteTime ? jobInfo.jobCompleteTime * 1000 : 0
                ).toLocaleString("en-GB")}
              </div>
            ) : (
              <div className="p-2 text-sm">🔴 Failed</div>
            )}
          </div>
        </div>
      </div>
      {parameters && (
        <div className="flex flex-row w-full justify-center">
          <div className="border border-black w-8/12 rounded-md p-2 flex flex-col">
            <>
              <div className="border-b border-b-black p-2 font-bold ">
                Provided Parameters
              </div>
              <div
                className={`grid grid-cols-${
                  parameters.length < 3 ? parameters.length : "3"
                } gap-y-5 p-4`}
              >
                {[...parameters].map((param) => (
                  <div>
                    <div className="font-bold">{param.key}</div>
                    <div>{param.value}</div>
                  </div>
                ))}
              </div>
            </>
          </div>
        </div>
      )}

      {jobType?.fileUploadCount !== 0 && (
        <div>
            <div className="border border-black w-8/12 rounded-md p-2 flex flex-col">
                <div className="border-b border-b-black p-2 font-bold ">
                Uploaded Files
                </div>
                <div className="p-4">
                <div className="text-sm">
                    {jobType?.fileUploadCount === 0
                    ? "No files uploaded"
                    : "Files uploaded"}
                </div>
                </div>
            </div>
        </div>
      )}

      
    </div>
  ) : (
    <>Loading...</>
  );
};

export default JobInfo;
