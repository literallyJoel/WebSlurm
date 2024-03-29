import {
  downloadInputFile,
  getJob,
  getParameters,
  type File,
  OutputFile,
  downloadOutputFile,
  downloadZip,
} from "@/helpers/jobs";
import { useState } from "react";
import { useQuery } from "react-query";
import { useParams } from "react-router-dom";
import { GrStatusUnknown } from "react-icons/gr";
import Spinner from "@/components/Spinner/Spinner";
import { getJobType } from "@/helpers/jobTypes";
import ExtendedViewer from "./components/ExtendedViewer";
import { Button } from "@/shadui/ui/button";
import { useAuthContext } from "@/providers/AuthProvider/AuthProvider";

const JobInfo = (): JSX.Element => {
  const { jobID } = useParams();
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const [hasFileUpload, setHasFileUpload] = useState(false);
  const [inputFile, setInputFile] = useState<File>();
  const [outputFile, setOutputFile] = useState<OutputFile>();
  const [arrayJobCount, setArrayJobCount] = useState(0);
  const { data: jobInfo } = useQuery(`job${jobID}Info`, () => {
    return jobID ? getJob(jobID, token) : false;
  });

  useQuery(
    `job${jobID}output`,
    () => {
      return downloadOutputFile(token, `${jobID}`);
    },
    {
      enabled: !!jobID && arrayJobCount < 2,
      onSuccess: (data) => {
        setOutputFile(data);
      },
    }
  );

  useQuery(
    `job${jobID}inputfiles`,
    () => {
      return downloadInputFile(token, `${jobID}`);
    },
    {
      enabled: jobInfo !== false && jobInfo !== undefined && !!hasFileUpload,
      onSuccess: (data) => {
        setInputFile(data);
      },
    }
  );

  useQuery(
    `job${jobID}Type`,
    () => {
      return getJobType(token, `${jobInfo ? jobInfo.jobTypeID : ""}`);
    },
    {
      enabled: jobInfo !== false && jobInfo !== undefined,
      onSuccess: (data) => {
        setHasFileUpload(data.hasFileUpload);
        setArrayJobCount(data.arrayJobCount);
      },
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

  return jobInfo === false ? (
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
            <div>ID: {jobInfo.jobID}</div>
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
                  jobInfo.jobCompleteTime ? jobInfo.jobCompleteTime * 1000 : 0
                ).toLocaleDateString("en-GB")}
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
      {inputFile && (
        <div className="w-full flex flex-col items-center">
          <div className="border border-black w-8/12 rounded-md p-2 flex flex-col">
            <div className="border-b border-b-black p-2 font-bold ">
              Uploaded Files
            </div>
            <div className="p-2 flex flex-col">
              <span>
                Viewing <span className="font-bold">{inputFile.fileName}</span>
              </span>
              {inputFile.fileExt === "zip" && (
                <span className="text-sm">
                  Double click file in viewer to download
                </span>
              )}
            </div>
            <div className="max-h-80 overflow-auto p-2">
              {arrayJobCount > 1 ? (
                <></>
              ) : (
                <ExtendedViewer
                  file={{ type: "file", content: inputFile }}
                  key={`in${jobID}`}
                  jobID={jobID ?? ""}
                />
              )}
            </div>
            <div className="flex flex-row justify-center w-full">
              {inputFile.fileExt === "zip" ? (
                <Button
                  className="w-1/2"
                  onClick={() => {
                    downloadZip(token, jobID ?? "");
                  }}
                >
                  Download {inputFile.fileName}
                </Button>
              ) : (
                <a href={inputFile.fileURL} download className="w-full">
                  <Button className="w-1/2">
                    Download {inputFile.fileName}
                  </Button>
                </a>
              )}
            </div>
          </div>
        </div>
      )}
      {outputFile && (
        <div className="w-full flex flex-col items-center max-h-11">
          <div className="border border-black w-8/12 rounded-md p-2 flex flex-col">
            <div className="border-b border-b-black p-2 font-bold ">Output</div>
            <div className="p-2 flex flex-col">
              <span>
                Viewing{" "}
                <span className="font-bold">
                  {outputFile.type === "file"
                    ? outputFile.content.fileName
                    : "Output"}
                </span>
              </span>
              {((outputFile.type === "file" &&
                outputFile.content.fileExt === "zip") ||
                outputFile.type === "meta") && (
                <span className="text-sm">
                  Double click file in viewer to download
                </span>
              )}
            </div>
            <ExtendedViewer
              file={outputFile}
              key={`out${jobID}`}
              jobID={jobID ?? ""}
            />

            {outputFile.type === "file" && (
              <div className="flex flex-row justify-center w-full">
                <a
                  href={outputFile.content.fileURL}
                  download
                  className="w-full"
                >
                  <Button className="w-1/2">
                    Download {outputFile.content.fileName}
                  </Button>
                </a>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  ) : (
    <div className="w-full h-full flex flex-col items-center">loading...</div>
  );
};

export default JobInfo;
