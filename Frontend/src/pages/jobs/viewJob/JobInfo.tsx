import { FileViewer } from "@/components/jobs/FileViewer";
import { downloadInputTree, downloadOutputTree } from "@/helpers/files";
import { getJob, getParameters } from "@/helpers/jobs";
import { useAuthContext } from "@/providers/AuthProvider";

import { useQuery } from "react-query";
import { useParams } from "react-router-dom";
import { GrStatusUnknown } from "react-icons/gr";
import Spinner from "@/components/Spinner/Spinner";
const JobInfo = (): JSX.Element => {
  const { jobId } = useParams();
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const { jobID } = useParams();

  const { data: jobInfo } = useQuery(
    `job${jobId}Info`,
    async () => {
      const job = await getJob(jobId ?? "", token);
      return Array.isArray(job) ? job[0] : job;
    },
    {
      enabled: !!jobId,
    }
  );

  const { data: parameters } = useQuery(
    `job${jobID}Parameters`,
    () => {
      return jobID ? getParameters(jobID, token) : [];
    },
    {
      retry: false,
    }
  );

  const { data: inputFileTree } = useQuery(
    `job${jobId}InputTree`,
    () => {
      return downloadInputTree({ token: token, jobId: jobId! });
    },
    {
      enabled: !!jobId,
    }
  );

  const { data: outputFileTree } = useQuery(
    `job${jobId}OutputTree`,
    () => {
      return downloadOutputTree({ token: token, jobId: jobId! });
    },
    {
      enabled: !!jobId,
    }
  );

  return !jobInfo ? (
    <div className="flex h-full flex-col gap-2 items-center justify-center">
      <GrStatusUnknown className="text-8xl text-uol animate-blueBounce" />
      <div className="text-2xl">
        Specified job does not exist or you do not have permission to access it.
      </div>
    </div>
  ) : jobInfo ? (
    <div className="h-full flex flex-col gap-2">
      <div className="text-4xl font-bold">{jobInfo.jobName}</div>
      <div className="flex flex-col w-full justify-center items-center">
        <div className="flex flex-row w-8/12 justify-center border border-black rounded-md">
          <div className="w-1/2 flex flex-row justify-between p-4">
            <div>ID: {jobInfo.jobId}</div>
            <div>Job Type: {jobInfo.jobTypeName}</div>
          </div>
        </div>
      </div>
      <div className="flex flex-col w-full justify-center items-center">
        <div className="flex flex-row w-8/12 justify-center border border-black rounded-md">
          <div className="w-1/2 flex flex-row justify-center p-4">
            {Number(jobInfo.jobComplete) === 0 ? (
              <div className="p-2 text-sm flex flex-row items-center">
                <Spinner />
                Running since{" "}
                {new Date(
                  jobInfo.jobStartTime ? jobInfo.jobStartTime * 1000 : 0
                ).toLocaleString("en-GB")}
              </div>
            ) : Number(jobInfo.jobComplete) === 1 ? (
              <div className="p-2 text-sm">
                🟢 Completed{" "}
                {new Date(
                  jobInfo.jobCompleteTime
                    ? Number(jobInfo.jobCompleteTime) * 1000
                    : 0
                ).toLocaleString("en-GB")}
              </div>
            ) : (
              <div className="p-2 text-sm">🔴 Failed</div>
            )}
          </div>
        </div>
      </div>
      {parameters && parameters.length !== 0 && (
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
      {inputFileTree && inputFileTree.length !== 0 && (
        <div className="w-full flex flex-col items-center">
          <div className="border border-black w-8/12 rounded-md p-2 flex flex-col">
            <div className="border-b border-b-black p-2 font-bold ">
              Input Files
            </div>
            <div className="p-2 flex flex-col">
              <FileViewer
                tree={inputFileTree}
                type="in"
                jobId={jobId!}
                token={token}
              />
            </div>
          </div>
        </div>
      )}

      {outputFileTree && outputFileTree.length !== 0 && (
        <div className="w-full flex flex-col items-center">
          <div className="border border-black w-8/12 rounded-md p-2 flex flex-col">
            <div className="border-b border-b-black p-2 font-bold ">
              Output Files
            </div>
            <div className="p-2 flex flex-col">
              <FileViewer
                tree={outputFileTree}
                type="out"
                jobId={jobId!}
                token={token}
              />
            </div>
          </div>
        </div>
      )}
    </div>
  ) : (
    <div className="w-full h-full flex flex-col items-center">loading...</div>
  );
};

export default JobInfo;
